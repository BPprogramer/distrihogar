# Devoluciones

Se accede desde Ventas → Devoluciones. Admite ventas de contado y fiadas;
los cambios de producto y Mercado Libre quedan fuera de este módulo.

Antes de publicar el código en otra instalación:

```sh
php migrations/devoluciones.php
```

La migración es aditiva e idempotente: crea dos tablas InnoDB sin modificar
ventas, abonos ni cierres existentes. Requiere PHP 8 y MySQL con `GET_LOCK`.
Las tablas existentes involucradas deben ser transaccionales (InnoDB).

## Reglas

- Se calcula el valor desde las líneas y el total históricos de la factura.
  Los descuentos globales se distribuyen proporcionalmente y el redondeo
  acumulado en centavos evita diferencias en devoluciones parciales sucesivas.
- Primero se reduce la deuda. El excedente se reembolsa en efectivo desde
  la caja abierta actual, validando fondos disponibles y pagos recibidos.
- El saldo del fiado es total original menos recaudo menos reducciones de deuda.
  Los nuevos abonos respetan ese saldo; no se inventan abonos para cancelar deuda.
- Solo los productos marcados como vendibles regresan al stock disponible.
- Cabecera, detalle, inventario, estado de la venta y contador de caja se guardan
  en una transacción. La clave de solicitud evita duplicados al reintentar.
- Las escrituras de API comparten un bloqueo MySQL por base de datos para evitar
  cruces entre devoluciones, abonos, edición de ventas y cierres.
- No se permite editar o eliminar devoluciones, ventas con devoluciones ni sus
  abonos. La edición/eliminación de ventas también valida caja abierta y ausencia
  de abonos al guardar, no solo al entrar al formulario.
- Los cierres originales no se recalculan. El cierre actual resta reembolsos una
  sola vez, sin crear además un egreso duplicado.
- Los reportes históricos mantienen sus indicadores originales y muestran un
  resumen separado de devoluciones, reducciones de deuda y reembolsos. No se
  estima una utilidad neta de devoluciones: las líneas antiguas no conservan
  costos unitarios históricos suficientes para calcularla con exactitud.

## Verificación

```sh
php tests/devoluciones.php
php tests/productos-activos.php
php tests/productos-activos-unit.php
node tests/busqueda-productos.test.js
node node_modules/gulp/bin/gulp.js js
```

Las pruebas de integración usan exclusivamente tablas temporales que ocultan las
tablas reales durante su conexión. Cubren abonos posteriores, reembolsos mixtos,
stock, descuentos, duplicados, falta de fondos, importes alterados, rollback,
protección de edición y conservación de cierres.
