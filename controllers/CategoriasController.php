<?php

namespace Controllers;


use MVC\Router;

class CategoriasController {

    public static function index(Router $router){
     
        if(!is_auth()){
            header('Location:/login');
        }
        $router->render('categorias/index', [
            'titulo' => 'Categorías',
            'nombre'=>$_SESSION['nombre']
        
        ]);
    }

}