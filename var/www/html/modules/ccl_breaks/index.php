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
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
require_once "libs/misc.lib.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoDescansos.class.php";

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
        case 'borrar':
            $content = borrarDescanso($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'mostrar_formulario':
            $content = mostrarFormulario($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'guardar_editar':
        case 'guardar_nuevo':
            $content = guardarDescanso($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = mostrarDescansos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function mostrarDescansos($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pDescansos = new paloSantoDescansos($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Break"));
    $oGrid->pagingShow(true); // show paging section.

    // Posibilidad de Eliminar una marca
    $oGrid->deleteList(_tr("Are you sure?"),'borrar',_tr("Delete"));

    $url = array(
        "menu"         =>  $module_name,
        "filter_field" =>  $filter_field,
        "filter_value" =>  $filter_value);
    $oGrid->setURL($url);

    // Columnas que deben aparecer
    $arrColumns = array(_tr(""),_tr("Prefix"),_tr("Name"),_tr("Description"),_tr("Edit"));
    $oGrid->setColumns($arrColumns);

    // Campos para realizar el filtrado de resultados
    $arrFilter = array("prefijo" => _tr("Prefix"), "nombre" => _tr("Name"));

    // mostrar que filtro fue aplicado
    if ($filter_field != '') {
      $oGrid->addFilterControl(_tr("Filter applied: ").$arrFilter[$filter_field]." = ".$filter_value, $filter_value, array("filter_field" => "", "filter_value" => ""));
    }

    $total = $pDescansos->obtenerTotalDescansos($filter_field, $filter_value);
    $arrData = null;

    $limit  = 20;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $arrResult =$pDescansos->obtenerDescansos($limit, $offset, $filter_field, $filter_value);

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){ 
          $arrTmp[0] = "<input type='checkbox' name='prefix_".$value['prefijo']."' />";
          $arrTmp[1] = $value['prefijo'];
          $arrTmp[2] = $value['nombre'];
          $arrTmp[3] = $value['descripcion'];
          $arrTmp[4] = "<a href='index.php?menu=$module_name&action=mostrar_editar&prefijo=$arrTmp[1]'>"._tr("Edit")."</a>";
          $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    //begin section filter
    $oFilterForm = new paloForm($smarty, createFieldFilter($arrFilter));
    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("ADD_NEW", _tr("Add new break"));
    $smarty->assign("ADD_NEW_LINK", "index.php?menu=$module_name&crear_descanso=crear_descanso");
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filtro_mostrar_descansos.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}

function createFieldFilter($arrFilter)
{
    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Search"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function borrarDescanso($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{ 
  $pDescansos = new paloSantoDescansos($pDB);

  foreach ($_POST as $campo => $valor) {
    if(substr($campo, 0, 7) == "prefix_") {
      $prefijo = substr($campo, 7);
      $pDescansos->borrarDescansoPorPrefijo($prefijo);
    }
  }
  
  return mostrarDescansos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function mostrarFormulario($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pDescansos = new paloSantoDescansos($pDB);
    $arrFormDescanso = camposParaFormulario();
    $oForm = new paloForm($smarty,$arrFormDescanso);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id = getParameter("prefijo");
    $smarty->assign("ID", $id); //persistence prefijo with input hidden in tpl

    if($action=="mostrar") {
      $oForm->setViewMode();
    } else if ($action=="mostrar_editar" || getParameter("guardar_editar")) {
      $oForm->setEditMode();
    }

    if($action=="mostrar" || $action=="mostrar_editar"){ // the action is to mostrar or mostrar_editar.
        $dataformulario = $pDescansos->obtenerDescansoPorPrefijo($id);
        if(is_array($dataformulario) & count($dataformulario)>0)
            $_DATA = $dataformulario;
        else{
            $smarty->assign("mb_title", _tr("Error get Data"));
            $smarty->assign("mb_message", $pDescansos->errMsg);
        }
    }

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/formulario.tpl",_tr("Break"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
  
}

function camposParaFormulario()
{
    $arrFields = array(
            "prefijo"       => array( "LABEL"                  => _tr("Prefix"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => "",
                                      "VALIDATION_TYPE"        => "ereg",
                                      "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{2}$"

                                    ),
            "nombre"        => array( "LABEL"                  => _tr("Name"),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => "",
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            "descripcion"   => array( "LABEL"                  => _tr("Description"),
                                      "REQUIRED"               => "no",
                                      "INPUT_TYPE"             => "TEXTAREA",
                                      "INPUT_EXTRA_PARAM"      => "",
                                      "VALIDATION_TYPE"        => "text",
                                      "EDITABLE"               => "si",
                                      "COLS"                   => "50",
                                      "ROWS"                   => "4",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            );
    return $arrFields;
}

function guardarDescanso($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pDescansos = new paloSantoDescansos($pDB);
    $arrFormformulario = camposParaFormulario();
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
        if (isset($data['guardar_nuevo'])) {
          $content = mostrarFormulario($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        } else {
          $content = mostrarDescansos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        }
    }
    else{
        
        $nombre = trim($data['nombre']);
        $descripcion = trim($data['descripcion']);
        
        if (isset($data['guardar_nuevo'])) {
          $prefijo = $data['prefijo'];
          $pDescansos->crearDescanso($prefijo, $nombre, $descripcion);
        } else if (isset($data['guardar_editar'])) {
          $prefijo = $data['id'];
          $pDescansos->editarDescanso($prefijo, $nombre, $descripcion);
        } 

        $content = mostrarDescansos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    return $content;
}

function getAction()
{
    if(getParameter("guardar_nuevo")) //Get parameter by POST (submit)
        return "guardar_nuevo";
    else if(getParameter("guardar_editar"))
        return "guardar_editar";
    else if(getParameter("borrar")) 
        return "borrar";
    else if(getParameter("crear_descanso")) 
        return "mostrar_formulario";
    else if(getParameter("action")=="mostrar")      //Get parameter by GET (command pattern, links)
        return "mostrar_formulario";
    else if(getParameter("action")=="mostrar_editar")
        return "mostrar_formulario";
    else
        return "mostrar_descansos"; //cancel
}
?>