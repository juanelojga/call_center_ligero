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
require_once "libs/paloSantoDB.class.php";
require_once "libs/paloSantoConfig.class.php";
require_once "libs/paloSantoACL.class.php";

require_once "modules/ccl_brands/libs/paloSantoMarcas.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/paloSantoSupervisores.class.php";

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

    $pACL = new paloACL(new paloDB($arrConf['elastix_dsn']['acl']));
    if(!empty($pACL->errMsg)) {
        echo "ERROR DE ACL: $pACL->errMsg <br>";
    }

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case 'mostrar_formulario':
            $content = mostrarFormularioAsignarMarcas($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf);
            break;
        case 'obtener_informacion_supervisor':
            $content = obtenerInformacionSupervisor($pDB);
            break;
        case 'asignar_marca':
            $content = asignarMarcaSupervisor($pDB);
            break;
        case 'borrar_marca':
            $content = borrarMarcaSupervisor($pDB);
            break;
        default:
            $content = mostrarListaUsuarios($smarty, $module_name, $local_templates_dir, $pDB, $pACL, $arrConf);
            break;
    }
    return $content;
}

function mostrarListaUsuarios($smarty, $module_name, $local_templates_dir, &$pDB, &$pACL, $arrConf)
{

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Supervisors"));
    $oGrid->pagingShow(true); // show paging section.

    $url = [
        "menu" => $module_name
    ];

    $oGrid->setURL($url);

    // Columnas que deben aparecer
    $arrColumns = array(_tr("User"),_tr("Name"),_tr("Group"),_tr("Brands"));
    $oGrid->setColumns($arrColumns);

    $total = $pACL->getNumUsers();
    $arrData = null;

    $limit  = 20;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $arrUsers = $pACL->getUsersPaging($limit, $offset);

    if(is_array($arrUsers) && $total>0){
        foreach($arrUsers as $user){ 
          
          $arrMembership = $pACL->getMembership($user[0]);
          $idGrupo = array_values($arrMembership);

          if ($idGrupo[0] != 1) {
            $arrGroup = array_keys($arrMembership);
            $arrTmp[0] = $user[1];
            $arrTmp[1] = $user[2];
            $arrTmp[2] = $arrGroup[0];
            $arrTmp[3] = "<a href='index.php?menu=$module_name&action=mostrar_editar&id_user=$user[0]'>"._tr("Edit")."</a>";
            $arrData[] = $arrTmp;
          }

        }
    }
    $oGrid->setData($arrData);

    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}

