// Compartido exclusivamente por Productos y el selector de productos en Ventas.
window.BusquedaProductos = (() => {
    const normalizar = valor => String(valor || '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const palabras = valor => normalizar(valor).match(/[a-z]+|\d+(?:[.,/]\d+)*/g) || [];

    // Una insercion, omision, sustitucion o intercambio de letras contiguas.
    function errorSimple(a, b) {
        if (Math.abs(a.length - b.length) > 1) return false;
        if (a === b) return true;
        let i = 0;
        while (i < Math.min(a.length, b.length) && a[i] === b[i]) i++;
        if (a.length === b.length) {
            return a.slice(i + 1) === b.slice(i + 1) ||
                (a[i] === b[i + 1] && a[i + 1] === b[i] && a.slice(i + 2) === b.slice(i + 2));
        }
        return a.length > b.length
            ? a.slice(i + 1) === b.slice(i)
            : a.slice(i) === b.slice(i + 1);
    }

    // 0: buscador anterior; 1: palabras; 2: errores; 3: sin coincidencia.
    function nivel(consulta, nombre, coincideAntes) {
        if (!consulta.trim() || coincideAntes) return 0;
        const terminos = palabras(consulta);
        const destino = palabras(nombre);
        if (!terminos.length) return 3;
        let conError = false;
        for (const termino of terminos) {
            const numero = /\d/.test(termino);
            if (destino.some(p => numero
                ? p.replace(',', '.') === termino.replace(',', '.')
                : !/\d/.test(p) && p.includes(termino))) continue;
            if (!numero && termino.length >= 4 && destino.some(p =>
                !/\d/.test(p) && p.length >= 4 && errorSimple(termino, p))) {
                conError = true;
            } else {
                return 3;
            }
        }
        return conError ? 2 : 1;
    }

    function configurarSelector(elemento) {
        const anterior = $.fn.select2.defaults.defaults.matcher;
        $(elemento).select2({
            theme: 'bootstrap4',
            matcher(params, data) {
                const consulta = params.term || '';
                const original = anterior(params, data);
                const prioridad = nivel(consulta, data.text || '', !!original);
                if (prioridad === 3 || (consulta.trim() && data.id === '0')) return null;
                return Object.assign({}, original || data, { prioridadBusqueda: prioridad });
            },
            sorter: resultados => resultados.sort((a, b) => a.prioridadBusqueda - b.prioridadBusqueda)
        });
    }

    function configurarTabla(tabla) {
        const settings = tabla.settings()[0];
        const entrada = $(tabla.table().container()).find('.dataTables_filter input');
        let consulta = '';
        let original = null;
        const prioridades = new Map();
        const filtro = (config, datos, indice) => {
            if (config !== settings || !consulta) return true;
            const prioridad = nivel(consulta, datos[2], original.test(datos.join('  ')));
            prioridades.set(indice, prioridad);
            return prioridad < 3;
        };
        $.fn.dataTable.ext.search.push(filtro);
        entrada.off('.DT').on('input.busquedaProductos search.busquedaProductos', function () {
            consulta = this.value.trim();
            original = $.fn.dataTable.ext.internal._fnFilterCreateSearch(consulta, false, true, true);
            prioridades.clear();
            tabla.search('').draw();
        });
        $(settings.nTable).on('preDraw.dt.busquedaProductos', () => {
            if (consulta) settings.aiDisplay.sort((a, b) => prioridades.get(a) - prioridades.get(b));
        }).on('destroy.dt.busquedaProductos', () => {
            const indice = $.fn.dataTable.ext.search.indexOf(filtro);
            if (indice !== -1) $.fn.dataTable.ext.search.splice(indice, 1);
            entrada.off('.busquedaProductos');
            $(settings.nTable).off('.busquedaProductos');
        });
    }

    return { nivel, configurarSelector, configurarTabla };
})();
