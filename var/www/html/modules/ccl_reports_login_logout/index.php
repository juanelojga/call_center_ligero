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
  $Id: index.php,v 1.1 2016-02-22 09:02:19 Juan Almeida jalmeida@palosanto.com Exp $ */
//include elastix framework
require_once "libs/paloSantoGrid.class.php";
require_once "libs/paloSantoForm.class.php";
require_once "libs/misc.lib.php";
require_once "libs/paloSantoACL.class.php";
require_once "modules/ccl_supervisors/libs/paloSantoSupervisores.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/paloSantoReporteLogin.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) require_once "$lang_file";
    else require_once "modules/$module_name/lang/en.lang";

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

    // determinar el usuario del sistema
    $pDBACL = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pDBACL);
    $user = isset($_SESSION['elastix_user'])?$_SESSION['elastix_user']:"";
    $esAdministrador = $pACL->isUserAdministratorGroup($user);
    $userId = $pACL->getIdUser($user);

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        default:
            $content = mostrarReporteLogin($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $esAdministrador, $userId);
            break;
    }
    return $content;
}

function mostrarReporteLogin($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $esAdministrador, $userId)
{
    $pReporteLogin = new paloSantoReporteLogin($pDB);

    $valoresPorDefectoFiltro = valoresPorDefectoFiltro();

    $parametrosFiltro = valoresFiltro($valoresPorDefectoFiltro);

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Reports Login Logout"));
    $oGrid->pagingShow(true); // show paging section.

    $url = [
      "menu" => $module_name,
    ];

    $url = array_merge($url, $parametrosFiltro);

    $oGrid->enableExport();   // enable export.
    $oGrid->setNameFile_Export(_tr("Reports Login Logout"));
    $oGrid->setURL($url);

    // Columnas que deben aparecer
    $arrColumns = array(_tr("Date Start"),_tr("Date End"),_tr("Extension"),_tr("Duration"),_tr("Reason"),_tr("Brand"),_tr("Campaign"),_tr("Agent Number"),_tr("Agent Name"),_tr("# Calls Active"),_tr("Time Calls Active"),_tr("# Calls Break"),_tr("Time Calls Break"),_tr("Time on Break"),_tr("% Occupation"));
    $oGrid->setColumns($arrColumns);

    $marcasAsignadas = obtenerMarcasAsignadas($pDB, $esAdministrador, $userId);

    $total = $pReporteLogin->obtenerTotalRegistros($parametrosFiltro, $marcasAsignadas);

    $arrData = null;

    if($oGrid->isExportAction()){
        
      $limit = $total;
      $offset = 0;
      
    } else {

      $limit  = 20;
      $oGrid->setLimit($limit);
      $oGrid->setTotal($total);
      $offset = $oGrid->calculateOffset();

    }

    $arrResult =$pReporteLogin->obtenerRegistros($limit, $offset, $parametrosFiltro, $marcasAsignadas);

    if(is_array($arrResult) && $total>0){
      foreach($arrResult as $key => $value){ 
        
        $ocupacion = ($value["tiempo_llamadas_activo"] / ($value["duracion_sesion"] - $value["tiempo_en_descanso"])) * 100;

        $arrTmp[0] = $value['fecha_inicio'];
        $arrTmp[1] = $value['fecha_fin'];
        $arrTmp[2] = $value['extension'];
        $arrTmp[3] = gmdate("H:i:s", $value["duracion_sesion"]);
        $arrTmp[4] = ucfirst($value['motivo_desconexion']);
        $arrTmp[5] = $value['marca_nombre'];
        $arrTmp[6] = $value['campana_nombre'];
        $arrTmp[7] = $value['agente_numero'];
        $arrTmp[8] = $value['agente_nombre'];
        $arrTmp[9] = $value['numero_llamadas_activo'];
        $arrTmp[10] = gmdate("H:i:s", $value["tiempo_llamadas_activo"]);
        $arrTmp[11] = $value['numero_llamadas_descanso'];
        $arrTmp[12] = gmdate("H:i:s", $value["tiempo_llamadas_descanso"]);
        $arrTmp[13] = gmdate("H:i:s", $value["tiempo_en_descanso"]);
        $arrTmp[14] = number_format($ocupacion,2);
        $arrData[] = $arrTmp;
      }
    }

    if (!is_array($arrResult)) {
      $smarty->assign(array(
        'mb_title'      =>  _tr('ERROR'),
        'mb_message'    =>  $pReporteLogin->errMsg,
      ));
    }

    $oGrid->setData($arrData);

    //begin section filter
    $oFilterForm = new paloForm($smarty, createFieldFilter());
    $smarty->assign("SHOW", _tr("Show"));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filtro.tpl","",$parametrosFiltro);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}

function createFieldFilter()
{

    $arrMotivos = [
      NULL          => _tr(""),
      "normal"      => _tr("Normal"),
      "supervisor"  => _tr("Supervisor"),
      "forzado"     => _tr("Forced"),
    ];

    $arrFormElements = array(
            "fecha_inicio"        => array( "LABEL"                  => _tr("Date Start"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "DATE",
                                            "INPUT_EXTRA_PARAM"      => array("TIME"=> FALSE, "FORMAT" => "%Y-%m-%d"),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => "^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$"),
            "fecha_fin"           => array( "LABEL"                  => _tr("Date End"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "DATE",
                                            "INPUT_EXTRA_PARAM"      => array("TIME"=> FALSE, "FORMAT" => "%Y-%m-%d"),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => "^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$"),
            "extension_inicio"    => array( "LABEL"                  => _tr("Extension"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "extension_fin"       => array( "LABEL"                  => _tr("Extension"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "marca_prefijo"       => array( "LABEL"                  => _tr("Brand Prefix"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "marca_nombre"        => array( "LABEL"                  => _tr("Brand Name"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "campana_prefijo_inicio"=> array( "LABEL"                  => _tr("Campaign Prefix"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "campana_prefijo_fin" => array( "LABEL"                  => _tr("Campaign Prefix"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "campana_nombre"      => array( "LABEL"                  => _tr("Campaign Name"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "agente_numero_inicio"=> array( "LABEL"                  => _tr("Agent Number"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "agente_numero_fin"   => array( "LABEL"                  => _tr("Agent Number"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "agente_nombre"       => array( "LABEL"                  => _tr("Agent Name"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
            "motivo_desconexion"  => array( "LABEL"                  => _tr("Disconnection Reason"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrMotivos,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function valoresPorDefectoFiltro()
{
    return array(
        'fecha_inicio'            => date("Y-m-d"), 
        'fecha_fin'               => date("Y-m-d"),
        'motivo_desconexion'      => NULL,
        'extension_inicio'        => NULL,
        'extension_fin'           => NULL,
        'agente_numero_inicio'    => NULL,
        'agente_numero_fin'       => NULL,
        'agente_nombre'           => NULL,
        'campana_prefijo_inicio'  => NULL,
        'campana_prefijo_fin'     => NULL,
        'campana_nombre'          => NULL,
        'marca_prefijo'           => NULL,
        'marca_nombre'            => NULL,
    );
}

function valoresFiltro($paramFiltro)
{
    foreach (array_keys($paramFiltro) as $k) {
        if (!is_null(getParameter($k)) && getParameter($k) != ""){
            $paramFiltro[$k] = getParameter($k);
        }
    }

    return $paramFiltro;
}

function obtenerMarcasAsignadas(&$pDB, $esAdministrador, $userId)
{
  if ($esAdministrador) {
    
    $resultado = [
      "esAdministrador" => 1,
      "marcas" => NULL
    ];

  } else {

    $pSupervisores = new paloSantoSupervisores($pDB);

    $marcas = $pSupervisores->obtenerMarcasAsignadasSupervisor($userId);

    $resultado = [
      "esAdministrador" => 0,
      "marcas" => $marcas
    ];

  }

  return $resultado;
}

function getAction()
{
    if(getParameter("mostrar_reporte")) //Get parameter by POST (submit)
        return "mostrar_reporte";
    else if(getParameter("action")=="mostrar_reporte") //Get parameter by GET (command pattern, links)
        return "mostrar_reporte";
    else
        return "mostrar_reporte"; //cancel
}