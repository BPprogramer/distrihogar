(function(){
    const inicio = document.querySelector('#inicio');
    if(inicio){
        const ingresos = document.querySelector('#inicio_ingresos')
        const ganancias = document.querySelector('#inicio_ganancias')
        const costos = document.querySelector('#inicio_costos')
        const inventario = document.querySelector('#inicio_inventario')
        // const ingresos_reales = document.querySelector('#inicio_ingresos_reales')
        // const ganancias_reales = document.querySelector('#inicio_ganancias_reales')
        // const dinero_fiados = document.querySelector('#inicio_dinero_fiados')
        const numero_ventas = document.querySelector('#inicio_numero_ventas')
        const numero_fiados = document.querySelector('#inicio_numero_fiados')
        const numero_pagos = document.querySelector('#inicio_numero_pagos')
        const numero_cajas = document.querySelector('#inicio_numero_cajas')
        const numero_productos = document.querySelector('#inicio_numero_productos')
        const numero_clientes = document.querySelector('#inicio_numero_clientes')
        // const dinero_mercado_libre = document.querySelector('#inicio_dinero_mercado_libre')
        // const dinero_pendiente_mercado_libre = document.querySelector('#inicio_dinero_pendiente_mercado_libre')
        

        cargarInformacion();

        function mostrarInfo(resultado){
      
            ingresos.textContent = resultado.ingresos
            ganancias.textContent = resultado.ganancias
            costos.textContent = resultado.costos
            inventario.textContent = resultado.inventario
            // ingresos_reales.textContent = resultado.ingresos_reales
            // ganancias_reales.textContent = resultado.ganancias_reales
            // dinero_fiados.textContent = resultado.fiados
            numero_ventas.textContent = resultado.numero_ventas
            numero_fiados.textContent = resultado.numero_fiados
            numero_pagos.textContent = resultado.numero_pagos
            numero_cajas.textContent = resultado.numero_cajas
            numero_productos.textContent = resultado.numero_productos
            numero_clientes.textContent = resultado.numero_clientes
            // dinero_mercado_libre.textContent = resultado.dinero_mercado_libre
            // dinero_pendiente_mercado_libre.textContent = resultado.dinero_pendiente_mercado_libre
            
        }

        async function cargarInformacion(){
            const url = `${location.origin}/api/inicio`;
            try {
                const respuesta = await fetch(url);
                const resultado = await respuesta.json();
                mostrarInfo(resultado);
            } catch (error) {
                console.log(error)
            }
        }
    }
})();