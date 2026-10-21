(function () {
    const root = document.querySelector('#devoluciones');
    if (!root) return;
    const $id = id => document.getElementById(id);
    const dinero = v => Number(v).toLocaleString('es-CO', {style: 'currency', currency: 'COP', maximumFractionDigits: 2});
    const escapar = v => { const e = document.createElement('span'); e.textContent = v ?? ''; return e.innerHTML.replaceAll('"', '&quot;').replaceAll("'", '&#39;'); };
    let detalle = null, solicitud = '', resumen = null, ocupado = false, version = 0;
    const error = (id, mensaje = '') => { $id(id).textContent = mensaje; $id(id).hidden = !mensaje; };
    async function api(url, options) {
        const response = await fetch(url, options);
        let data;
        try { data = await response.json(); } catch (_) { throw new Error('No se pudo leer la respuesta. Recargue la página.'); }
        if (!response.ok || data.type === 'error') throw new Error(data.msg || 'No se pudo completar la operación.');
        return data;
    }
    const texto = $.fn.dataTable.render.text();
    const tabla = $('#tabla-devoluciones').DataTable({
        responsive: true, order: [[0, 'desc']],
        ajax: (datos, callback) => { api('/api/devoluciones').then(r => { error('error-devoluciones'); callback(r); }).catch(e => { error('error-devoluciones', e.message); callback({data: []}); }); },
        language: {emptyTable: 'No hay devoluciones registradas', info: 'Mostrando _START_ a _END_ de _TOTAL_ devoluciones', infoEmpty: 'Sin devoluciones', infoFiltered: '(de _MAX_ devoluciones)', lengthMenu: 'Mostrar _MENU_', search: 'Buscar:', zeroRecords: 'No hay coincidencias', paginate: {first:'Primera', last:'Última', next:'Siguiente', previous:'Anterior'}},
        columns: [
            {data:'id'}, {data:'codigo', render:texto}, {data:'nombre_cliente', render:texto}, {data:'fecha', render:texto},
            ...['total','deuda_reducida','reembolso'].map(data => ({data, render: (v,t) => t === 'display' ? dinero(v) : Number(v)})),
            {data:'responsable', render:texto},
            {data:'id', orderable:false, searchable:false, render:id => `<button type="button" class="btn btn-sm bg-hover-azul text-white" data-detalle="${Number(id)}" aria-label="Ver devolución ${Number(id)}"><i class="fas fa-search"></i> Ver</button>`}
        ]
    });
    function limpiarVenta() {
        detalle = null; resumen = null;
        $id('contenido-devolucion').hidden = true;
        $id('guardar-devolucion').disabled = true;
    }
    $id('nueva-devolucion').onclick = () => {
        version++;
        $id('form-devolucion').reset(); limpiarVenta();
        solicitud = Array.from(crypto.getRandomValues(new Uint8Array(32)), b => b.toString(16).padStart(2, '0')).join('');
        $id('resultados-devolucion').replaceChildren(); error('error-form-devolucion');
        $('#modal-devolucion').modal({backdrop:'static', keyboard:false});
    };
    async function buscar() {
        const q = $id('buscar-venta-devolucion').value.trim();
        const actual = ++version;
        limpiarVenta(); error('error-form-devolucion');
        if (q.length < 2) { error('error-form-devolucion', 'Escriba al menos 2 caracteres para buscar.'); return; }
        $id('resultados-devolucion').textContent = 'Buscando ventas…';
        try {
            const r = await api('/api/devoluciones/buscar?q=' + encodeURIComponent(q));
            if (actual !== version) return;
            $id('resultados-devolucion').replaceChildren();
            if (!r.ventas.length) $id('resultados-devolucion').textContent = 'No se encontraron ventas.';
            r.ventas.forEach(v => {
                const b = document.createElement('button'); b.type = 'button'; b.className = 'list-group-item list-group-item-action';
                b.textContent = `Venta ${v.codigo} · ${v.nombre_cliente || 'Cliente de contado'} · ${v.fecha} · ${dinero(v.total_factura)}`;
                b.onclick = () => seleccionar(v.id); $id('resultados-devolucion').appendChild(b);
            });
        } catch(e) { if (actual === version) { $id('resultados-devolucion').replaceChildren(); error('error-form-devolucion',e.message); } }
    }
    $id('buscar-devolucion').onclick = buscar;
    $id('buscar-venta-devolucion').onkeydown = e => { if (e.key === 'Enter') { e.preventDefault(); buscar(); } };
    async function seleccionar(id) {
        const actual = ++version;
        limpiarVenta(); error('error-form-devolucion');
        try {
            const r = await api('/api/devoluciones/venta?id=' + Number(id));
            if (actual !== version) return;
            detalle = r;
            $id('resultados-devolucion').replaceChildren();
            const v = r.venta;
            $id('datos-venta-devolucion').textContent = `Venta ${v.codigo} · ${v.nombre_cliente || 'Cliente de contado'} · Total original: ${dinero(v.total_factura)} · Pagos recibidos: ${dinero(v.recaudo)} · Devoluciones anteriores: ${dinero(v.devoluciones_total)} · Reembolsos anteriores: ${dinero(v.reembolsos)} · Deuda actual: ${dinero(v.saldo_pendiente)}`;
            $id('lineas-devolucion').innerHTML = r.lineas.map(l => `<tr data-linea="${Number(l.id)}"><td>${escapar(l.nombre)}</td><td>${Number(l.cantidad)}</td><td>${Number(l.devueltas)}</td><td>${l.disponible}</td><td><input type="number" class="form-control" min="0" max="${l.disponible}" step="1" value="0" aria-label="Cantidad a devolver de ${escapar(l.nombre)}" ${l.disponible <= 0 ? 'disabled' : ''}></td><td><select class="form-control" aria-label="Destino de ${escapar(l.nombre)}"><option value="1">Vuelve al stock</option><option value="0">No vuelve al stock (dañado)</option></select></td><td data-valor>${dinero(0)}</td></tr>`).join('');
            $id('contenido-devolucion').hidden = false; calcular();
        } catch(e) { if (actual === version) error('error-form-devolucion',e.message); }
    }
    function seleccion() {
        return Array.from($id('lineas-devolucion').rows).map(tr => ({linea_id:Number(tr.dataset.linea), cantidad:Number(tr.querySelector('input').value), reintegrar:Number(tr.querySelector('select').value)})).filter(l => l.cantidad !== 0);
    }
    function calcular() {
        if (!detalle) return;
        let total = 0, valido = true, unidades = 0;
        Array.from($id('lineas-devolucion').rows).forEach(tr => {
            const l = detalle.lineas.find(l => Number(l.id) === Number(tr.dataset.linea));
            const n = Number(tr.querySelector('input').value);
            if (!Number.isInteger(n) || n < 0 || n > l.disponible) valido = false;
            const valor = Math.round(l.valor_centavos * (Number(l.devueltas) + n) / Number(l.cantidad)) - Math.round(l.valor_centavos * Number(l.devueltas) / Number(l.cantidad));
            total += valor; unidades += n;
            tr.querySelector('[data-valor]').textContent = dinero(valor/100);
        });
        const deuda = Math.round(Number(detalle.venta.saldo_pendiente)*100), reduccion = Math.min(deuda,total);
        resumen = {total:total/100, deuda_reducida:reduccion/100, reembolso:(total-reduccion)/100, saldo_posterior:(deuda-reduccion)/100};
        $id('resumen-devolucion').innerHTML = `<div class="row">${[['Valor de devolución',resumen.total],['Disminuye la deuda',resumen.deuda_reducida],['Reembolso en efectivo',resumen.reembolso],['Saldo pendiente',resumen.saldo_posterior]].map(([label,v]) => `<div class="col-md-3 mb-2">${label}<br><strong>${dinero(v)}</strong></div>`).join('')}</div>`;
        $id('guardar-devolucion').disabled = ocupado || !valido || unidades <= 0;
    }
    $id('lineas-devolucion').oninput = calcular;
    $id('form-devolucion').onsubmit = async e => {
        e.preventDefault();
        if (ocupado || !detalle || $id('guardar-devolucion').disabled) return;
        error('error-form-devolucion');
        ocupado = true; calcular();
        const decision = await Swal.fire({icon:'question', title:'Confirmar devolución', text:`Valor: ${dinero(resumen.total)}. Disminuye la deuda: ${dinero(resumen.deuda_reducida)}. Entregar en efectivo: ${dinero(resumen.reembolso)}. Saldo pendiente: ${dinero(resumen.saldo_posterior)}. Este registro no se puede editar ni eliminar.`, showCancelButton:true, confirmButtonText:'Registrar devolución', cancelButtonText:'Volver a revisar'});
        if (!decision.isConfirmed) { ocupado = false; calcular(); return; }
        const datos = new FormData();
        Object.entries({venta_id:detalle.venta.id, items:JSON.stringify(seleccion()), motivo:$id('motivo-devolucion').value, resumen:JSON.stringify(resumen), csrf:root.dataset.csrf, solicitud}).forEach(([k,v]) => datos.append(k,v));
        $('#form-devolucion :input').prop('disabled',true);
        try {
            const r = await api('/api/devoluciones/guardar', {method:'POST', body:datos});
            ocupado = false;
            await new Promise(resolve => {
                $('#modal-devolucion').one('hidden.bs.modal', resolve).modal('hide');
            });
            tabla.ajax.reload(null,false);
            await ver(r.id);
        } catch(e) { error('error-form-devolucion',e.message); }
        finally { ocupado = false; $('#form-devolucion :input').prop('disabled',false); calcular(); }
    };
    $('#modal-devolucion').on('hide.bs.modal', e => { if (ocupado) e.preventDefault(); });
    async function ver(id) {
        try {
            const r = await api('/api/devoluciones/detalle?id='+Number(id)), d = r.devolucion;
            $id('titulo-detalle-devolucion').textContent = `Devolución ${d.id} · Venta ${d.codigo}`;
            $id('detalle-devolucion').innerHTML = `<p>${escapar(d.fecha)} · ${escapar(d.nombre_cliente || 'Cliente de contado')} · Responsable: ${escapar(d.responsable)}</p><p><strong>Motivo:</strong> ${escapar(d.motivo)}</p><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Producto</th><th>Cantidad</th><th>Destino</th><th>Valor</th></tr></thead><tbody>${r.items.map(i=>`<tr><td>${escapar(i.nombre)}</td><td>${Number(i.cantidad)}</td><td>${Number(i.reintegrar) ? 'Vuelve al stock' : 'No vuelve al stock'}</td><td>${dinero(i.valor)}</td></tr>`).join('')}</tbody></table></div><p>Total devuelto: <strong>${dinero(d.total)}</strong></p><p>Disminución de deuda: <strong>${dinero(d.deuda_reducida)}</strong></p><p>Reembolso en efectivo: <strong>${dinero(d.reembolso)}</strong>${d.caja_id ? ' · Caja '+(Number(d.caja_id)+1000) : ' · Sin salida de caja'}</p><p>Saldo al registrar: ${dinero(d.saldo_anterior)} → <strong>${dinero(d.saldo_posterior)}</strong></p>`;
            $('#modal-detalle-devolucion').modal('show');
        } catch(e) { error('error-devoluciones',e.message); }
    }
    $('#tabla-devoluciones').on('click','[data-detalle]',function () { ver(this.dataset.detalle); });
})();
