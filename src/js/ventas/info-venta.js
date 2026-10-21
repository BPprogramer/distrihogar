(function () {
    const mercadolibre = document.querySelector('#mercadolibre');
    const ventas = document.querySelector('#ventas');
    if (mercadolibre || ventas) {

        $('#tabla').on('click', '#info', function (e) {
            const ventaId = e.currentTarget.dataset.ventaId;
 
            consultarInfoVenta(ventaId);
        })
        async function consultarInfoVenta(id) {
         
            $('#modal-info').modal('show');

            const url = `${location.origin}/api/venta?id=${id}`;
            try {
                const respuesta = await fetch(url);
                const resultado = await respuesta.json();
                mostrarInfoVenta(resultado);
            } catch (error) {

            }



        }

        function mostrarInfoVenta(resultado) {
            const { productos_venta , venta} = resultado
      
            const codigoVenta = document.querySelector('#codigo-venta');
            const clienteVenta = document.querySelector('#cliente-venta');
            const fechaVenta = document.querySelector('#fecha-venta');

            const totalVenta = document.querySelector('#total-venta');
            const totalVentaSinComision = document.querySelector('#total-sin-comision');
            const recaudoVenta = document.querySelector('#recaudo-venta');
            const saldoVenta = document.querySelector('#saldo-venta');
            const metodoVenta = document.querySelector('#metodo-venta');
            const estadoVenta = document.querySelector('#estado-venta');
            
            codigoVenta.textContent = venta.codigo
            clienteVenta.textContent = venta.nombre_cliente
            fechaVenta.textContent = venta.fecha
            totalVenta.textContent = (parseFloat(venta.total_factura)).toLocaleString('en');
            totalVentaSinComision.textContent = (parseFloat(venta.total)).toLocaleString('en');
            recaudoVenta.textContent = (parseFloat(venta.recaudo)).toLocaleString('en');
            saldoVenta.textContent = (parseFloat(venta.total_factura - venta.recaudo)).toLocaleString('en');

         
            if(venta.metodo_pago==2){
                metodoVenta.textContent = 'Fiado'
            }
            if(venta.metodo_pago==3){
                metodoVenta.textContent = 'Mercad Libre'
            }
            if(venta.metodo_pago==1){
                metodoVenta.textContent = 'De Contado'
            }
            if(estadoVenta.classList.contains('text-danger')){
                estadoVenta.classList.remove('text-danger')
            }
            if(estadoVenta.classList.contains('text-success')){
                estadoVenta.classList.remove('text-success')
            }
            if(venta.estado==0){
                estadoVenta.textContent = 'Pendiente'
                estadoVenta.classList.add('text-danger')
            }else{
                estadoVenta.textContent = 'Pagado'
                estadoVenta.classList.add('text-success')
            }

            
            const bodyProductos = document.querySelector('#body-productos-venta');
            limpiarHtml(bodyProductos);

         

         
            productos_venta.forEach(producto => {
                const { nombre, cantidad, precio } = producto
                const tr = document.createElement('TR');
                const tdNombre = document.createElement('td');
                tdNombre.textContent = nombre;
                const tdCantidad = document.createElement('td')
                tdCantidad.textContent = cantidad;
                const tdPrecio = document.createElement('td');
                tdPrecio.textContent = (parseFloat(precio)).toLocaleString('en');
                const tdSubTotal = document.createElement('td');
                tdSubTotal.textContent = (parseFloat(precio * cantidad)).toLocaleString('en');


                tr.appendChild(tdNombre)
                tr.appendChild(tdCantidad)
                tr.appendChild(tdPrecio)
                tr.appendChild(tdSubTotal)

                bodyProductos.appendChild(tr);
            })
            
        }
        function limpiarHtml(referencia) {

            while (referencia.firstChild) {
                referencia.removeChild(referencia.firstChild)
            }
        }
    }
})();