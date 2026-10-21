<?php
require __DIR__ . '/../models/ActiveRecord.php';
require __DIR__ . '/../models/Producto.php';
class ProductoPrueba extends Model\Producto {
    public static function find($id) {
        if (!in_array($id, [1, 2], true)) return null;
        return new self(['id' => $id, 'nombre' => 'Producto', 'activo' => $id === 1 ? 1 : 0]);
    }
}
function verificar($valor, $mensaje) {
    if (!$valor) throw new RuntimeException($mensaje);
}
$linea = fn($id, $cantidad) => (object) compact('id', 'cantidad');
$historial = [(object) ['producto_id' => 2, 'cantidad' => 3]];
verificar((new Model\Producto())->activo === 1, 'Activo por defecto');
verificar(ProductoPrueba::validarProductosVenta([$linea(1, 2)]) === null, 'Permitir activo');
verificar(ProductoPrueba::validarProductosVenta([$linea(2, 1)]) !== null, 'Rechazar inactivo');
verificar(ProductoPrueba::validarProductosVenta([$linea(2, 3)], $historial) === null, 'Conservar inactivo histórico');
verificar(ProductoPrueba::validarProductosVenta([$linea(2, 2)], $historial) === null, 'Reducir histórico');
verificar(ProductoPrueba::validarProductosVenta([$linea(2, 4)], $historial) !== null, 'No aumentar histórico');
verificar(ProductoPrueba::validarProductosVenta([$linea(2, 2), $linea(2, 2)], $historial) !== null, 'Control de duplicados');
foreach ([null, [], [$linea(999, 1)], [$linea(1, -1)], [$linea('1 OR 1', 2)]] as $invalido) {
    verificar(ProductoPrueba::validarProductosVenta($invalido) !== null, 'Rechazar datos inválidos');
}
echo "OK: estado predeterminado y validaciones de venta nueva/edición.\n";
