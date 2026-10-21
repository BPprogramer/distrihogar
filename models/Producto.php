<?php

namespace Model;

class  Producto extends ActiveRecord {
    protected static $tabla = 'productos';
    protected static $columnasDB = ['id', 'nombre','codigo', 'stock', 'stock_minimo', 'precio_compra', 'precio_venta', 'porcentaje_venta', 'ventas', 'categoria_id', 'proveedor_id'];

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

      
    }
    public function formatearDatosFloat(){
      
        $this->precio_compra = floatval(str_replace(',','',$this->precio_compra));
        $this->precio_venta = floatval(str_replace(',','',$this->precio_venta));
     
     
    }


}