function mostrarFormularioAsignarMarcas($smarty, $module_name, $local_templates_dir, &$pDB, &$pACL, $arrConf)
{
    $arrFormDescanso = camposParaFormulario();
    $oForm = new paloForm($smarty,$arrFormDescanso);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");

    $id_user = getParameter("id_user");
    $arrUser = $pACL->getUsers($id_user);

    $arrMembership = $pACL->getMembership($id_user);
    $arrGroup = array_keys($arrMembership);

    $_DATA = [
      "usuario"       => $arrUser[0][1],
      "nombre"        => $arrUser[0][2],
      "grupo"         => $arrGroup[0],
      "id_supervisor" => $id_user
    ];

    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("MARCA_POR_ASIGNAR", _tr("Avaible Brands"));
    $smarty->assign("MARCAS_ASIGNADAS", _tr("Asigned Brands"));
    $smarty->assign("ADD", _tr("Add"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/formulario.tpl",_tr("Supervisor"), $_DATA);
    $content = "<div ng-app='SupervisorApp' ng-controller='MainCtrl' ng-init='obtenerInformacionSupervisor(". $id_user .")'>".$htmlForm."</div>";

    return $content;
  
}

function camposParaFormulario()
{
    $arrFields = array(
            "usuario"       => array( "LABEL"                  => _tr("User"),
                                      "REQUIRED"               => "no",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("disabled" => "disabled"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""

                                    ),
            "nombre"        => array( "LABEL"                  => _tr("Name"),
                                      "REQUIRED"               => "no",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("disabled" => "disabled"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            "grupo"         => array( "LABEL"                  => _tr("Group"),
                                      "REQUIRED"               => "no",
                                      "INPUT_TYPE"             => "TEXT",
                                      "INPUT_EXTRA_PARAM"      => array("disabled" => "disabled"),
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            "id_supervisor" => array( "LABEL"                  => _tr(""),
                                      "REQUIRED"               => "yes",
                                      "INPUT_TYPE"             => "HIDDEN",
                                      "INPUT_EXTRA_PARAM"      => "",
                                      "VALIDATION_TYPE"        => "text",
                                      "VALIDATION_EXTRA_PARAM" => ""
                                    ),
            );
    return $arrFields;
}

function obtenerInformacionSupervisor(&$pDB)
{

    $id_supervisor = getParameter("id_supervisor");

    $respuesta = [
      "estado"            => "error",
      "marcasAsignadas"   => [],
      "marcasPorAsignar"  => []
    ];

    if (isset($id_supervisor) & $id_supervisor != "") {

      $respuesta["estado"] = "success";

      $pSupervisores = new paloSantoSupervisores($pDB);

      $marcasAsignadasSupervisor = $pSupervisores->obtenerMarcasAsignadasSupervisor($id_supervisor);

      $marcasCreadas = obtenerMarcasCreadas($pDB);

      if (count($marcasAsignadasSupervisor) > 0 && is_array($marcasAsignadasSupervisor)) {

        foreach ($marcasAsignadasSupervisor as $marcaId => $marcaAsignada) {
          foreach ($marcasCreadas as $campo => $marcaCreada) {
            if($marcaAsignada["marca"] == $marcaCreada["marca"]) {
              $marcasAsignadasSupervisor[$marcaId]["nombre"] = $marcaCreada["nombre"];
              unset($marcasCreadas[$campo]);
              break;
            }
          }
        }

        $respuesta["marcasAsignadas"] = $marcasAsignadasSupervisor;

      }

      $marcasPorAsignar = $marcasCreadas;

      if (count($marcasPorAsignar) > 0 && is_array($marcasPorAsignar)) {
        $respuesta["marcasPorAsignar"] = array_values($marcasPorAsignar);
      } else {
        $respuesta["marcasPorAsignar"] = [];
      }

    }

    printJson($respuesta);
}

function asignarMarcaSupervisor(&$pDB)
{

    $id_supervisor = getParameter("id_supervisor");
    $marca = getParameter("marca");

    $respuesta = [
      "estado" => "error"
    ];

    if (isset($id_supervisor) & $id_supervisor != "" & isset($marca) & $marca != "") {

      $pSupervisores = new paloSantoSupervisores($pDB);

      if (!$pSupervisores->verificarMarcaAsignadaSupervisor($marca, $id_supervisor)) {
        if ($pSupervisores->asignarMarcaSupervisor($marca, $id_supervisor)) {
          $respuesta["estado"] = "success";
        } 
      }

    }

    printJson($respuesta);
}

function borrarMarcaSupervisor(&$pDB)
{

    $id_supervisor = getParameter("id_supervisor");
    $marca = getParameter("marca");

    $respuesta = [
      "estado" => "error"
    ];

    if (isset($id_supervisor) & $id_supervisor != "" & isset($marca) & $marca != "") {

      $pSupervisores = new paloSantoSupervisores($pDB);

      if ($pSupervisores->verificarMarcaAsignadaSupervisor($marca, $id_supervisor)) {
        if ($pSupervisores->borrarMarcaAsignadaSupervisor($marca, $id_supervisor)) {
          $respuesta["estado"] = "success";
        } 
      }

    }

    printJson($respuesta);
}

function obtenerMarcasCreadas(&$pDB)
{
    $pMarcas = new paloSantoMarcas($pDB);

    $totalMarcas = $pMarcas->getNumMarcas();
    $marcas = $pMarcas->getMarcas($totalMarcas);

    $arrOptionsMarcas = NULL;

    // Verificar si existen marcas creadas
    if ($totalMarcas > 0) {
      foreach ($marcas as $marca) {
        $arrOptionsMarcas[] = array(
          "marca" => $marca['prefijo'], 
          "nombre" => $marca['nombre']);
      }
    }

    return $arrOptionsMarcas;
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
    if(getParameter("guardar_editar"))
        return "guardar_editar";
    else if(getParameter("borrar")) 
        return "borrar";
    else if(getParameter("action")=="mostrar_editar")
        return "mostrar_formulario";
    else if(getParameter("action")=="obtener_informacion_supervisor")
        return "obtener_informacion_supervisor";
    else if(getParameter("action")=="asignar_marca")
        return "asignar_marca";
    else if(getParameter("action")=="borrar_marca")
        return "borrar_marca";
    else
        return "mostrar_supervisores"; //cancel
}
?>