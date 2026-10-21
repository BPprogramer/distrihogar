<?php
// Pruebas en tablas TEMPORARY: no alteran los productos ni ventas reales.
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../includes')->safeLoad();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base configurada para ejecutar las pruebas temporales.\n");
    exit(1);
}
Model\ActiveRecord::setDB($db);
function comprobar($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
}
$db->query('CREATE TEMPORARY TABLE productos (id INT PRIMARY KEY, nombre VARCHAR(100), codigo VARCHAR(30), stock INT DEFAULT 5, stock_minimo INT DEFAULT 1, precio_compra DECIMAL(10,2) DEFAULT 1, precio_venta DECIMAL(10,2) DEFAULT 2, porcentaje_venta INT DEFAULT 100, ventas INT DEFAULT 0, categoria_id INT, proveedor_id INT)');
$db->query('CREATE TEMPORARY TABLE productos_venta (id INT PRIMARY KEY, producto_id INT, venta_id INT, cantidad INT)');
$db->query("INSERT INTO productos (id, nombre) VALUES (1, 'ACTIVO SIN VENTAS'), (2, 'CON VENTAS'), (3, 'INACTIVO'), (4, 'INACTIVO SIN VENTAS')");
$db->query(file_get_contents(__DIR__ . '/../migrations/2026-10-21_productos_activos.sql'));
comprobar(count(Model\Producto::whereArray(['activo' => 1])) === 4, 'La migración debe activar todos los existentes');
comprobar((new Model\Producto())->activo === 1, 'Nuevos activos por defecto');
$db->query('UPDATE productos SET activo = 0 WHERE id IN (3, 4)');
$db->query('INSERT INTO productos_venta VALUES (1, 2, 10, 2), (2, 3, 10, 2)');
comprobar(count(Model\Producto::whereArray(['activo' => 0])) === 2, 'Filtro inactivos');
ob_start();
Controllers\ApiVentas::productos();
$disponibles = json_decode(ob_get_clean());
comprobar(array_column($disponibles, 'id') == [2, 1], 'Selector de ventas solo activos');
$_GET = [];
ob_start();
Controllers\ApiProductos::productos();
$tabla = json_decode(ob_get_clean(), true)['data'];
comprobar(count($tabla) === 2 && $tabla[0][7] === 'Activo', 'Tabla activa por defecto y columna estado');
$_GET = ['activo' => '0'];
ob_start();
Controllers\ApiProductos::productos();
$tabla = json_decode(ob_get_clean(), true)['data'];
comprobar(count($tabla) === 2 && $tabla[0][7] === 'Inactivo', 'Tabla de inactivos');
$linea = fn($id, $cantidad) => (object) compact('id', 'cantidad');
$anteriores = [(object) ['producto_id' => 3, 'cantidad' => 2]];
comprobar(Model\Producto::validarProductosVenta([$linea(1, 1)]) === null, 'Venta nueva activa');
comprobar(Model\Producto::validarProductosVenta([$linea(3, 1)]) !== null, 'Rechazar inactivo nuevo');
comprobar(Model\Producto::validarProductosVenta([$linea(3, 2)], $anteriores) === null, 'Conservar historial');
comprobar(Model\Producto::validarProductosVenta([$linea(3, 1)], $anteriores) === null, 'Reducir cantidad histórica');
comprobar(Model\Producto::validarProductosVenta([$linea(3, 3)], $anteriores) !== null, 'Impedir aumentar inactivo');
comprobar(Model\Producto::validarProductosVenta([$linea(3, 2), $linea(3, 1)], $anteriores) !== null, 'No evadir límite con duplicados');
comprobar(Model\Producto::validarProductosVenta([$linea(999, 1)]) !== null, 'Producto inexistente');
comprobar(Model\Producto::find(1)->eliminar()['status'], 'Eliminar activo sin ventas');
comprobar(Model\Producto::find(4)->eliminar()['status'], 'Eliminar inactivo sin ventas');
comprobar(Model\Producto::find(2)->eliminar()['ventas_asociadas'], 'Proteger activo vendido');
comprobar(Model\Producto::find(3)->eliminar()['ventas_asociadas'], 'Proteger inactivo vendido');
$producto = Model\Producto::find(3);
$producto->activo = 1;
comprobar($producto->guardar() && (int) Model\Producto::find(3)->activo === 1, 'Reactivar producto');
echo "OK: migración, filtros, ventas, historial, eliminación y reactivación.\n";
