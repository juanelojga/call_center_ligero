<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:21 gcarrillo Exp $ */

ini_set('max_execution_time', 300);

include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "modules/ccl_cdrreport/libs/paloSantoCDR.class.php";
include_once "libs/misc.lib.php";
include_once "modules/monitoring/libs/paloSantoMonitoring.class.php";
include_once "libs/paloSantoACL.class.php";
include_once "modules/ccl_supervisors/libs/paloSantoSupervisores.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // DSN para consulta de cdrs
    $dsn = generarDSNSistema('root', 'asteriskcdrdb');
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
        case 'download':
            $content = downloadFile($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $esAdministrador, $userId);
            break;
        case 'display_masive':
            $content = viewFormMasiveDownload($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $esAdministrador, $userId);
            break;
        case 'get_total':
            $content = getTotalCdrs($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $esAdministrador, $userId);
            break;
        case 'masive':
            $content = downloadMasive($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $esAdministrador, $userId);
            break;
        default:
            $content = reportCDR($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $esAdministrador, $userId);
            break;
    }
    return $content;
}

function reportCDR($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $esAdministrador, $userId)
{
    $oCDR    = new paloSantoCDR($pDB);

    $arrFormElements = construirCamposFormulario();

    // Parámetros base y validación de parámetros
    $url = array('menu' => $module_name);

    $paramFiltroBase = $paramFiltro = getFilterDefaultValues();

    $paramFiltro = getFilterValues($paramFiltro);

    // descarga masiva de grabaciones
    $masiveUrl = array(
        'menu'      =>  $module_name,
        'action'    =>  'display_masive',
        );

    // link para descargar de forma masiva archivos de audio
    $masiveLink = "<a href='?".http_build_query($masiveUrl)."' >"._tr("Download all recordings")."</a>";


    // Cadenas estáticas en la plantilla
    $smarty->assign(array(
        "Filter"    =>  _tr("Filter"),
        "Masive"    =>  $masiveLink,
    ));

    $oFilterForm = new paloForm($smarty, $arrFormElements);

    $oGrid  = new paloSantoGrid($smarty);
    if($paramFiltro['date_start']==="")
        $paramFiltro['date_start']  = " ";


    if($paramFiltro['date_end']==="")
        $paramFiltro['date_end']  = " ";


    $valueFieldName = $arrFormElements['field_name']["INPUT_EXTRA_PARAM"][$paramFiltro['field_name']];
    $valueStatus = $arrFormElements['status']["INPUT_EXTRA_PARAM"][$paramFiltro['status']];

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Start Date")." = ".$paramFiltro['date_start'].", "._tr("End Date")." = ".
    $paramFiltro['date_end'], $paramFiltro, array('date_start' => date("d M Y"),'date_end' => date("d M Y")),true);

    $oGrid->addFilterControl(_tr("Filter applied: ").$valueFieldName." = ".$paramFiltro['field_pattern'],$paramFiltro, array('field_name' => "dst",'field_pattern' => ""));

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Status")." = ".$valueStatus,$paramFiltro, array('status' => 'ALL'),true);

    $paramFiltroCCLParameters = array_slice($arrFormElements, 6);
    foreach ($paramFiltroCCLParameters as $campo => $valor) {
        $oGrid->addFilterControl(_tr("Filter applied: ")._tr($valor["LABEL"])." = ".$paramFiltro[$campo],$paramFiltro, array($campo => ''));
    }

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl", "", $paramFiltro);

    $url = array_merge($url, $paramFiltro);
    
    $oGrid->setTitle(_tr("CDR Report"));
    $oGrid->pagingShow(true); // show paging section.

    $oGrid->enableExport();   // enable export.
    $oGrid->setNameFile_Export(_tr("CDRReport"));
    $oGrid->setURL($url);

    $arrData = null;

    $marcasAsignadas = obtenerMarcasAsignadas($esAdministrador, $userId);

	$total = $oCDR->contarCDRs($paramFiltro, $marcasAsignadas);

    if($oGrid->isExportAction()){
        $limit = $total;
        $offset = 0;
        
        $arrColumns = columnasTituloTabla();
        $arrColumns = array_slice($arrColumns, 0, 11);

        $oGrid->setColumns($arrColumns);

	    $arrResult = $oCDR->listarCDRs($paramFiltro, $limit, $offset, $marcasAsignadas);
 
        if(is_array($arrResult) && $total>0){
            foreach($arrResult as $key => $value){

                if ($value["descanso_prefijo"]) {
                    $estado = _tr("On Break") . ": " . $value["descanso_nombre"];
                } else {
                    $estado = _tr("Active");
                }

                $arrTmp[0] = $value["calldate"];
                $arrTmp[1] = $value["src"];
                $arrTmp[2] = $value["dst"];
                $arrTmp[3] = $value["dstchannel"];
                $arrTmp[4] = gmdate("H:i:s", $value["billsec"]);
                $arrTmp[5] = $value["marca_nombre"];
                $arrTmp[6] = $value["campana_nombre"];
                $arrTmp[7] = $value["agente_numero"];
                $arrTmp[8] = $value["agente_nombre"];
                $arrTmp[9] = $estado;
                $arrTmp[10] = _tr($value["disposition"]);

                $arrData[] = $arrTmp;
            }
        }

        if (!is_array($arrResult)) {
            $smarty->assign(array(
                'mb_title'      =>  _tr('ERROR'),
                'mb_message'    =>  $oCDR->errMsg,
            ));
        }
    }else {
        $limit = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);

        $offset = $oGrid->calculateOffset();

        $arrResult = $oCDR->listarCDRs($paramFiltro, $limit, $offset, $marcasAsignadas);
       
        $arrColumns = columnasTituloTabla();

        $oGrid->setColumns($arrColumns);

        if(is_array($arrResult) && $total>0){
            foreach($arrResult as $key => $value){

                if ($value["descanso_prefijo"]) {
                    $estado = _tr("On Break") . ": " . $value["descanso_nombre"];
                } else {
                    $estado = _tr("Active");
                }

                $arrTmp[0] = $value["calldate"];
                $arrTmp[1] = $value["src"];
                $arrTmp[2] = $value["dst"];
                $arrTmp[3] = $value["dstchannel"];
                $arrTmp[4] = gmdate("H:i:s", $value["billsec"]);
                $arrTmp[5] = $value["marca_nombre"];
                $arrTmp[6] = $value["campana_nombre"];
                $arrTmp[7] = $value["agente_numero"];
                $arrTmp[8] = $value["agente_nombre"];
                $arrTmp[9] = $estado;
                $arrTmp[10] = _tr($value["disposition"]);
                if ($value["billsec"] > 0 && $value["recordingfile"] != "") {
                    $file = $value['uniqueid'];
                    $namefile = basename($value['recordingfile']);
                    $urlparams = array(
                        'menu'      =>  $module_name,
                        'id'        =>  $file,
                        'namefile'  =>  $namefile,
                        'rawmode'   =>  'yes',
                        'action'    =>  'download'
                    );
                    $recordingLink = "<a href='?".http_build_query($urlparams)."' >"._tr("Download")."</a>";
                } else {
                    $recordingLink = "";
                }

                $arrTmp[11] = $recordingLink;

                $arrData[] = $arrTmp;
            }
        }
        if (!is_array($arrResult)) {
        $smarty->assign(array(
            'mb_title'      =>  _tr('ERROR'),
            'mb_message'    =>  $oCDR->errMsg,
        ));
        }
    }
    $oGrid->setData($arrData);
    $oGrid->showFilter($htmlFilter);
    $content = $oGrid->fetchGrid();
    return $content;

}

