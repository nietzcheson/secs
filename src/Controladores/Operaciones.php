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

class Operaciones implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = $app['controllers_factory'];

    $controllers->match('/', function (Request $request) use ($app) {

      $operaciones = $app["db"]->fetchAll(
      "SELECT *
        FROM referencias r LEFT JOIN empresas em
        ON r.id_u_empresa = em.id_u_empresa
        LEFT JOIN monedas mon
        ON r.moneda = mon.id_moneda
        LEFT JOIN usuarios u
        ON r.co = u.id
        LEFT JOIN estatus st
        ON r.status = st.id
        WHERE r.cliente = '{$app["id_marca"]}'
        ORDER BY id_referencia DESC
      ");

      for($i=0;$i<count($operaciones);$i++){

        $cotizaciones = $app["db"]->fetchAll("SELECT id_u_referencia FROM cotizaciones WHERE id_u_referencia = '{$operaciones[$i]["id_u_referencia"]} '");
        $ordenes = $app["db"]->fetchAll("SELECT * FROM ordenes_compra WHERE id_u_referencia= '{$operaciones[$i]["id_u_referencia"]}'");

        $operaciones[$i]["cotizaciones"] = count($cotizaciones);
        $operaciones[$i]["ordenes"] = count($ordenes);
      }

      return $app['twig']->render('views/operaciones.html',
        array(
          'titulo' => "Operaciones",
          'operaciones' => $operaciones,

        )
      );

    })
    ->bind('operaciones')
    ;

    $controllers->match('/operacion/{operacion}', function ($operacion, Request $request) use ($app) {

      $_operacion = $app["db"]->fetchAssoc("SELECT *
        FROM referencias r LEFT JOIN empresas em
        ON r.id_u_empresa = em.id_u_empresa
        LEFT JOIN prospectos pr
        ON r.contacto = pr.id_u_prospecto
        LEFT JOIN estatus est
        ON r.status = est.id
        LEFT JOIN monedas mon
        ON r.moneda = mon.id_moneda
        WHERE id_u_referencia = '{$operacion}'
      ");

      // $usuarios = $app["db"]->fetchAll("SELECT * FROM usuarios");
      //
      // foreach($usuarios as $usuario){
      //   if($_operacion["co"]==$usuario["id"]){
      //     $_operacion["co"] = $usuario["nombre"]
      //   }
      // }
      //
      // echo "<pre>";print_r($_operacion);
      // exit();

      return $app['twig']->render('views/operacion.html',
      array(
        'titulo' => "Operaciones",
        'id_operacion' => $operacion,
        'operacion' => $_operacion
        //'operaciones' => $operaciones,

      )
    );

  })
  ->bind('operacion')
  ;

  $controllers->match('/operacion/{operacion}/ordenes/', function ($operacion, Request $request) use ($app) {

    $ordenes_compra = $app["db"]->fetchAll("SELECT *
      FROM ordenes_compra
      WHERE id_u_referencia = '{$operacion}'
      ");


      return $app['twig']->render('views/ordenes-compra.html',
      array(
        'titulo' => "Operaciones",
        'id_operacion' => $operacion,
        'ordenes_compra' => $ordenes_compra
        //'operaciones' => $operaciones,

      )
    );

  })
  ->bind('ordenes_compra')
  ;

  $controllers->match('/operacion/{operacion}/orden/{orden}', function ($operacion, $orden, Request $request) use ($app) {

    $ordenes_compra = $app["db"]->fetchAll("SELECT *
      FROM ordenes_compra
      WHERE id_u_referencia = '{$operacion}'
      ");

    $orden_compra = $app["db"]->fetchAll(
      "SELECT *
        FROM ordenes_productos op LEFT JOIN productos pr
        ON op.id_u_producto = pr.id_u_producto
        WHERE id_u_orden = '{$orden}'
      "
      );

      //exit($total_orden);

      return $app['twig']->render('views/orden-compra.html',
      array(
        'titulo' => "Operaciones",
        'id_operacion' => $operacion,
        'id_orden' => $orden,
        'ordenes_compra' => $ordenes_compra,
        'orden_compra' => $orden_compra,
      )
    );

  })
  ->bind('orden_compra')
  ;

  $controllers->match('/cotizaciones/{operacion}', function ($operacion, Request $request) use ($app) {

    $cotizaciones = $app["db"]->fetchAll("SELECT *
      FROM cotizaciones
      WHERE id_u_referencia = '{$operacion}'
      ");

      return $app['twig']->render('views/cotizaciones.html',
      array(
        'titulo' => "Operaciones",
        'id_operacion' => $operacion,
        'cotizaciones' => $cotizaciones
        //'operaciones' => $operaciones,

      )
    );

  })
  ->bind('cotizaciones')
  ;

  $controllers->match('/operacion/{operacion}/cotizacion/{cotizacion}', function ($operacion, $cotizacion, Request $request) use ($app) {

    $cotizaciones = $app["db"]->fetchAll("SELECT *
      FROM cotizaciones
      WHERE id_u_referencia = '{$operacion}'
      ");

      $app->register(new Servicios\Cotizacion());
      $_cotizacion = $app['cotizacion']($cotizacion);



    return $app['twig']->render('views/cotizacion.html',
    array(
      'titulo' => "Operaciones",
      'id_operacion' => $operacion,
      'cotizaciones' => $cotizaciones,
      'id_cotizacion' => $cotizacion,
      "cotizacion" => $_cotizacion,
      'imprimir_pdf' => true,
    )
  );

})
->bind('cotizacion')
;

    $controllers->match('/crear-factura/{operacion}/{cotizacion}', function($operacion, $cotizacion, Request $request) use($app){

      $app->register(new Servicios\Cotizacion());
      $_cotizacion = $app['cotizacion']($cotizacion);


      $render = $app['twig']->render('views/pdf.html', array(
        "cotizacion" => $_cotizacion,
      ));

      //return $render;

      $dompdf = new DOMPDF();

      $codigo = utf8_decode($render);

      $dompdf = new DOMPDF();
      $dompdf->load_html($render);
      ini_set("memory_limit","64M");
      $dompdf->render();
      $dompdf->stream($cotizacion.".pdf");



      //$pdf = new pdf\PDF();

      return "Creando el pdf";
    })->bind('crear_factura');

    $controllers->match('/operacion/{operacion}/cotizacion/{cotizacion}/cxc', function($operacion, $cotizacion, Request $request) use($app){

      $cotizaciones = $app["db"]->fetchAll("SELECT *
        FROM cotizaciones
        WHERE id_u_referencia = '{$operacion}'
        ");

      $app->register(new Servicios\Cotizacion());
      $_cotizacion = $app['cotizacion']($cotizacion);

      return $app['twig']->render('views/cxc.html', array(
        'id_operacion' => $operacion,
        'cotizaciones' => $cotizaciones,
        'id_cotizacion' => $cotizacion,
        'cotizacion' => $_cotizacion
        ));
    })->bind("cxc");


    return $controllers;
  }



}

?>
