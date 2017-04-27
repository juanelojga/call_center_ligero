<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.5.0-2                                               |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2016-02-24 11:02:39 Juan Almeida jalmeida@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
require_once "libs/misc.lib.php";
include_once "modules/ccl_brands/libs/paloSantoMarcas.class.php";
include_once "modules/ccl_campaigns/libs/paloSantoCampanas.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAgentes.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $dsn = generarDSNSistema('root', 'ccl_ligero');
    $pDB = new paloDB($dsn);

    //actions
    $action = getAction();
    $content = "";

    switch($action){
      case 'delete':
            $content = deleteAgentes($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'view_form':
            $content = viewFormAgente($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'view_edit':
            $content = viewFormEditarAgente($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'save_edit':
            $content = editarAgente($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'obtener_marcas':
            $content = obtenerMarcasJson($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'obtener_campanas':
            $content = obtenerCampanasJson($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'obtener_agentes_libres':
            $content = obtenerAgentesLibresJson($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      case 'crear_agentes':
            $content = crearAgentesJson($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
      default:
            $content = reportAgentes($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportAgentes($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pAgentes = new paloSantoAgentes($pDB);
    
    $paramFiltroBase = $paramFiltro = array(
        "filter_field"        => "",
        "filter_value"        => "",
        "filter_by_brand"     => "",
        "filter_by_campaign"  => "",
    );

    foreach (array_keys($paramFiltro) as $k) {
        if (!is_null(getParameter($k))){
            $paramFiltro[$k] = getParameter($k);
        }
    }

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Agent"));
    $oGrid->pagingShow(true); // show paging section.

    // Posibilidad de Eliminar una marca
    $oGrid->deleteList(_tr("Are you sure?"),'delete',_tr("Delete"));

    $url = array(
        "menu"                =>  $module_name,
        "filter_field"        =>  $paramFiltro['filter_field'],
        "filter_value"        =>  $paramFiltro['filter_value'],
        "filter_by_brand"     =>  $paramFiltro['filter_by_brand'],
        "filter_by_campaign"  =>  $paramFiltro['filter_by_campaign']);
    $oGrid->setURL($url);

    $arrColumns = array(_tr(""),_tr("Number"),_tr("Name"),_tr("Brand"),_tr("Campaign"),_tr("Edit"));
    $oGrid->setColumns($arrColumns);

    $arrFilter = array("numero" => _tr("Number"), "nombre" => _tr("Name"));

    // mostrar que filtro fue aplicado
    if ($paramFiltro['filter_field'] != '') {
      $oGrid->addFilterControl(_tr("Filter applied: ").$arrFilter[$paramFiltro['filter_field']]." = ".$paramFiltro['filter_value'], $paramFiltro['filter_value'], array("filter_field" => "", "filter_value" => ""),true);
    }

    if ($paramFiltro['filter_by_brand'] != '') {
      $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Brand")." = ".$paramFiltro['filter_by_brand'], $paramFiltro['filter_by_brand'], array("filter_by_brand" => ""),true);
    }

    if ($paramFiltro['filter_by_campaign'] != '') {
      $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Campaign")." = ".$paramFiltro['filter_by_campaign'], $paramFiltro['filter_by_campaign'], array("filter_by_campaign" => ""),true);
    }

    $total = $pAgentes->getNumAgentes(
      $paramFiltro['filter_field'], 
      $paramFiltro['filter_value'],
      $paramFiltro['filter_by_brand'],
      $paramFiltro['filter_by_campaign']
      );

    $arrData = null;
    
    $limit  = 20;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $arrResult =$pAgentes->getAgentes(
      $limit, 
      $offset, 
      $paramFiltro['filter_field'], 
      $paramFiltro['filter_value'],
      $paramFiltro['filter_by_brand'],
      $paramFiltro['filter_by_campaign']
      );

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){ 
      	    $arrTmp[0] = "<input type='checkbox' name='prefix_".$value['numero']."' />";
      	    $arrTmp[1] = $value['numero'];
            $arrTmp[2] = $value['nombre'];
            $arrTmp[3] = $value['marca_nombre'];
            $arrTmp[4] = $value['campana_nombre'];
            $arrTmp[5] = "<a href='index.php?menu=$module_name&action=view_edit&numero_agente=$arrTmp[1]'>"._tr("Edit")."</a>";
            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    //begin section filter
    $oFilterForm = new paloForm($smarty, createFieldFilter($arrFilter));
    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("ADD_NEW", _tr("Add new agent"));
    $smarty->assign("ADD_NEW_LINK", "index.php?menu=$module_name&new_open=new_open");
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$paramFiltro);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}


function createFieldFilter($arrFilter)
{

    $arrFormElements = array(
            "filter_field"        => array("LABEL"               => _tr("Search"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => $arrFilter,
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value"        => array("LABEL"               => "",
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "filter_by_brand"     => array("LABEL"               => _tr("Brand"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
            "filter_by_campaign"  => array("LABEL"               => _tr("Campaign"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function deleteAgentes($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{ 
  $pAgentes = new paloSantoAgentes($pDB);
  
  foreach ($_POST as $campo => $valor) {
    if(substr($campo, 0, 7) == "prefix_") {
      $numeroAgente = substr($campo, 7);
      $pAgentes->deleteAgente($numeroAgente);
    }
  }

  return reportAgentes($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function viewFormAgente($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pAgentes = new paloSantoAgentes($pDB);
    $arrFormAgente = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormAgente);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;

    $action = getParameter("action");

    $smarty->assign("BRAND", _tr("Brand"));
    $smarty->assign("CAMPAIGN", _tr("Campaign"));
    $smarty->assign("QUANTITY", _tr("Quantity"));
    $smarty->assign("FREE_AGENTS", _tr("Free Agents"));
    $smarty->assign("AGENT_PREFIX", _tr("Initial Agent Prefix"));

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/nuevo_agente.tpl",_tr("Agents"), $_DATA);
    $content = "<div ng-app='app' ng-controller='MainCtrl' ng-init=obtenerMarcas()><form style='margin-bottom:0;' ng-submit='guardarAgente()' name='nuevoAgente'>".$htmlForm."</form></div>";

    return $content;
  
}

function createFieldForm()
{
    $arrFields = array(
            "nombre"        => array( "LABEL"                  => _tr("Base Name"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("ng-model" => "nombre"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            );
    return $arrFields;
}

function viewFormEditarAgente($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pAgentes = new paloSantoAgentes($pDB);

    $camposFormulario = createFieldFilterEditForm();
    $oForm = new paloForm($smarty,$camposFormulario);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;

    $numeroAgente = getParameter("numero_agente");
    $smarty->assign("NUMERO_AGENTE", $numeroAgente);

    $dataformulario = $pAgentes->getAgenteByNumero($numeroAgente);

    if(is_array($dataformulario) & count($dataformulario)>0)
      $_DATA = $dataformulario;
    else{
      $smarty->assign("mb_title", _tr("Error get Data"));
      $smarty->assign("mb_message", $pMarcas->errMsg);
    }

    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/editar_agente.tpl",_tr("Agent"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
  
}

function createFieldFilterEditForm()
{

    return array(
            "marca_nombre"  => array( "LABEL"                  => _tr("Brand"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("disabled" => "disabled"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            "campana_nombre"=> array( "LABEL"                  => _tr("Campaign"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("disabled" => "disabled"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            "numero"        => array( "LABEL"                  => _tr("Number"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("disabled" => "disabled"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            "nombre"        => array( "LABEL"                  => _tr("Name"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => "",
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            );
}

function editarAgente($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pAgentes = new paloSantoAgentes($pDB);

    $arrFormformulario = createFieldFilterEditForm();
    $oForm = new paloForm($smarty,$arrFormformulario);

    $data = $_POST;

    if(!$oForm->validateForm($data)){
      
        // Validation basic, not empty and VALIDATION_TYPE 
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr("The following fields contain errors").":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";
        }

        $smarty->assign("mb_message", $strErrorMsg);

        $content = reportAgentes($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    
    } else {
        
        $numero = trim($data['numero_agente']);
        $nombre = trim($data['nombre']);
        
        if (isset($data['save_edit'])) {
          $pAgentes->editarAgente($numero, $nombre);
        } 

        $content = reportAgentes($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    return $content;
}

function obtenerMarcasJson($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pMarcas = new paloSantoMarcas($pDB);

    $totalMarcas = $pMarcas->getNumMarcas();
    $marcas = $pMarcas->getMarcas($totalMarcas);

    $arrOptionsMarcas = NULL;

    // Verificar si existen marcas creadas
    if ($totalMarcas > 0) {
      foreach ($marcas as $marca) {
        $arrOptionsMarcas[] = array(
          "prefijo" => $marca['prefijo'], 
          "nombre" => $marca['nombre']);
      }
    }

    printJson($arrOptionsMarcas);
}

function obtenerCampanasJson($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pCampanas = new paloSantoCampanas($pDB);

    $arrOptionsCampanas = NULL;

    $marca = getParameter("marca");

    if (isset($marca) && $marca != "") {
      $campanas = $pCampanas->getCampanasByMarca($marca);

      // Verificar si existen marcas creadas
      if (count($campanas) > 0 && is_array($campanas)) {
        foreach ($campanas as $campana) {
          $arrOptionsCampanas[] = array(
            "prefijo" => $campana['prefijo'], 
            "nombre" => $campana['nombre']);
        }
      }
    }

    printJson($arrOptionsCampanas);
}

function obtenerAgentesLibresJson($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pAgentes = new paloSantoAgentes($pDB);

    $campana = getParameter("campana");

    // array con 100 valores
    $agentesLibres = range(1, 99);

    if (isset($campana) && $campana != "") {
      $agentes = $pAgentes->getAgentesByCampana($campana);

      // Verificar si existen agentes creados
      if (count($agentes) > 0 && is_array($agentes)) {
        foreach ($agentes as $agente) {
          $clave = array_search(substr($agente['numero'], 4, 2), $agentesLibres);
          unset($agentesLibres[$clave]);
        }
      }
    }

    printJson([
      'total' => count($agentesLibres),
      'agentes' => array_values($agentesLibres),
    ]);
}

function crearAgentesJson($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pAgentes = new paloSantoAgentes($pDB);

    $message = "success";
    $total = 0;

    $campana = getParameter("campana");
    $cantidad = getParameter("cantidad");
    $semillaAgente = getParameter("semilla_agente");
    $semillaNombre = getParameter("semilla_nombre");

    if (isset($campana) && $campana != "") {
      // 100 posibles agentes
      $arrAgentes = range(1, 99);

      $arrAgentes = array_slice($arrAgentes, $semillaAgente);

      $agentes = $pAgentes->getAgentesByCampana($campana);

      // Verificar si existen agentes creados
      if (count($agentes) > 0 && is_array($agentes)) {
        foreach ($agentes as $agente) {
          $clave = array_search(substr($agente['numero'], 4, 2), $arrAgentes);
          unset($arrAgentes[$clave]);
        }
      }
    
      $nuevosAgentes = array_values($arrAgentes);

      array_unshift($nuevosAgentes, $semillaAgente);

      if (count($nuevosAgentes) > $cantidad) {
        $nuevosAgentes = array_slice($nuevosAgentes, 0, $cantidad);
      }

      // completar el array con los datos que se ingresaran
      foreach ($nuevosAgentes as $nuevoAgente) {   

        $numero = $campana . sprintf("%02d", $nuevoAgente);
        $nombre = $semillaNombre . sprintf("%02d", $nuevoAgente);

        if (!$pAgentes->createAgente($numero, $nombre, $campana)) {
          Header('HTTP/1.1 404 Not Found');
          die("<b>404 "._tr("error")." </b>");
        } else {
          $total++;
        }

      }

    }

    printJson([
      'message' => $message,
      'total' => $total,
    ]);
}

function printJson($data)
{
    header('Content-type: application/json');
    
    header('Access-Control-Allow-Origin: *');

    echo json_encode($data);
    
    exit();
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_open")) 
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_edit";
    else if(getParameter("action")=="obtener_marcas")
        return "obtener_marcas";
    else if(getParameter("action")=="obtener_campanas")
        return "obtener_campanas";
    else if(getParameter("action")=="obtener_agentes_libres")
        return "obtener_agentes_libres";
    else if(getParameter("action")=="crear_agentes")
        return "crear_agentes";
    else
        return "report"; //cancel
}
?>