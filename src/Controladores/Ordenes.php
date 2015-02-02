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


      //Crear array para poner el id del cliente de la operación en las órdenes de compra
      $ordenes_compra = $app["db"]->fetchAll("SELECT *
        FROM ordenes_compra op LEFT JOIN proveedores p
        ON op.id_u_proveedor = p.id_u_proveedor
        WHERE op.cliente_id='{$app["id_marca"]}' ORDER BY id_orden DESC");


      return $app['twig']->render('views/ordenes-compra.html',
      array(
        "titulo" => "Órdenes de compra",
        'btn_header' => array(
          'titulo' => "Crear orden de compra",
          'href' => $app['url_generator']->generate('crear_orden'),
          'icon' => ''
        ),
        'ordenes_compra' => $ordenes_compra
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
          $data["referencia_propuesta"] = $data["id_u_referencia"];
          $data["id_u_referencia"] = "";

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

    $orden_productos = $app["db"]->fetchAll("SELECT * FROM ordenes_productos WHERE id_u_orden='{$id_orden}'");

    $total_orden = 0;

    foreach($orden_productos as $producto){
      $total_orden += $producto["cantidad"] * $producto["precio"];
    }

    $app->register(new Formularios\OrdenForm());
    $form = $app['ordenForm']($orden_compra);

    $_error = "";
    if('POST' == $request->getMethod()){

      if($_POST["submit"]==1){
        $orden_producto = array(
          "id_u_producto" => $_POST["producto"],
          "cantidad" => $_POST["cantidad"],
          "precio" => $_POST["precio"]
        );
        $lleno = 0;
        foreach($orden_producto as $producto=>$key){
          if(empty($key)){
            $lleno += 1;
          }
        }
        if($lleno==0){
          $orden_producto["id_u_orden"] = $id_orden;
          $orden_producto["id_u_proveedor"] = $orden_compra["id_u_proveedor"];
          $orden_producto["total"] = "";
          $orden_producto["fecha_creacion"] = date("d/m/Y");
          $orden_producto["creador"] = "";
          $orden_producto["igi"] = "";
          $orden_producto["iva_aduanal"] = "";

          $sql = $app["sql"]("insert","ordenes_productos",$orden_producto);

          return $app->redirect($app['url_generator']->generate('orden_compra', array("id_orden"=> $id_orden)));

        }else{
          $_error = "Alguno de los campos está sin seleccionar o vacío";
        }
      }

      if($_POST["submit"]==2){

        $orden = array();
        for($i=0;$i<count($_POST["id_producto"]);$i++){
          $orden[$i] = array(
            "id_orden_producto" => $_POST["orden_producto"][$i],
            "id_u_producto" => $_POST["producto"][$i],
            "cantidad" => $_POST["cantidad"][$i],
            "precio" => $_POST["precio"][$i],
          );

          $app["sql"]("update","ordenes_productos",$orden[$i]);

        }

        return $app->redirect($app['url_generator']->generate('orden_compra', array("id_orden"=> $id_orden)));


      }

      if($_POST["submit"] !=1 && $_POST["submit"] !=2){
        $app["db"]->executeQuery("DELETE FROM ordenes_productos WHERE id_orden_producto='{$_POST["submit"]}'");
        return $app->redirect($app['url_generator']->generate('orden_compra', array("id_orden"=> $id_orden)));
      }

    }

    return $app["twig"]->render('views/orden-compra.html',array(
      'titulo' => "Orden de compra",
      'form' => $form->createView(),
      "productos" => $productos,
      "error" => $_error,
      "orden_productos" => $orden_productos,
      "total_orden" => $total_orden
    ));


  })->bind('orden_compra');

    return $controllers;
  }



}

?>
