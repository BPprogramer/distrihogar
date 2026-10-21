<?php
// Pruebas de integración: todas las escrituras se hacen en tablas TEMPORARY,
// que ocultan las tablas reales exclusivamente durante esta conexión.
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../includes/funciones.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/../includes')->safeLoad();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($_ENV['DB_HOST'],$_ENV['DB_USER'],$_ENV['DB_PASS'],$_ENV['DB_NAME']);
Model\ActiveRecord::setDB($db);
session_start();
$_SESSION = ['id'=>1,'nombre'=>'Pruebas','roll'=>1];
foreach (['ventas','productos','productos_venta','cajas','cuotas','pago_cuotas','clientes','ingresos','egresos','usuarios','devoluciones','devoluciones_detalle'] as $tabla) {
    $engine=$db->query("SHOW TABLE STATUS WHERE Name='$tabla'")->fetch_assoc()['Engine'];
    if ($engine !== 'InnoDB') throw new RuntimeException('Las tablas deben ser transaccionales: '.$tabla);
    $columnas = [];
    foreach ($db->query("SHOW COLUMNS FROM $tabla") as $columna) {
        $columnas[] = '`'.$columna['Field'].'` '.$columna['Type'].($columna['Field']==='id' ? ' NOT NULL AUTO_INCREMENT PRIMARY KEY' : ' NULL');
    }
    $db->query("CREATE TEMPORARY TABLE $tabla (".implode(',', $columnas).") ENGINE=InnoDB");
}
$db->query('ALTER TABLE devoluciones ADD UNIQUE (solicitud)');
function verificar($ok,$mensaje) { if (!$ok) throw new RuntimeException($mensaje); }
function falla($fn,$mensaje) { try { $fn(); } catch (DomainException $e) { return; } throw new RuntimeException($mensaje); }
function endpoint($fn,$post) { $_POST=$post; ob_start(); $fn(); $r=json_decode(ob_get_clean(),true); verificar(is_array($r),'Respuesta JSON válida'); return $r; }
function elegir($linea,$cantidad,$stock=1) { return ['linea_id'=>$linea,'cantidad'=>$cantidad,'reintegrar'=>$stock]; }
function registrar($venta,$items,$token=null) {
    $r=Model\Devolucion::calcular(Model\Devolucion::detalleVenta($venta),$items);
    return Model\Devolucion::registrar($venta,$items,'Prueba de devolución',1,$token??bin2hex(random_bytes(32)),$r);
}
$db->query("INSERT INTO cajas (id,estado,efectivo_apertura,efectivo_cierre,numero_transacciones) VALUES (1,1,1000,81000,1),(2,0,500000,0,0)");
$db->query("INSERT INTO productos (id,nombre,stock,ventas,precio_compra) VALUES (1,'Producto A',10,20,25000),(2,'Producto B',10,20,100)");
$db->query("INSERT INTO ventas (id,codigo,total,total_factura,recaudo,costo,metodo_pago,estado,caja_id,fecha) VALUES (1,1001,200000,200000,80000,100000,2,0,1,'2026-01-01'),(2,1002,100000,100000,100000,50000,1,1,1,'2026-01-01'),(3,1003,100,100,0,50,2,0,2,'2026-01-01'),(4,1004,1000,1000,0,500,2,0,2,'2026-01-01')");
$db->query("INSERT INTO productos_venta (id,venta_id,producto_id,cantidad,precio,precio_factura) VALUES (1,1,1,4,50000,50000),(2,2,1,2,50000,50000),(3,3,2,3,50,50),(4,4,2,1,1000,1000)");
$db->query('INSERT INTO pago_cuotas (id,venta_id,cliente_id) VALUES (1,1,1),(3,3,3),(4,4,4)');
$db->query("INSERT INTO cuotas (id,numero_pago,monto,saldo,caja_id,pago_cuotas_id,cuota_inicial) VALUES (1,200001,80000,120000,1,1,1)");
$db->query('UPDATE ventas SET descuento=0,vendedor_id=1');
$ventaOriginal=Model\Venta::find(1); $cuotasOriginales=Model\Devolucion::consulta('SELECT * FROM cuotas');
$cierreOriginal=Model\Caja::find(1);
$token=bin2hex(random_bytes(32));
$dev=registrar(1,[elegir(1,1)],$token);
verificar(Model\Devolucion::saldo(Model\Venta::find(1))===70000.0,'Deuda 120000 menos devolución 50000 = 70000');
verificar(Model\Devolucion::consulta('SELECT * FROM cuotas')===$cuotasOriginales,'Los abonos quedan intactos');
verificar(Model\Venta::find(1)->total===$ventaOriginal->total && Model\Venta::find(1)->recaudo===$ventaOriginal->recaudo,'No se altera total ni recaudo');
verificar(Model\Caja::find(1)==$cierreOriginal,'No se altera el cierre histórico');
verificar((int)Model\Producto::find(1)->stock===11,'Reintegro de inventario');
verificar(registrar(1,[elegir(1,1)],$token)===$dev,'Reintento idempotente');
verificar((int)Model\Producto::find(1)->stock===11,'Reintento no duplica stock');
falla(fn()=>registrar(1,[elegir(1,4)]),'No exceder unidades restantes');
falla(fn()=>registrar(1,[elegir(1,1),elegir(1,1)]),'No duplicar líneas');
falla(fn()=>registrar(1,[elegir(2,1)]),'No devolver otra venta');
falla(fn()=>registrar(1,[elegir(1,-1)]),'No aceptar negativos');
$r=endpoint([Controllers\ApiFiados::class,'pagar'],['cliente_id'=>1,'monto'=>20000]);
verificar($r['type']==='success','Registrar abono después de una devolución');
verificar(Model\Devolucion::saldo(Model\Venta::find(1))===50000.0,'Abono reduce deuda ajustada');
verificar(Model\Venta::find(1)->fecha===$ventaOriginal->fecha,'Abonar no cambia fecha original');
verificar(endpoint([Controllers\ApiFiados::class,'pagar'],['cliente_id'=>1,'monto'=>50001])['type']==='error','No aceptar abono mayor que deuda ajustada');
registrar(1,[elegir(1,2,0)]);
$r=Model\Devolucion::resumen(1);
verificar((float)$r['deuda_reducida']===100000.0 && (float)$r['reembolsos']===50000.0,'Devolución mixta: deuda y reembolso');
verificar((int)Model\Producto::find(1)->stock===11,'Producto dañado no vuelve al stock');
verificar(Model\Devolucion::saldo(Model\Venta::find(1))===0.0,'Deuda cancelada');
verificar((int)Model\Venta::find(1)->estado===1,'Venta saldada');
verificar(Model\Devolucion::bloqueoVenta(1)!==null,'Bloqueo de venta devuelta');
verificar(endpoint([Controllers\ApiFiados::class,'eliminarPago'],['numero_pago'=>200002])['type']==='error','No borrar pago de venta con devolución');
registrar(2,[elegir(2,1)]);
verificar((float)Model\Devolucion::resumen(2)['reembolsos']===50000.0,'Venta pagada reembolsa todo lo devuelto');
// Tres devoluciones parciales de una factura con descuento global y centavos.
registrar(3,[elegir(3,1)]); registrar(3,[elegir(3,1)]); registrar(3,[elegir(3,1)]);
verificar((float)Model\Devolucion::resumen(3)['devoluciones_total']===100.0,'Las devoluciones parciales suman exactamente la factura');
falla(fn()=>registrar(3,[elegir(3,1)]),'No devolver una venta totalmente devuelta');
verificar(Model\Devolucion::bloqueoVenta(4)===null,'Venta abierta sin pagos permite editar');
$db->query('INSERT INTO cuotas (numero_pago,monto,saldo,caja_id,pago_cuotas_id) VALUES (300001,10,990,2,4)');
verificar(endpoint([Controllers\ApiVentas::class,'editar'],['id'=>4])['type']==='error','Guardar edición vuelve a verificar pagos');
verificar(endpoint([Controllers\ApiVentas::class,'eliminar'],['id'=>4])['type']==='error','Eliminar venta con abonos se bloquea');
$saldoCaja=Model\Devolucion::efectivoDisponible(Model\Caja::find(2));
verificar(endpoint([Controllers\ApiCajas::class,'cerrar'],['id'=>2])['type']==='success','Cierre con reembolsos');
verificar((float)Model\Caja::find(2)->efectivo_cierre===$saldoCaja,'Cierre descuenta reembolsos exactamente una vez');
verificar(Model\Caja::find(1)==$cierreOriginal,'Cierre anterior intacto al terminar');
falla(fn()=>registrar(2,[elegir(2,1)]),'Reembolso sin caja abierta se rechaza');
verificar((int)Model\Devolucion::detalleVenta(2)['lineas'][0]['disponible']===1,'Rechazo no consume unidades');
verificar(endpoint([Controllers\ApiCajas::class,'cerrar'],['id'=>2])['type']==='error','No recalcular cierre histórico');
// Rechazos por fondos e importes manipulados no deben consumir inventario.
$db->query('INSERT INTO cajas (id,estado,efectivo_apertura,numero_transacciones) VALUES (3,0,0,0)');
$antes=Model\Producto::find(1)->stock;
falla(fn()=>registrar(2,[elegir(2,1)]),'Reembolso sin fondos se rechaza');
verificar(Model\Producto::find(1)->stock===$antes,'Rechazo no altera inventario');
$db->query('UPDATE cajas SET efectivo_apertura=100000 WHERE id=3');
$items=[elegir(2,1)];
$resumen=Model\Devolucion::calcular(Model\Devolucion::detalleVenta(2),$items);
$resumen['reembolso']=1;
falla(fn()=>Model\Devolucion::registrar(2,$items,'Resumen cambiado',1,bin2hex(random_bytes(32)),$resumen),'No aceptar importes manipulados');
// Fallo después de insertar cabecera: toda la operación debe revertirse.
$db->query('ALTER TABLE devoluciones_detalle ADD fallo_prueba INT NOT NULL');
try { registrar(2,$items); throw new RuntimeException('Debió fallar el detalle'); }
catch (mysqli_sql_exception $e) { verificar($e->getCode()===1364,'Fallo de escritura esperado'); }
$db->query('ALTER TABLE devoluciones_detalle DROP COLUMN fallo_prueba');
verificar((float)Model\Devolucion::resumen(2)['reembolsos']===50000.0,'Rollback de cabecera');
verificar(Model\Producto::find(1)->stock===$antes,'Rollback conserva inventario');
registrar(2,$items);
verificar((float)Model\Devolucion::resumen(2)['reembolsos']===100000.0,'Reembolso total acumulado correcto');
$db->query("INSERT INTO clientes (id,nombre) VALUES (1,'Cliente 1'),(3,'Cliente 3'),(4,'Cliente 4')");
$clientes=endpoint([Controllers\ApiVentas::class,'clientesFiados'],[]);
verificar(array_map('intval',array_column($clientes,'id'))===[4],'Selector excluye deudas saldadas por devolución');
echo "OK: devolución parcial, abonos, reembolso, inventario, redondeo, duplicados, rollback, edición protegida y cierres.\n";
