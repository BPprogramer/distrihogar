<?php
namespace Model;

use DomainException;

class Devolucion extends ActiveRecord
{
    public static function consulta($sql, $params = []) {
        $stmt = self::$db->prepare($sql);
        if (!$stmt) throw new \RuntimeException('No se pudo preparar la operación.');
        if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        if (!$stmt->execute()) throw new \RuntimeException('No se pudo guardar la operación.');
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : self::$db->insert_id;
    }

    public static function resumen($id) {
        return self::consulta('SELECT COALESCE(SUM(total),0) devoluciones_total, COALESCE(SUM(deuda_reducida),0) deuda_reducida, COALESCE(SUM(reembolso),0) reembolsos FROM devoluciones WHERE venta_id = ?', [$id])[0];
    }

    public static function efectivoDisponible($caja) {
        $r = self::consulta('SELECT
            (SELECT COALESCE(SUM(recaudo),0) FROM ventas WHERE caja_id=? AND metodo_pago=1)
            + (SELECT COALESCE(SUM(monto),0) FROM cuotas WHERE caja_id=?)
            + (SELECT COALESCE(SUM(ingreso),0) FROM ingresos WHERE caja_id=? AND estado<>0)
            - (SELECT COALESCE(SUM(egreso),0) FROM egresos WHERE caja_id=? AND estado<>0)
            - (SELECT COALESCE(SUM(reembolso),0) FROM devoluciones WHERE caja_id=?) movimientos', array_fill(0,5,$caja->id))[0];
        return round((float)$caja->efectivo_apertura + (float)$r['movimientos'], 2);
    }

    public static function saldo($venta, $resumen = null) {
        $r = $resumen ?? self::resumen($venta->id);
        $total = (int)$venta->metodo_pago === 3 ? $venta->total_factura : $venta->total;
        return max(0.0, round((float)$total - (float)$venta->recaudo - (float)$r['deuda_reducida'], 2));
    }

    public static function informar($venta, $id = null) {
        $r = self::resumen($id ?? $venta->id);
        foreach ($r as $key => $value) $venta->$key = (float)$value;
        $venta->saldo_pendiente = self::saldo($venta, $r);
        return $venta;
    }

    public static function bloqueoVenta($id) {
        $v = Venta::find((int)$id);
        if (!$v) return 'No se encontró la venta.';
        $c = Caja::find((int)$v->caja_id);
        if (!$c || (int)$c->estado !== 0) return 'La caja de esta venta está cerrada. No se puede editar ni eliminar.';
        if (self::consulta('SELECT id FROM devoluciones WHERE venta_id = ? LIMIT 1', [$id])) return 'Esta venta tiene devoluciones. No se puede editar ni eliminar.';
        if (self::consulta('SELECT c.id FROM cuotas c JOIN pago_cuotas p ON p.id=c.pago_cuotas_id WHERE p.venta_id=? LIMIT 1', [$id])) return 'Tiene pagos asociados por lo que no puede editar ni eliminar la venta.';
        return null;
    }

    public static function detalleVenta($id) {
        $v = Venta::find((int)$id);
        if (!$v) throw new DomainException('No se encontró la venta.');
        if (!in_array((int)$v->metodo_pago, [1,2], true)) throw new DomainException('Esta sección admite ventas de contado y fiadas. Las ventas de Mercado Libre se gestionan por su canal.');
        self::informar($v);
        $lineas = self::consulta('SELECT pv.*, p.nombre, COALESCE(d.devueltas,0) devueltas FROM productos_venta pv JOIN productos p ON p.id=pv.producto_id LEFT JOIN (SELECT linea_id,SUM(cantidad) devueltas FROM devoluciones_detalle GROUP BY linea_id) d ON d.linea_id=pv.id WHERE pv.venta_id=? ORDER BY pv.id', [$id]);
        // Distribución acumulada en centavos: el total de todas las líneas coincide
        // exactamente con la factura, incluyendo descuentos globales y redondeos.
        $base = array_sum(array_map(fn($l) => (float)$l['precio_factura'] * (int)$l['cantidad'], $lineas));
        if ($base <= 0) throw new DomainException('La venta no tiene importes válidos para calcular una devolución.');
        $acumulado = 0; $asignado = 0;
        foreach ($lineas as &$l) {
            if ((int)$l['cantidad'] <= 0 || (float)$l['precio_factura'] < 0) throw new DomainException('Hay cantidades o precios históricos inválidos. Revise la venta.');
            $acumulado += (float)$l['precio_factura'] * (int)$l['cantidad'];
            $hasta = (int)round(self::centavos($v->total_factura) * $acumulado / $base);
            $l['valor_centavos'] = $hasta - $asignado;
            $asignado = $hasta;
            $l['disponible'] = (int)$l['cantidad'] - (int)$l['devueltas'];
        }
        unset($l);
        return ['venta' => $v, 'lineas' => $lineas, 'historial' => self::consulta('SELECT * FROM devoluciones WHERE venta_id=? ORDER BY id DESC', [$id])];
    }

    public static function centavos($valor) { return (int)round((float)$valor * 100); }

    public static function calcular($detalle, $seleccion) {
        if (!is_array($seleccion) || !$seleccion) throw new DomainException('Seleccione al menos un producto.');
        $lineas = array_column($detalle['lineas'], null, 'id');
        $vistos = []; $items = []; $total = 0;
        foreach ($seleccion as $s) {
            $id = filter_var($s['linea_id'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($s['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $reintegrar = $s['reintegrar'] ?? null;
            if (!$id || isset($vistos[$id]) || !isset($lineas[$id])) throw new DomainException('Hay productos repetidos o ajenos a la venta.');
            $l = $lineas[$id]; $vistos[$id] = true;
            if (!$cantidad || $cantidad < 1 || $cantidad > $l['disponible']) throw new DomainException('Revise las cantidades: no puede devolver más de lo disponible.');
            if (!in_array($reintegrar, [0,1], true)) throw new DomainException('Indique el destino de cada producto.');
            $valor = (int)round($l['valor_centavos'] * ((int)$l['devueltas'] + $cantidad) / (int)$l['cantidad']) - (int)round($l['valor_centavos'] * (int)$l['devueltas'] / (int)$l['cantidad']);
            $items[] = ['linea_id'=>$id, 'producto_id'=>$l['producto_id'], 'nombre'=>$l['nombre'], 'cantidad'=>$cantidad, 'reintegrar'=>$reintegrar, 'valor'=>$valor / 100];
            $total += $valor;
        }
        $saldo = self::centavos($detalle['venta']->saldo_pendiente);
        $reduccion = min($saldo, $total);
        return ['total'=>$total/100, 'deuda_reducida'=>$reduccion/100, 'reembolso'=>($total-$reduccion)/100, 'saldo_anterior'=>$saldo/100, 'saldo_posterior'=>($saldo-$reduccion)/100, 'items'=>$items];
    }

    public static function registrar($id, $seleccion, $motivo, $usuario, $solicitud, $esperado) {
        if (strlen($solicitud) !== 64 || !ctype_xdigit($solicitud)) throw new DomainException('Solicitud inválida. Vuelva a abrir el formulario.');
        $previa = self::consulta('SELECT id,venta_id,usuario_id FROM devoluciones WHERE solicitud=?', [$solicitud]);
        if ($previa) {
            if ((int)$previa[0]['venta_id'] !== $id || (int)$previa[0]['usuario_id'] !== $usuario) throw new DomainException('Solicitud inválida.');
            return (int)$previa[0]['id'];
        }
        $motivo = trim($motivo);
        if (strlen($motivo) < 3 || strlen($motivo) > 500) throw new DomainException('Escriba un motivo entre 3 y 500 caracteres.');
        self::$db->begin_transaction();
        try {
            $detalle = self::detalleVenta($id);
            $v = $detalle['venta'];
            if (abs((float)$v->total - (float)$v->total_factura) > 0.01) throw new DomainException('Los totales históricos de esta venta no coinciden. Revise la venta antes de devolver.');
            $r = self::calcular($detalle, $seleccion);
            foreach (['total','deuda_reducida','reembolso','saldo_posterior'] as $key) {
                if (!isset($esperado[$key]) || self::centavos($esperado[$key]) !== self::centavos($r[$key])) throw new DomainException('La venta cambió. Vuelva a revisar el resumen antes de confirmar.');
            }
            $caja = null;
            if ($r['reembolso'] > 0) {
                $caja = Caja::get(1);
                if (!$caja || (int)$caja->estado !== 0) throw new DomainException('Abra una caja para entregar el reembolso en efectivo.');
                if (self::centavos(self::efectivoDisponible($caja)) < self::centavos($r['reembolso'])) throw new DomainException('La caja actual no tiene efectivo suficiente para este reembolso.');
                $pagadoNeto = self::centavos($v->recaudo) - self::centavos($v->reembolsos);
                if (self::centavos($r['reembolso']) > $pagadoNeto) throw new DomainException('El reembolso supera los pagos recibidos. Revise la venta.');
            }
            $fecha = (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s');
            $dev = self::consulta('INSERT INTO devoluciones (venta_id,usuario_id,caja_id,fecha,motivo,total,deuda_reducida,reembolso,saldo_anterior,saldo_posterior,solicitud) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$id,$usuario,$caja ? $caja->id : null,$fecha,$motivo,$r['total'],$r['deuda_reducida'],$r['reembolso'],$r['saldo_anterior'],$r['saldo_posterior'],$solicitud]);
            foreach ($r['items'] as $item) {
                self::consulta('INSERT INTO devoluciones_detalle (devolucion_id,linea_id,producto_id,nombre,cantidad,reintegrar,valor) VALUES (?,?,?,?,?,?,?)', [$dev,$item['linea_id'],$item['producto_id'],$item['nombre'],$item['cantidad'],$item['reintegrar'],$item['valor']]);
                self::consulta('UPDATE productos SET stock=stock+?, ventas=GREATEST(0,ventas-?) WHERE id=?', [$item['reintegrar'] ? $item['cantidad'] : 0,$item['cantidad'],$item['producto_id']]);
            }
            self::consulta('UPDATE ventas SET estado=? WHERE id=?', [$r['saldo_posterior'] > 0 ? 0 : 1,$id]);
            if ($caja) self::consulta('UPDATE cajas SET numero_transacciones=numero_transacciones+1 WHERE id=?', [$caja->id]);
            self::$db->commit();
            return (int)$dev;
        } catch (\Throwable $e) { self::$db->rollback(); throw $e; }
    }
}
