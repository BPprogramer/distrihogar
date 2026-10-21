<?php

namespace Model;

class  Producto extends ActiveRecord {
    protected static $tabla = 'productos';
    protected static $columnasDB = ['id', 'nombre','codigo', 'stock', 'stock_minimo', 'precio_compra', 'precio_venta', 'porcentaje_venta', 'ventas', 'categoria_id', 'proveedor_id', 'activo'];

    public $id;
    public $nombre;
    public $codigo;
    public $stock;
    public $stock_minimo;
    public $precio_compra;
    public $precio_venta;
    public $porcentaje_venta;
    public $ventas;
    public $categoria_id;
    public $proveedor_id;
    public $activo;

 
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->codigo = $args['codigo'] ?? null;
        $this->stock = $args['stock'] ?? '';
        $this->stock_minimo = $args['stock_minimo'] ?? '';
        $this->precio_compra = $args['precio_compra'] ?? '';
        $this->precio_venta = $args['precio_venta'] ?? '';
        $this->porcentaje_venta = $args['porcentaje_venta'] ?? '';
        $this->ventas = $args['ventas'] ?? 0;
        $this->categoria_id = $args['categoria_id'] ?? null;
        $this->proveedor_id = $args['proveedor_id'] ?? null;
        $this->activo = $args['activo'] ?? 1;

      
    }
    public function eliminar() {
        $id = (int) $this->id;
        try {
            $resultado = self::$db->query("DELETE FROM productos WHERE id = $id AND NOT EXISTS (SELECT 1 FROM productos_venta WHERE producto_id = $id)");
            if ($resultado && self::$db->affected_rows === 1) return ['status' => true];
            return ['status' => false, 'ventas_asociadas' => (bool) ProductosVenta::where('producto_id', $id)];
        } catch (\mysqli_sql_exception $e) {
            return ['status' => false, 'ventas_asociadas' => (bool) ProductosVenta::where('producto_id', $id)];
        }
    }

    // Los inactivos ya vendidos se pueden conservar o reducir, nunca agregar o aumentar.
    public static function validarProductosVenta($productos, $anteriores = []) {
        if (!is_array($productos) || !$productos) return 'Debe agregar productos a la venta.';
        $cantidadesAnteriores = [];
        foreach ($anteriores as $anterior) {
            $id = (int) $anterior->producto_id;
            $cantidadesAnteriores[$id] = ($cantidadesAnteriores[$id] ?? 0) + (float) $anterior->cantidad;
        }
        $cantidades = [];
        foreach ($productos as $producto) {
            $id = filter_var($producto->id ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1 || !is_numeric($producto->cantidad ?? null) || (float) $producto->cantidad <= 0) {
                return 'Hay un producto o cantidad inválida en la venta.';
            }
            $cantidades[$id] = ($cantidades[$id] ?? 0) + (float) $producto->cantidad;
        }
        foreach ($cantidades as $id => $cantidad) {
            $producto = static::find($id);
            if (!$producto) return 'Uno de los productos ya no existe. Revise la venta.';
            if ((int) $producto->activo !== 1 && $cantidad > ($cantidadesAnteriores[$id] ?? 0)) {
                return 'El producto "' . $producto->nombre . '" está inactivo. No puede agregarlo ni aumentar su cantidad.';
            }
        }
        return null;
    }

    public function formatearDatosFloat(){
      
        $this->precio_compra = floatval(str_replace(',','',$this->precio_compra));
        $this->precio_venta = floatval(str_replace(',','',$this->precio_venta));
     
     
    }


}
