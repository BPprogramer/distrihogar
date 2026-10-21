(function(){

  
    const reporte = document.querySelector('#reporte');
    if(reporte){
        const ingresos = document.querySelector('#reporte_ingresos')
        const ganancias = document.querySelector('#reporte_ganancias')
        const costos = document.querySelector('#reporte_costos')
        const inventario = document.querySelector('#reporte_inventario')
        // const ingresos_reales = document.querySelector('#reporte_ingresos_reales')
        // const ganancias_reales = document.querySelector('#reporte_ganancias_reales')
        const dinero_fiados = document.querySelector('#reporte_dinero_fiados')
        const numero_ventas = document.querySelector('#reporte_numero_ventas')
        const numero_fiados = document.querySelector('#reporte_numero_fiados')
        const numero_pagos = document.querySelector('#reporte_numero_pagos')
        const numero_cajas = document.querySelector('#reporte_numero_cajas')
        const numero_productos = document.querySelector('#reporte_numero_productos')
        const numero_clientes = document.querySelector('#reporte_numero_clientes')

        const fecha = document.querySelector('#fecha');
        fecha.addEventListener('input',function(e){
        
            cargarInformacion(e.target.value);
        })
    

        llenarIputFecha();

        function llenarIputFecha(){
            const fecha_actual_utc = new Date();

            // Ajustar al huso horario de Colombia (UTC-5)
            const fecha_actual_colombia = new Date(fecha_actual_utc.getTime() - (5 * 60 * 60 * 1000));
        
            // Formatear la fecha y asignarla al input
            const fecha_actual_formateada = fecha_actual_colombia.toISOString().slice(0, 10);
            fecha.value = fecha_actual_formateada;
        
            // Cargar información con la fecha ajustada
            cargarInformacion(fecha.value);
        }

       

        function mostrarInfo(resultado){
   
            ingresos.textContent = resultado.ingresos_totales
            ganancias.textContent = resultado.ganancias
            costos.textContent = resultado.costos
            inventario.textContent = resultado.total_inventario
            // ingresos_reales.textContent = resultado.ingresos_reales
            // ganancias_reales.textContent = resultado.ganancias_reales
            // dinero_fiados.textContent = resultado.fiados
            numero_ventas.textContent = resultado.numero_ventas
            numero_fiados.textContent = resultado.numero_fiados
            numero_pagos.textContent = resultado.numero_pagos
            numero_cajas.textContent = resultado.numero_cajas
            numero_productos.textContent = resultado.numero_productos
            numero_clientes.textContent = resultado.numero_clientes
            
        }

        async function cargarInformacion(fecha){

            const datos = new FormData();
            datos.append('fecha',fecha);
            const url = `${location.origin}/api/info-general`;

            try {
                const respuesta = await fetch(url,{
                    method:'POST',
                    body:datos
                });
                const resultado = await respuesta.json();
                console.log(resultado)
                // console.log(resultado);
                mostrarInfo(resultado);
            } catch (error) {
                console.log(error)
            }
        }
    }
})();