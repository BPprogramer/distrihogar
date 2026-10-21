<?php
namespace Controllers;

use Model\Devolucion;
use MVC\Router;

class DevolucionesController
{
    public static function index(Router $router) {
        if (!is_auth()) { header('Location:/login'); return; }
        $_SESSION['csrf_devoluciones'] ??= bin2hex(random_bytes(32));
        $router->render('devoluciones/index', ['titulo'=>'Devoluciones', 'nombre'=>$_SESSION['nombre'], 'csrf'=>$_SESSION['csrf_devoluciones']]);
    }

    private static function responder($accion) {
        header('Content-Type: application/json; charset=utf-8');
        if (!is_auth()) { http_response_code(401); echo json_encode(['type'=>'error','msg'=>'Inicie sesión para continuar.']); return; }
        try { echo json_encode($accion(), JSON_UNESCAPED_UNICODE); }
        catch (\DomainException $e) { echo json_encode(['type'=>'error','msg'=>$e->getMessage()]); }
        catch (\Throwable $e) { error_log('Devoluciones: '.$e->getMessage()); http_response_code(500); echo json_encode(['type'=>'error','msg'=>'No se pudo completar la operación. Recargue y vuelva a intentar.']); }
    }

    public static function listar() {
        self::responder(fn() => ['data'=>Devolucion::consulta('SELECT d.*, v.codigo, v.nombre_cliente, u.nombre responsable FROM devoluciones d JOIN ventas v ON v.id=d.venta_id LEFT JOIN usuarios u ON u.id=d.usuario_id ORDER BY d.id DESC')]);
    }

    public static function buscar() {
        self::responder(function () {
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2) return ['ventas'=>[]];
            return ['ventas'=>Devolucion::consulta('SELECT id,codigo,nombre_cliente,fecha,total_factura FROM ventas WHERE metodo_pago IN (1,2) AND (CAST(codigo AS CHAR) LIKE ? OR nombre_cliente LIKE ? OR cedula_cliente LIKE ?) ORDER BY id DESC LIMIT 30', array_fill(0,3,'%'.$q.'%'))];
        });
    }

    public static function venta() {
        self::responder(fn() => Devolucion::detalleVenta((int)($_GET['id'] ?? 0)));
    }

    public static function detalle() {
        self::responder(function () {
            $id = (int)($_GET['id'] ?? 0);
            $r = Devolucion::consulta('SELECT d.*,v.codigo,v.nombre_cliente,u.nombre responsable FROM devoluciones d JOIN ventas v ON v.id=d.venta_id LEFT JOIN usuarios u ON u.id=d.usuario_id WHERE d.id=?', [$id]);
            if (!$r) throw new \DomainException('No se encontró la devolución.');
            return ['devolucion'=>$r[0], 'items'=>Devolucion::consulta('SELECT * FROM devoluciones_detalle WHERE devolucion_id=?', [$id])];
        });
    }

    public static function guardar() {
        self::responder(function () {
            if (!hash_equals($_SESSION['csrf_devoluciones'] ?? '', $_POST['csrf'] ?? '') || empty($_SESSION['csrf_devoluciones'])) throw new \DomainException('La sesión cambió. Recargue la página.');
            $id = Devolucion::registrar((int)($_POST['venta_id'] ?? 0), json_decode($_POST['items'] ?? '', true), $_POST['motivo'] ?? '', (int)$_SESSION['id'], $_POST['solicitud'] ?? '', json_decode($_POST['resumen'] ?? '', true) ?? []);
            return ['type'=>'success', 'msg'=>'Devolución registrada correctamente.', 'id'=>$id];
        });
    }
}
