<?php

namespace Servicios;


use Silex\Application;
use Silex\ServiceProviderInterface;


class Cotizacion implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['cotizacion'] = $app->protect(function ($id_cotizacion) use ($app) {


      $cotizacion = $app["db"]->fetchAssoc("SELECT *
        FROM cotizaciones cot LEFT JOIN referencias ref
        ON cot.id_u_referencia = ref.id_u_referencia
        WHERE id_u_cotizacion='{$id_cotizacion}'");


      $logo_empresa = $app["db"]->fetchAssoc("SELECT logo FROM empresas WHERE id_u_empresa='{$cotizacion["id_u_empresa"]}'");
      $cotizacion["logo_empresa"] = $logo_empresa["logo"];

      $colores = array(
        "si000"=>"#7f7f7f",
        "co001"=>"#217c9e",
        "im002"=>"#f19331",
        "ib003"=>"#af1727",
        "pr004"=>"#505050"
      );

      $cotizacion["color_caption"] = $colores[$cotizacion["id_u_empresa"]];

      $cliente = $app["db"]->fetchAssoc("SELECT nombre_marca FROM marcas WHERE id_u_marca='{$cotizacion["cliente"]}'");
      $cotizacion["cliente"] = $cliente["nombre_marca"];

      $contacto = $app["db"]->fetchAssoc("SELECT nombre_prospecto, apellido_prospecto FROM prospectos WHERE id_u_prospecto='{$cotizacion["contacto"]}'");
      $cotizacion["contacto"] = $contacto["nombre_prospecto"]." ".$contacto["apellido_prospecto"];

      $embalaje = $app["db"]->fetchAssoc("SELECT nombre FROM tipos_embalaje WHERE id_tipo='{$cotizacion["tipo_embalaje"]}'");
      $cotizacion["tipo_embalaje"] = $embalaje["nombre"];

      $seccion_aduanera = $app["db"]->fetchAssoc("SELECT denominacion FROM secciones_aduaneras WHERE id_seccion='{$cotizacion["seccion_aduanera"]}'");
      $cotizacion["seccion_aduanera"] = $seccion_aduanera["denominacion"];

      $medio_transporte = $app["db"]->fetchAssoc("SELECT medio_t_espanol FROM medios_transporte WHERE id_medio_transporte='{$cotizacion["medio_transporte"]}'");
      $cotizacion["medio_transporte"] = $medio_transporte["medio_t_espanol"];

      $incoterm = $app["db"]->fetchAssoc("SELECT nombre FROM incoterms WHERE codigo='{$cotizacion["incoterm"]}'");
      $cotizacion["incoterm"] = $incoterm["nombre"];

      $moneda = $app["db"]->fetchAssoc("SELECT * FROM monedas WHERE id_moneda='{$cotizacion["moneda"]}'");
      $cotizacion["moneda"] = $moneda["n_espanol"];
      $cotizacion["signo_moneda"] = $moneda["signo"];

      $tipo_operacion = $app["db"]->fetchAssoc("SELECT nombre FROM tipo_operacion WHERE id_operacion='{$cotizacion["operacion"]}'");
      $cotizacion["operacion"] = $tipo_operacion["nombre"];

      $co = $app["db"]->fetchAssoc("SELECT * FROM usuarios WHERE id='{$cotizacion["co"]}'");
      $cotizacion["co"] = $co["nombre"];
      $cotizacion["co_email"] = $co["email"];

      /**
      @ValorFactura
      @Es la suma de todas las órdenes de compra por cotización
      */

      $ordenes_compra = $app["db"]->fetchAll("SELECT *
        FROM cotizaciones_ordenes co LEFT JOIN ordenes_productos op
        ON co.id_u_orden = op.id_u_orden
        WHERE id_u_cotizacion='{$id_cotizacion}'
        ");

        $cotizacion["valor_factura"] = "";

      foreach($ordenes_compra as $orden){
        $cotizacion["valor_factura"] += $orden["cantidad"] * $orden["precio"];
      }

      /**
      @Valor incrementables
      @Es la suma de todas las órdenes de compra por cotización
      */

      $incrementables_cotizacion = $app["db"]->fetchAll("SELECT * FROM
        incrementables_cotizacion ic LEFT JOIN incrementables inc
        ON ic.id_incrementable = inc.id_incrementable
        WHERE ic.id_u_cotizacion ='{$id_cotizacion}'");
      $cotizacion["incrementables_cotizacion"] = $incrementables_cotizacion;

      $valor_incrementables = "";

      foreach($incrementables_cotizacion as $incrementable){
        $valor_incrementables += $incrementable["valor"];
      }

      /**
      @SeguroCotizacion
      @Valor factura + Valor incrementables % seguro
      */

      $cotizacion["seguro_cotizacion"] = ($cotizacion["valor_factura"] + $valor_incrementables) * ($cotizacion["seguro"] / 100 );

      if($cotizacion["seguro_cotizacion"] < $cotizacion["min_seguro"]){
        $cotizacion["seguro_cotizacion"] = $cotizacion["min_seguro"];
      }

      $cotizacion["total_incrementables"] = $valor_incrementables + $cotizacion["seguro_cotizacion"];

      $cotizacion["valor_aduana"] = $cotizacion["valor_factura"] + $cotizacion["total_incrementables"];

      $cotizacion["gastos_aduanales"] = $app["db"]->fetchAll("SELECT * FROM
        gastos_cotizaciones gc LEFT JOIN gastos_aduanales ga
        ON gc.id_gasto = ga.id_gasto
        WHERE gc.id_u_cotizacion ='{$id_cotizacion}'");

      $gastos_complementarios = $cotizacion["hon_agente_plus"] * $cotizacion["cantidad_embalaje"];

      $honorarios_agente_aduanal = $cotizacion["valor_aduana"] * $cotizacion["hon_agente"] / 100;

      $cotizacion["honorarios_agente_aduanal"] = $honorarios_agente_aduanal + $gastos_complementarios;

      $cotizacion["total_gastos_aduanales"] = 0;

      foreach($cotizacion["gastos_aduanales"] as $gasto){
        $cotizacion["total_gastos_aduanales"] += $gasto["valor"];
      }

      $cotizacion["total_gastos_aduanales"] = $cotizacion["total_gastos_aduanales"] + $cotizacion["honorarios_agente_aduanal"];

      $prorrateo = array();

      $monto_total = 0;
      $total_productos = 0;

      for($i=0;$i<count($ordenes_compra); $i++){

        $prorrateo[$i]["cantidad"] = $ordenes_compra[$i]["cantidad"];
        $prorrateo[$i]["precio_unitario"] = $ordenes_compra[$i]["precio"];
        $prorrateo[$i]["monto_total"] = $prorrateo[$i]["cantidad"] * $prorrateo[$i]["precio_unitario"];
        $monto_total += $prorrateo[$i]["monto_total"];
        $total_productos += $prorrateo[$i]["cantidad"];
      }

      $impuestos_cotizacion = $app["db"]->fetchAssoc("SELECT * FROM impuestos_cotizacion WHERE id_u_cotizacion='{$id_cotizacion}'");
      $cotizacion["dta_cotizacion"] = $cotizacion["valor_aduana"] * $impuestos_cotizacion["dta_porcentaje"] / 100;

      if($cotizacion["dta_cotizacion"] < $impuestos_cotizacion["dta"]){
        $cotizacion["dta_cotizacion"] = $impuestos_cotizacion["dta"];
      }

      $cotizacion["total_impuestos"] = 0;

      $cotizacion["igi_total"] = 0;
      $cotizacion["dta_total"] = 0;
      $cotizacion["prv_total"] = 0;
      $cotizacion["iva_total"] = 0;

      for($i=0;$i<count($ordenes_compra); $i++){
        $prorrateo[$i]["porcentaje_incrementables"] = $prorrateo[$i]["monto_total"] / $monto_total;
        $prorrateo[$i]["incrementables"] = $cotizacion["total_incrementables"] * $prorrateo[$i]["porcentaje_incrementables"];
        $prorrateo[$i]["incrementable_por_pieza"] = $prorrateo[$i]["incrementables"] / $prorrateo[$i]["cantidad"];
        $prorrateo[$i]["valor_aduana_unitario"] = $prorrateo[$i]["incrementable_por_pieza"] + $prorrateo[$i]["precio_unitario"];
        $prorrateo[$i]["valor_aduana_total"] = $prorrateo[$i]["valor_aduana_unitario"] * $prorrateo[$i]["cantidad"];

        $prorrateo[$i]["igi_unitario"] = $prorrateo[$i]["valor_aduana_unitario"] * $ordenes_compra[$i]["igi"] / 100;
        $prorrateo[$i]["igi_total"] = $prorrateo[$i]["igi_unitario"] * $ordenes_compra[$i]["cantidad"];

        $cotizacion["igi_total"] += $prorrateo[$i]["igi_total"];

        $prorrateo[$i]["dta_unitario"] = $cotizacion["dta_cotizacion"] / $total_productos;
        $prorrateo[$i]["dta_total"] = $prorrateo[$i]["dta_unitario"] * $ordenes_compra[$i]["cantidad"];

        $cotizacion["dta_total"] += $prorrateo[$i]["dta_total"];

        $prorrateo[$i]["prv_unitario"] = $impuestos_cotizacion["prv"] / $total_productos;
        $prorrateo[$i]["prv_total"] = $prorrateo[$i]["prv_unitario"] * $ordenes_compra[$i]["cantidad"];

        $cotizacion["prv_total"] += $prorrateo[$i]["prv_total"];

        $prorrateo[$i]["iva_aduana_unitario"] = ($prorrateo[$i]["valor_aduana_unitario"] + $prorrateo[$i]["igi_unitario"] + $prorrateo[$i]["dta_unitario"]) * $ordenes_compra[$i]["iva_aduanal"] / 100;

        $prorrateo[$i]["iva_aduana_total"] = $prorrateo[$i]["iva_aduana_unitario"] * $ordenes_compra[$i]["cantidad"];

        $cotizacion["iva_total"] += $prorrateo[$i]["iva_aduana_total"];

        $prorrateo[$i]["gastos_aduanales"] = $cotizacion["total_gastos_aduanales"] / $total_productos;
        $prorrateo[$i]["total_gastos_aduanales"] = $prorrateo[$i]["gastos_aduanales"] * $ordenes_compra[$i]["cantidad"];

      }

      $cotizacion["total_impuestos"] = $cotizacion["igi_total"] + $cotizacion["dta_total"] + $cotizacion["prv_total"] + $cotizacion["iva_total"];

      $cotizacion["honorarios_cia"] = ($cotizacion["valor_aduana"] + $cotizacion["total_gastos_aduanales"] + $cotizacion["total_impuestos"] ) * $cotizacion["hon_cia"] / 100;

      if($cotizacion["honorarios_cia"] < $cotizacion["hon_cia_plus"]){
        $cotizacion["honorarios_cia"] = $cotizacion["hon_cia_plus"];
      }

      $cotizacion["subtotal"] = 0;
      for($i=0;$i<count($ordenes_compra); $i++){
        $prorrateo[$i]["honorarios_unitarios"] = $cotizacion["honorarios_cia"] / $total_productos;
        $prorrateo[$i]["honorarios_total"] = $prorrateo[$i]["honorarios_unitarios"] * $ordenes_compra[$i]["cantidad"];
        $prorrateo[$i]["precio_unitario_nacional"] = $prorrateo[$i]["valor_aduana_unitario"] + $prorrateo[$i]["igi_unitario"] + $prorrateo[$i]["dta_unitario"] + $prorrateo[$i]["prv_unitario"] + $prorrateo[$i]["gastos_aduanales"] + $prorrateo[$i]["honorarios_unitarios"];
        $prorrateo[$i]["precio_total_nacional"] = $prorrateo[$i]["precio_unitario_nacional"] * $ordenes_compra[$i]["cantidad"];
        $cotizacion["subtotal"] += $prorrateo[$i]["precio_total_nacional"];
      }

      $cotizacion["total_gastos_impuestos_honorarios"] = $cotizacion["total_gastos_aduanales"] + $cotizacion["total_impuestos"] + $cotizacion["honorarios_cia"];
      $cotizacion["total_gastos_sin_iva"] = $cotizacion["total_gastos_impuestos_honorarios"] - $cotizacion["iva_total"];
      $cotizacion["iva_factura"] = $impuestos_cotizacion["iva_factura"] * $cotizacion["subtotal"] / 100;
      $cotizacion["total_factura"] = $cotizacion["iva_factura"] + $cotizacion["subtotal"];
      $cotizacion["pagar_sin_valor_factura"] = $cotizacion["total_factura"] - $cotizacion["valor_factura"];
      $cotizacion["saldo_mercancia"] = $cotizacion["valor_factura"];
      $cotizacion["saldo_despacho"] = ($cotizacion["total_factura"] - $cotizacion["valor_factura"]);

      return $cotizacion;

    });
  }

  public function boot(Application $app)
  {
  }
}


 ?>
