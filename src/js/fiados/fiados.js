(function () {
    const seccionFiados = document.querySelector('#seccion-fiados');


    if (seccionFiados) {

        let datosDeuda = {
            monto: 0,
            deuda: 0,
            saldo: 0,
            cliente_id: ''
        }

        let ventasInpagas = [];
        let idCliente = '';
        const btnSumitPago = document.querySelector('#btnSubmitPago');

        const bodyFiados = document.querySelector('#body-fiados');
        const bodyPagos = document.querySelector('#body-pagos');
        const totalDeuda = document.querySelector('#total-deuda');
        const btnPagar = document.querySelector('#pagar');
        const formulario = document.querySelector('#pagoForm')

        const deudaActual = document.querySelector('#deuda-actual');
        const btnPagarTodo = document.querySelector('#pagar-todo');
        const monto = document.querySelector('#monto');
        const saldoRestante = document.querySelector('#saldo-restante');

        btnPagarTodo.addEventListener('click', function () {
            datosDeuda.monto = datosDeuda.deuda
            monto.value = parseFloat(datosDeuda.monto).toLocaleString('en');
            calcularSaldoRestante()
        })

        monto.addEventListener('input', function (e) {

            const valor = e.target.value;

            let deudaSinFormat = parseFloat(valor.replace(/,/g, ''));

            if (deudaSinFormat == '') {
                deudaSinFormat = 0;
            }
            datosDeuda.monto = deudaSinFormat;
            calcularSaldoRestante()
            const monto_ingresado = formatearValor(valor);


            monto.value = monto_ingresado;

        })


        $('#selectClientes').on('select2:select', function (e) {
            if (e.target.value != 0) {

                consultarInfoCliente(e.target.value);
            } else {
                //resetearCliente();
            }
        });
        btnPagar.addEventListener('click', function () {
            id = null;
            accionesModal();
        })

        async function enviarDatos() {
            const datos = new FormData();
            datos.append('cliente_id', datosDeuda.cliente_id);
            datos.append('monto', datosDeuda.monto);
            btnSumitPago.disabled = true;
            const url = `${location.origin}/api/pagar`;
            try {
                const respuesta = await fetch(url, {
                    method: 'POST',
                    body: datos
                });
                const resultado = await respuesta.json();
                btnSumitPago.disabled = false;
                $('#modal-pago').modal('hide');
                eliminarToastAnterior();

                if (resultado.type == 'error') {
                    $(document).Toasts('create', {
                        class: 'bg-danger',
                        title: 'Error',

                        body: resultado.msg
                    })

                } else {

                    $(document).Toasts('create', {
                        class: 'bg-azul text-blanco',
                        title: 'Completado',

                        body: resultado.msg
                    })
                    consultarInfoCliente(idCliente)
                }
                setTimeout(() => {
                    eliminarToastAnterior();
                }, 8000)
            } catch (error) {

            }

        }

        function calcularSaldoRestante() {
            datosDeuda.saldo = datosDeuda.deuda - datosDeuda.monto;
            if (isNaN(datosDeuda.saldo)) {
                datosDeuda.saldo = datosDeuda.deuda;
            }
            saldoRestante.textContent = '$' + parseFloat(datosDeuda.saldo).toLocaleString('en')
        }

        function accionesModal() {

            formulario.reset();

            btnSumitPago.disabled = false;
            if ($('#selectClientes').val() != 0) {
                $('#modal-pago').modal('show');

                deudaActual.value = parseFloat(datosDeuda.deuda).toLocaleString('en')
                saldoRestante.textContent = "$" + parseFloat(datosDeuda.saldo).toLocaleString('en');
                inicializarValidador();

            } else {
                Swal.fire({
                    icon: 'warning',
                    text: 'Por favor seleccione un cliente',
                })
            }

        }




        async function consultarInfoCliente(id) {
            idCliente = id;
            try {
                saldoRestante.textContent = '$0';
                const respuesta = await fetch(`${location.origin}/api/pagos-cuotas?id=${id}`);

                const resultado = await respuesta.json();


                limpiarHtml(bodyFiados);
                limpiarHtml(bodyPagos);
                if (resultado.fiados.length == 0) {
                    Swal.fire({
                        icon: 'warning',
                        text: 'No hay fiados para este cliente',
                    })
                    totalDeuda.textContent = 0;
                    datosDeuda.deuda = 0;
                    datosDeuda.monto = 0;
                    datosDeuda.saldo = 0;


                } else {
                    const { fiados, pagos_cuotas } = resultado;
                    mostrarFiados(fiados);
                    mostrarPagos(pagos_cuotas)
                }

            } catch (error) {

                console.log(error)
            }
        }

        function mostrarPagos(pagos) {


            pagos.forEach(pago => {
                const { numero_pago, monto, fecha_pago } = pago;

                const tr = document.createElement('TR');

                const tdNumeroPago = document.createElement('TD');
                tdNumeroPago.textContent = '#' + numero_pago;

                const tdMonto = document.createElement('TD');
                tdMonto.textContent = '$' + parseFloat(monto).toLocaleString('en');

                const tdFecha = document.createElement('TD');
                tdFecha.textContent = fecha_pago;

                const tdAcciones = document.createElement('TD');
                const divAcciones = document.createElement('DIV');
                divAcciones.classList.add('d-flex', 'ustify-content-start');

                // const btnInfo = document.createElement('BUTTON');
                // btnInfo.type = 'button'
                // btnInfo.classList.add('btn', 'btn-sm', 'mr-4', 'bg-hover-azul', 'text-white', 'toolMio');
                // btnInfo.innerHTML = '<span class="toolMio-text">Ver</span><i class="fas fa-search"></i>';

                const btnEliminar = document.createElement('BUTTON');
                btnEliminar.type = 'button'
                btnEliminar.classList.add('btn', 'btn-sm', 'bg-hover-azul', 'text-white', 'toolMio');
                btnEliminar.innerHTML = '<span class="toolMio-text">Eliminar</span><i class="fas fa-trash"></i>';

                btnEliminar.onclick = function () {
                    //eliminarPago(pago.numero_pago); //vamos a revisar que la 
                    alertaEliminarPago(pago);
                }
                // divAcciones.appendChild(btnInfo);
                divAcciones.appendChild(btnEliminar);
                tdAcciones.appendChild(divAcciones);

                tr.appendChild(tdNumeroPago)
                tr.appendChild(tdMonto)
                tr.appendChild(tdFecha)

                tr.appendChild(tdAcciones);
                bodyPagos.appendChild(tr);

            });

        }

        function alertaEliminarPago(pago){
            
            
            Swal.fire({
                icon:'warning',
                html: `<h2 class="">esta seguro de eliminar el pago por un valor de <span class="font-weight-bold"> ${(parseFloat(pago.monto)).toLocaleString('en')} </span>?</h2><br><p>Esta acción no se puede deshacer</p>`,
          
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: `Cancelar`,
                

            }).then(result=>{
                if(result.isConfirmed){
                    eliminarPago(pago)
                }
            })
        }

        async function eliminarPago(pago) {
           
            const datos = new FormData();
            datos.append('numero_pago', pago.numero_pago);
            const url = `${location.origin}/api/eliminar-pago`;
            try {
                const respuesta = await fetch(url, {
                    method: 'POST',
                    body: datos
                })
                const resultado = await respuesta.json();
              
               
                eliminarToastAnterior();

                if (resultado.type == 'error') {
                    $(document).Toasts('create', {
                        class: 'bg-danger',
                        title: 'Error',

                        body: resultado.msg
                    })
                } else {
                    console.log(resultado)
                    $(document).Toasts('create', {
                        class: 'bg-azul text-blanco',
                        title: 'Completado',

                        body: resultado.msg
                    })
                    consultarInfoCliente(idCliente)

                }

                setTimeout(() => {
                    eliminarToastAnterior();
                }, 8000)
            } catch (error) {

            }
        }


        function mostrarFiados(fiados) {

            let total_deuda = 0;

            fiados.forEach(fiado => {
                const { codigo, venta_id, total, recaudo, estado } = fiado;
                if (estado != 0) {
                    ventasInpagas.push();
                }

                const tr = document.createElement('TR');

                const tdCodigo = document.createElement('TD');
                tdCodigo.textContent = '#' + codigo;

                const tdTotal = document.createElement('TD');
                tdTotal.textContent = '$' + parseFloat(total).toLocaleString('en');

                const tdAbono = document.createElement('TD');
                tdAbono.textContent = '$' + parseFloat(recaudo).toLocaleString('en');

                const tdDeuda = document.createElement('TD');
                tdDeuda.textContent = '$' + parseFloat(total - recaudo).toLocaleString('en');

                const tdEstado = document.createElement('TD');

                const divEstado = document.createElement('DIV');
                divEstado.classList.add('d-flex', 'justify-content-left', 'text-center');

                const btnEstado = document.createElement('BUTTON');
                btnEstado.type = 'button'

                btnEstado.classList.add('btn', 'w-40', 'btn-inline', 'btn-sm');

                if (estado == 0) {
                    total_deuda = total_deuda + parseFloat(total) - parseFloat(recaudo);
                    btnEstado.textContent = 'Pendiente';
                    btnEstado.classList.add('btn-danger');
                } else {
                    btnEstado.textContent = 'Pagado';
                    btnEstado.classList.add('bg-azul', 'text-white');
                }



                divEstado.appendChild(btnEstado);
                tdEstado.appendChild(divEstado);

                const tdInfo = document.createElement('TD');
                const divInfo = document.createElement('DIV');
                divInfo.classList.add('d-flex', 'ustify-content-start');

                const btnInfo = document.createElement('BUTTON');
                btnInfo.type = 'button'
                btnInfo.classList.add('btn', 'btn-sm', 'bg-hover-azul', 'text-white', 'toolMio');
                btnInfo.innerHTML = '<span class="toolMio-text">Ver</span><i class="fas fa-search"></i>';

                btnInfo.onclick = () => {
                    consultarInfo(fiado);
                }

                divInfo.appendChild(btnInfo);
                tdInfo.appendChild(divInfo);

                tr.appendChild(tdCodigo)
                tr.appendChild(tdTotal)
                tr.appendChild(tdAbono)
                tr.appendChild(tdDeuda)
                tr.appendChild(tdEstado)
                tr.appendChild(tdInfo);
                bodyFiados.appendChild(tr);
                datosDeuda.cliente_id = fiado.cliente_id;
            });
            totalDeuda.textContent = parseFloat(total_deuda).toLocaleString('en')
            datosDeuda.deuda = total_deuda;
            datosDeuda.saldo = total_deuda;




        }

        function mostrarInfoFiado(fiado, productos) {


            const codigoVenta = document.querySelector('#codigo-venta');
            const clienteVenta = document.querySelector('#cliente-venta');
            const fechaVenta = document.querySelector('#fecha-venta');

            const totalVenta = document.querySelector('#total-venta');
            const recaudoVenta = document.querySelector('#recaudo-venta');
            const saldoVenta = document.querySelector('#saldo-venta');

            codigoVenta.textContent = fiado.codigo
            clienteVenta.textContent = fiado.nombre_cliente
            fechaVenta.textContent = fiado.fecha
            totalVenta.textContent = (parseFloat(fiado.total)).toLocaleString('en');
            recaudoVenta.textContent = (parseFloat(fiado.recaudo)).toLocaleString('en');
            saldoVenta.textContent = (parseFloat(fiado.total - fiado.recaudo)).toLocaleString('en');

            const bodyProductos = document.querySelector('#body-productos-fiados');
            limpiarHtml(bodyProductos);

            productos.forEach(producto => {
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

        async function consultarInfo(fiado) {
            $('#modal-info').modal('show');

            try {
                const respuesta = await fetch(`${location.origin}/api/productos-fiados?venta-id=${fiado.venta_id}`);
                const resultado = await respuesta.json();
                if (resultado.type == 'error') {
                    Swal.fire({
                        icon: 'error',
                        text: resultado.msg,
                    })
                } else {
                    mostrarInfoFiado(fiado, resultado)
                }
            } catch (error) {

            }



        }

        function formatearValor(valor) {

            let valor_sin_formato = parseFloat(valor.replace(/,/g, ''));
            if (isNaN(valor_sin_formato)) {
                valor_sin_formato = '';
            }
            const valor_formateado = valor_sin_formato.toLocaleString('en');
            return valor_formateado;
        }



        function limpiarHtml(referencia) {

            while (referencia.firstChild) {
                referencia.removeChild(referencia.firstChild)
            }
        }


        function inicializarValidador() {
            $.validator.setDefaults({
                submitHandler: function () {
                    enviarDatos();
                }
            });

            // Función para validar que el valor sea diferente de "0"
            function notEqualChar(value, element, param) {
                return value !== param;
            }

            $('#pagoForm').validate({
                rules: {
                    monto: {
                        required: true,
                        customValidation: '0' // Carácter que se debe evitar
                    }
                },
                messages: {
                    monto: {
                        required: 'El monto a pagar es obligatorio',
                        customValidation: 'El monto no puede ser igual a "0"'
                    }
                },
                errorElement: 'span',
                errorPlacement: function (error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function (element, errorClass, validClass) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function (element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');
                }
            });

            // Agregar la regla personalizada utilizando la función
            $.validator.addMethod('customValidation', function (value, element) {
                return notEqualChar(value, element, '0');
            }, 'Este campo no puede ser igual a "0"');
        }

        function eliminarToastAnterior() {
            if (document.querySelector('#toastsContainerTopRight')) {
                document.querySelector('#toastsContainerTopRight').remove()
            }
        }

        // // Llamar a la función de inicialización al cargar la página
        // $(document).ready(function () {
        //     inicializarValidador();
        // });

        // // Volver a inicializar el validador cuando se detecta que el formulario es válido
        // $('#pagoForm').on('valid', function (event) {
        //     inicializarValidador();
        // });

    }


})();