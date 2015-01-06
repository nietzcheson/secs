<?php
namespace Controladores;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\FormServiceProvider;
use Formularios;
use Servicios;
use Librerias;
use DOMPDF;

class Ordenes implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = $app['controllers_factory'];

    $controllers->match('/', function (Request $request) use ($app) {
      return $app['twig']->render('views/ordenes-compra.html',
      array(
        "titulo" => "Órdenes de compra",
        'btn_header' => array(
          'titulo' => "Crear proveedor",
          'href' => $app['url_generator']->generate('crear_orden'),
          'icon' => ''
        )
      )
    );
    })->bind('ordenes');

    $controllers->match('/crear-orden', function (Request $request) use ($app) {

      $app->register(new Formularios\OrdenForm());
      $form = $app['ordenForm']();

      $_mensaje = "";
      $_error = "";
      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();
          $data["id_u_orden"] = "";
          $data["fecha_creacion"] = date('y/m/Y');
          $data["creador"] = "";
          $data["fecha_actualizacion"] = "";
          $data["actualizador"] = "";
          $data["cliente_id"] = $app["id_marca"];

          $sql = $app["sql"]("insert","ordenes_compra",$data);

          $last_orden = $app["db"]->fetchAssoc("SELECT * FROM ordenes_compra WHERE cliente_id='{$app["id_marca"]}' ORDER BY id_orden DESC LIMIT 1");

          return $app->redirect($app['url_generator']->generate('orden_compra', array("id_orden"=> $last_orden["id_orden"])));

        }
      }

      $proveedores = $app["db"]->fetchAll("SELECT * FROM proveedores");

      return $app['twig']->render('views/crear-orden.html',
      array(
        "titulo" => "Crear orden",
        "proveedores" => $proveedores,
        'form' => $form->createView(),
      )
    );

  })->bind('crear_orden');

  $controllers->match('/orden-compra/{id_orden}', function ($id_orden, Request $request) use ($app) {

    $orden_compra = $app["db"]->fetchAssoc("SELECT * FROM ordenes_compra WHERE id_orden='{$id_orden}'");


    $productos = $app["db"]->fetchAll("SELECT * FROM productos WHERE id_u_proveedor='{$orden_compra["id_u_proveedor"]}'");

    $app->register(new Formularios\OrdenForm());
    $form = $app['ordenForm']($orden_compra);

    if('POST' == $request->getMethod()){

      if($_POST["submit"]==1){

        echo $_POST["cantidad"];
        exit();

      }
    }


    return $app["twig"]->render('views/orden-compra.html',array(
      'titulo' => "Orden de compra",
      'form' => $form->createView(),
      "productos" => $productos
    ));


  })->bind('orden_compra');

    return $controllers;
  }



}

?>
