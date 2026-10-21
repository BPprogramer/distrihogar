<?php

namespace MVC;

class Router
{
    public array $getRoutes = [];
    public array $postRoutes = [];

    public function get($url, $fn)
    {
        $this->getRoutes[$url] = $fn;
    }

    public function post($url, $fn)
    {
        $this->postRoutes[$url] = $fn;
    }

    public function comprobarRutas()
    {

        $url_actual = $_SERVER['PATH_INFO'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $fn = $this->getRoutes[$url_actual] ?? null;
        } else {
            $fn = $this->postRoutes[$url_actual] ?? null;
        }

        if ( $fn ) {
            // Serializar escrituras evita que un cierre, abono o edición se cruce
            // con una devolución entre la validación y el guardado.
            $db = \Model\ActiveRecord::getDB();
            $bloquear = $method === 'POST' && str_starts_with($url_actual, '/api/');
            $lock = null;
            if ($bloquear) {
                $lock = 'pos:' . substr(hash('sha256', $db->query('SELECT DATABASE() db')->fetch_assoc()['db']), 0, 40);
                $stmt = $db->prepare('SELECT GET_LOCK(?, 10) obtenido');
                $stmt->bind_param('s', $lock);
                $stmt->execute();
                if ((int)$stmt->get_result()->fetch_assoc()['obtenido'] !== 1) {
                    http_response_code(409);
                    echo json_encode(['type'=>'error','msg'=>'Hay otra operación en curso. Intente nuevamente.']);
                    return;
                }
            }
            try { call_user_func($fn, $this); }
            finally {
                if ($lock !== null) {
                    $stmt = $db->prepare('SELECT RELEASE_LOCK(?)');
                    $stmt->bind_param('s', $lock);
                    $stmt->execute();
                }
            }
        } else {
            echo "Página No Encontrada o Ruta no válida";
        }
    }

    public function render($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value; 
        }

        ob_start(); 

        include_once __DIR__ . "/views/$view.php";

        $contenido = ob_get_clean(); // Limpia el Buffer

        $url_actual = $_SERVER['PATH_INFO'] ?? '/';
        if(str_contains($url_actual, '/login')){
            include_once __DIR__ . '/views/layout.php';
        }else{
            include_once __DIR__ . '/views/admin-layout.php';
        }

    }
}



/* 
+3217792811

    Prveedores

    id
    nombre
    telefono
    direcion

 

*/

/* 

    Productos

    id
    codigo
    nombre
    stock
    stock_minimo
    precio_compra
    precio_venta
    ventas
    id_categoria
    id_provedor
     
*/
    
    
    
  
    