function construirCamposFormulario()
{
    return array(
        "date_start"        => array(   "LABEL"                  => _tr("Start Date"),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "DATE",
                                        "INPUT_EXTRA_PARAM"      => array("TIME"=> FALSE, "FORMAT" => "%Y-%m-%d"),
                                        "VALIDATION_TYPE"        => "ereg",
                                        "VALIDATION_EXTRA_PARAM" => "^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$"),
        
        "date_end"          => array(   "LABEL"                  => _tr("End Date"),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "DATE",
                                        "INPUT_EXTRA_PARAM"      => array("TIME"=> FALSE, "FORMAT" => "%Y-%m-%d"),
                                        "VALIDATION_TYPE"        => "ereg",
                                        "VALIDATION_EXTRA_PARAM" => "^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$"),
        
        "field_name"        => array(   "LABEL"                  => _tr("Field Name"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => array( "dst"         => _tr("Destination"),
                                                                           "channel"     => _tr("Src. Channel"),
                                                                           "dstchannel"  => _tr("Dst. Channel")),
                                        "VALIDATION_TYPE"        => "ereg",
                                        "VALIDATION_EXTRA_PARAM" => "^(dst|src|channel|dstchannel|accountcode)$"),
        
        "field_pattern"     => array(   "LABEL"                  => _tr("Field"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "ereg",
                                        "VALIDATION_EXTRA_PARAM" => "^[\*|[:alnum:]@_\.,\/\-]+$"),

        "tipo"                  => array("LABEL"                  => _tr("Type"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => array(
                                                                    "ALL"      => _tr("ALL"),
                                                                    "active"   => _tr("Active"),
                                                                    "break"    => _tr("On Break")),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),

        "extension_inicio"  => array(   "LABEL"                  => _tr("Extension"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),

        "extension_fin"     => array(   "LABEL"                  => _tr("Extension"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "status"            => array(   "LABEL"                  => _tr("Status"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "SELECT",
                                        "INPUT_EXTRA_PARAM"      => array(
                                                                    "ALL"         => _tr("ALL"),
                                                                    "ANSWERED"    => _tr("ANSWERED"),
                                                                    "BUSY"        => _tr("BUSY"),
                                                                    "FAILED"      => _tr("FAILED"),
                                                                    "NO ANSWER "  => _tr("NO ANSWER")),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),        
        "descanso_prefijo"  => array(   "LABEL"                  => _tr("Break Prefix"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "descanso_nombre"   => array(   "LABEL"                  => _tr("Break Name"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "marca_prefijo"     => array(   "LABEL"                  => _tr("Brand Prefix"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "marca_nombre"      => array(   "LABEL"                  => _tr("Brand Name"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "campana_prefijo_inicio"=> array("LABEL"                  => _tr("Campaign Prefix"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),

        "campana_prefijo_fin"   => array("LABEL"                  => _tr("Campaign Prefix"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "campana_nombre"        => array("LABEL"                  => _tr("Campaign Name"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "agente_numero_inicio"  => array( "LABEL"                  => _tr("Agent Number"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),

        "agente_numero_fin"     => array("LABEL"                 => _tr("Agent Number"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),
        
        "agente_nombre"         => array("LABEL"                 => _tr("Agent Name"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => "",
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),

        "billsec_inicio"        => array("LABEL"                 => _tr("Duration"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),

        "billsec_fin"           => array("LABEL"                 => _tr("Duration"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("style" => "width:65px"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => ""),        

    );
}

function getFilterDefaultValues()
{
    return array(
        'date_start'                => date("Y-m-d"),
        'date_end'                  => date("Y-m-d"),
        'field_name'                => 'dst',
        'field_pattern'             => '',
        'extension_inicio'          => NULL,
        'extension_fin'             => NULL,
        'status'                    => 'ALL',
        'descanso_prefijo'          => NULL,
        'descanso_nombre'           => NULL,
        'agente_numero_inicio'      => NULL,
        'agente_numero_fin'         => NULL,
        'agente_nombre'             => NULL,
        'campana_prefijo_inicio'    => NULL,
        'campana_prefijo_fin'       => NULL,
        'campana_nombre'            => NULL,
        'marca_prefijo'             => NULL,
        'marca_nombre'              => NULL,
        'billsec_inicio'            => NULL,
        'billsec_fin'               => NULL,
        'tipo'                      => 'ALL',
    );
}

function getFilterValues($paramFiltro)
{
    foreach (array_keys($paramFiltro) as $k) {
        if (!is_null(getParameter($k))){
            $paramFiltro[$k] = getParameter($k);
        }
    }

    return $paramFiltro;
}

function columnasTituloTabla()
{
    return [
        _tr("Date"), 
        _tr("Source"), 
        _tr("Destination"), 
        _tr("Dst. Channel"),
        _tr("Duration"),
        _tr("Brand Name"),
        _tr("Campaign Name"),
        _tr("Agent Number"),
        _tr("Agent Name"),
        _tr("Status"),
        _tr("Type"),
        _tr("Recording")
    ];

}

function obtenerMarcasAsignadas($esAdministrador, $userId)
{
  if ($esAdministrador) {
    
    $resultado = [
      "esAdministrador" => 1,
      "marcas" => NULL
    ];

  } else {

    $dsn = generarDSNSistema('root', 'ccl_ligero');

    $pDB = new paloDB($dsn);

    $pSupervisores = new paloSantoSupervisores($pDB);

    $marcas = $pSupervisores->obtenerMarcasAsignadasSupervisor($userId);

    $resultado = [
      "esAdministrador" => 0,
      "marcas" => $marcas
    ];

  }

  return $resultado;
}

function downloadFile($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $esAdministrador, $userId)
{
    $record = getParameter("id");
    $namefile = getParameter('namefile');

    $pMonitoring = new paloSantoMonitoring($pDB);

    $path_record = $arrConf['records_dir'];

    if (is_null($record) || !preg_match('/^[[:digit:]]+\.[[:digit:]]+$/', $record)) {
        // Missing or invalid uniqueid
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    // Check record is valid and points to an actual file
    $filebyUid = $pMonitoring->getAudioByUniqueId($record, $namefile);
    if (is_null($filebyUid) || count($filebyUid) <= 0) {
        // Uniqueid does not point to a record with specified file
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    $file = basename($filebyUid['recordingfile']);
    $path = $path_record.$file;
    if ($file == 'deleted') {
        // Specified file has been deleted
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    if (!file_exists($path)) {
        // Queue recordings might lack an extension
        $arrData = glob("$path*");
        if (count($arrData) > 0) {
            $path = $arrData[0];
            $file = basename($path);
        }
    }

    if (file_exists($path) && is_file($path)) {
        $ok_path = $path;
    } else {
        $path2 = $path_record.getPathFile($file);
        if (file_exists($path2) && is_file($path2)) {
            $ok_path = $path2;
        } else {
            // Failed to find specified file
            Header('HTTP/1.1 404 Not Found');
            die("<b>404 "._tr("no_file")." </b>");
        }
    }

    // Set Content-Type according to file extension
    $contentTypes = array(
        'wav'   =>  'audio/wav',
        'gsm'   =>  'audio/gsm',
        'mp3'   =>  'audio/mpeg',
    );

    $extension = substr(strtolower($file), -3);
    if (!isset($contentTypes[$extension])) {
        // Unrecognized file extension
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    // Actually open and transmit the file
    $fp = fopen($ok_path, 'rb');
    if (!$fp) {
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: wav file");
    header("Content-Type: " . $contentTypes[$extension]);
    header("Content-Disposition: attachment; filename=" . $file);
    header("Content-Transfer-Encoding: binary");
    header("Content-length: " . filesize($ok_path));
    fpassthru($fp);
    fclose($fp);
}

function getPathFile($file)
{
    $arrTokens = explode('-',$file);
    if (count($arrTokens) < 4) return '/'.$file;
    $fyear     = substr($arrTokens[3],0,4);
    $fmonth    = substr($arrTokens[3],4,2);
    $fday      = substr($arrTokens[3],6,2);
    return  "$fyear/$fmonth/$fday/$file";
}

function viewFormMasiveDownload($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $esAdministrador, $userId)
{
    // crear el objeto que realizara la busqueda de las grabaciones
    $oCDR    = new paloSantoCDR($pDB);

    $oForm = new paloForm($smarty,array());

    //begin, Form data persistence to errors and other events.
    $_DATA  = array();

    // asignacion de valores para la vista
    $smarty->assign("FECHA_INICIO_LABEL", _tr("Start Date"));

    $smarty->assign("FECHA_FIN_LABEL", _tr("End Date"));

    $smarty->assign("STATUS_LABEL", _tr("Status"));

    $smarty->assign("FIELD_NAME", _tr("Filter"));

    $smarty->assign("AGENTE_NUMERO", _tr("Agent Number"));
    $smarty->assign("AGENTE_NOMBRE", _tr("Agent Name"));
    $smarty->assign("CAMPANA_PREFIJO", _tr("Campaign Prefix"));
    $smarty->assign("CAMPANA_NOMBRE", _tr("Campaign Name"));
    $smarty->assign("MARCA_PREFIJO", _tr("Brand Prefix"));
    $smarty->assign("MARCA_NOMBRE", _tr("Brand Name"));
    $smarty->assign("EXTENSION", _tr("Extension"));
    $smarty->assign("TIPO", _tr("Type"));
    $smarty->assign("DURACION", _tr("Duration"));

    $smarty->assign("PARTS_LABEL", _tr("Number of files per part"));
    $smarty->assign("PARTS_CALCULATED", _tr("Number of parts"));

    $smarty->assign("FILES", _tr("Files"));
    $smarty->assign("TOTAL", _tr("Number of recordings"));

    $smarty->assign("SAVE", _tr("Generate files"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/masive.tpl",_tr("Masive download"), $_DATA);
    $content = $htmlForm;

    return $content;

}

function downloadMasive($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $esAdministrador, $userId)
{
    // crear el objeto que realizara la busqueda de las grabaciones
    $oCDR    = new paloSantoCDR($pDB);
    $pMonitoring = new paloSantoMonitoring($pDB);

    // directorio en donde se almacenan grabaciones
    $path_record = $arrConf['records_dir'];

    // valores por defecto del filtro
    $paramFiltro = getFilterDefaultValues();

    // valores de la busqueda
    $paramFiltro = getFilterValues($paramFiltro);

    // comparar las fechas recibidas
    $date1 = new DateTime($paramFiltro['date_start']);
    $date2 = new DateTime($paramFiltro['date_end']);

    // Si la fecha1 (inicio) es mayor a la fecha2 (fin)
    // finaliza la ejecución
    if ($date1 > $date2) {
        // Missing or invalid dates
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("invalid_dates")." </b>");
    }

    // dar formato a valores necesarios para la busqueda
    $paramFiltro['date_start'] = $date1->format('Y-m-d 00:00:00');
    $paramFiltro['date_end'] = $date2->format('Y-m-d 23:59:59');
    $paramFiltro['status'] = 'ANSWERED';

    $limit = getParameter('limit');
    $offset = getParameter('offset');

    $marcasAsignadas = obtenerMarcasAsignadas($esAdministrador, $userId);

    // obtener los registros
    $arrResult = $oCDR->listarCDRs($paramFiltro, $limit, $offset, $marcasAsignadas);

    if (is_array($arrResult) && count($arrResult)) {

        // timestamp para el nombre del archivo
        $timestamp = new DateTime();

        $number = rand(10000, 99999);

            // nombre del archivo de descarga masiva de audio
        $aFileName = $arrConf['masive_dir'] . 'ccl_recordings_' . $number . '_' . $timestamp->getTimestamp() . '.zip';

        $a = new PharData($aFileName);

        // analizar cada uno de los registros obtenidos en la busqueda
        foreach ($arrResult as $result) {
                
            $record = $result['uniqueid'];
            $namefile = basename($result['recordingfile']);

            // verificar el uniqueid
            if (is_null($record) || !preg_match('/^[[:digit:]]+\.[[:digit:]]+$/', $record)) {
                continue;
            }

            // Check record is valid and points to an actual file
            $filebyUid = $result['recordingfile'];
            if (is_null($filebyUid) || count($filebyUid) <= 0) {
                continue;
            }

            $file = basename($filebyUid);
            $path = $path_record.$file;
            if ($file == 'deleted') {
                continue;
            }

            if (!file_exists($path)) {
                // Queue recordings might lack an extension
                $arrData = glob("$path*");
                if (count($arrData) > 0) {
                    $path = $arrData[0];
                    $file = basename($path);
                }
            }

            if (file_exists($path) && is_file($path)) {
                $ok_path = $path;
            } else {
                $path2 = $path_record.getPathFile($file);
                if (file_exists($path2) && is_file($path2)) {
                    $ok_path = $path2;
                } else {
                    continue;
                }
            }

            // Set Content-Type according to file extension
            $contentTypes = array(
                'wav'   =>  'audio/wav',
                'gsm'   =>  'audio/gsm',
                'mp3'   =>  'audio/mpeg',
            );

            $extension = substr(strtolower($file), -3);
            if (!isset($contentTypes[$extension])) {
                continue;
            }

            $a->addFile($ok_path, $namefile);
        }

        $aFileNameRelative = basename($aFileName);

        $response = [
            'message' => 'success',
            'file' => $aFileNameRelative,
            'url' => "/masive/" . $aFileNameRelative,
        ];

    } else {

        $response = [
                'message' => 'error',
            ];

    }

    echo json_encode($response);

    exit();

}

function getTotalCdrs($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $esAdministrador, $userId)
{
    // crear el objeto que realizara la busqueda de las grabaciones
    $oCDR    = new paloSantoCDR($pDB);

    // valores por defecto del filtro
    $paramFiltro = getFilterDefaultValues();

    // valores de la busqueda
    $paramFiltro = getFilterValues($paramFiltro);

    // comparar las fechas recibidas
    $date1 = new DateTime($paramFiltro['date_start']);
    $date2 = new DateTime($paramFiltro['date_end']);

    // Si la fecha1 (inicio) es mayor a la fecha2 (fin)
    // finaliza la ejecución
    if ($date1 > $date2) {
        // Missing or invalid dates
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("invalid_dates")." </b>");
    }

    // dar formato a valores necesarios para la busqueda
    $paramFiltro['date_start'] = $date1->format('Y-m-d 00:00:00');
    $paramFiltro['date_end'] = $date2->format('Y-m-d 23:59:59');
    $paramFiltro['status'] = 'ANSWERED';

    $marcasAsignadas = obtenerMarcasAsignadas($esAdministrador, $userId);

    $total = $oCDR->contarCDRs($paramFiltro, $marcasAsignadas);

    echo json_encode(['total' => $total]);

    exit();
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("action")=="display_record")
        return "display_record";
    else if(getParameter("action")=="display_masive")
        return "display_masive";
    else if(getParameter("action")=="get_total")
        return "get_total";
    else if(getParameter("submit_eliminar"))
        return "delete";
    else if(getParameter("action")=="download")
        return "download";
    else if(getParameter("action")=="masive")
        return "masive";
    else if(getParameter("action")=="view")   //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else
        return "report"; //cancel
}
?>