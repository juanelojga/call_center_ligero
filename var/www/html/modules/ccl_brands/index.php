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
    include_once "modules/$module_name/libs/paloSantoMarcas.class.php";

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
            $content = deleteMarca($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'view_form':
            $content = viewFormMarca($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'save_edit':
        case 'save_new':
            $content = saveNewMarca($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = reportMarcas($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportMarcas($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pMarcas = new paloSantoMarcas($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Brand"));
    $oGrid->pagingShow(true); // show paging section.

    // Posibilidad de Eliminar una marca
    $oGrid->deleteList(_tr("Are you sure?"),'delete',_tr("Delete"));

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

    $total = $pMarcas->getNumMarcas($filter_field, $filter_value);
    $arrData = null;

    $limit  = 20;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $arrResult =$pMarcas->getMarcas($limit, $offset, $filter_field, $filter_value);

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){ 
          $arrTmp[0] = "<input type='checkbox' name='prefix_".$value['prefijo']."' />";
          $arrTmp[1] = $value['prefijo'];
          $arrTmp[2] = $value['nombre'];
          $arrTmp[3] = $value['descripcion'];
          $arrTmp[4] = "<a href='index.php?menu=$module_name&action=view_edit&prefijo=$arrTmp[1]'>"._tr("Edit")."</a>";
          $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    //begin section filter
    $oFilterForm = new paloForm($smarty, createFieldFilter($arrFilter));
    $smarty->assign("SHOW", _tr("Show"));
    $smarty->assign("ADD_NEW", _tr("Add new brand"));
    $smarty->assign("ADD_NEW_LINK", "index.php?menu=$module_name&new_open=new_open");
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}

function createFieldFilter($arrFilter){

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

function deleteMarca($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{ 
  $pMarcas = new paloSantoMarcas($pDB);
  
  foreach ($_POST as $campo => $valor) {
    if(substr($campo, 0, 7) == "prefix_") {
      $prefijo = substr($campo, 7);
      $pMarcas->deleteMarcaByPrefijo($prefijo);
    }
  }

  return reportMarcas($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function viewFormMarca($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pMarcas = new paloSantoMarcas($pDB);
    $arrFormMarca = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormMarca);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id = getParameter("prefijo");
    $smarty->assign("ID", $id); //persistence prefijo with input hidden in tpl

    if($action=="view")
        $oForm->setViewMode();
    else if($action=="view_edit" || getParameter("save_edit"))
        $oForm->setEditMode();
    //end, Form data persistence to errors and other events.

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
        $dataformulario = $pMarcas->getMarcaByPrefijo($id);
        if(is_array($dataformulario) & count($dataformulario)>0)
            $_DATA = $dataformulario;
        else{
            $smarty->assign("mb_title", _tr("Error get Data"));
            $smarty->assign("mb_message", $pMarcas->errMsg);
        }
    }

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Brand"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
  
}

function createFieldForm()
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

function saveNewMarca($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pMarcas = new paloSantoMarcas($pDB);
    $arrFormformulario = createFieldForm();
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
        if (isset($data['save_new'])) {
          $content = viewFormMarca($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        } else {
          $content = reportMarcas($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
        }
    }
    else{
        
        $nombre = trim($data['nombre']);
        $descripcion = trim($data['descripcion']);
        
        if (isset($data['save_new'])) {
          $prefijo = $data['prefijo'];
          $pMarcas->createMarca($prefijo, $nombre, $descripcion);
        } else if (isset($data['save_edit'])) {
          $prefijo = $data['id'];
          $pMarcas->editMarca($prefijo, $nombre, $descripcion);
        } 

        $content = reportMarcas($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    return $content;
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
        return "view_form";
    else
        return "report"; //cancel
}
?>