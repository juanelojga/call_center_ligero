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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class paloSantoCDR
{

    public function __construct(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    private function _construirWhereCDR($param)
    {

        // condiciones de la lista desplegable
        switch ($param['field_name']) {
          case 'src':
            $whereFieldName = "(? IS NULL OR ? = \"\" OR cdr.src LIKE CONCAT('%',?,'%'))";
            break;

          case 'channel':
            $whereFieldName = "(? IS NULL OR ? = \"\" OR cdr.channel LIKE CONCAT('%',?,'%'))";
            break;

          case 'dstchannel':
            $whereFieldName = "(? IS NULL OR ? = \"\" OR cdr.dstchannel LIKE CONCAT('%',?,'%'))";
            break;
          
          default:
            $whereFieldName = "(? IS NULL OR ? = \"\" OR cdr.dst LIKE CONCAT('%',?,'%'))";
            break;
        }

        $arrParamFieldParam = [$param['field_pattern'], $param['field_pattern'], $param['field_pattern']];

        // condiciones para status
        if ($param['status'] != 'ALL') {
          $whereFieldName .= " AND (cdr.disposition = ?) ";
          $arrParamFieldParam[] = $param['status'];
        }

        if ($param['tipo'] == 'active') {
          $whereFieldName .= " AND (cdr.descanso IS NULL) ";
        } else if ($param['tipo'] == 'break') {
          $whereFieldName .= " AND (cdr.descanso IS NOT NULL) ";
        }

        // condiciones para filtrar por marca, campaña, agente, descanso
        $condicionesCallcenterLigero = $this->_condicionesCallcenterLigero($whereFieldName);
        $parametrosCallcenterLigero = $this->_parametrosCallcenterLigero($param);

        $where[0] = $condicionesCallcenterLigero;
        $where[1] = array_merge($arrParamFieldParam, $parametrosCallcenterLigero);

        return $where;
    }

    /* Procedimiento que ayuda a empaquetar los parámetros de las funciones 
     * viejas para compatibilidad */
    private function getParam($date_start="", $date_end="", $field_name="", $field_pattern="",$status="ALL",$calltype="",$troncales=NULL, $extension="")
    {
        $param = array();
        if (!empty($date_start)) $param['date_start'] = $date_start;
        if (!empty($date_end)) $param['date_end'] = $date_end;
        if (!empty($field_name)) $param['field_name'] = $field_name;
        if (!empty($field_pattern)) $param['field_pattern'] = $field_pattern;
        if (!empty($status) && $status != 'ALL') $param['status'] = $status;
        if (!empty($calltype)) $param['calltype'] = $calltype;
        if (!empty($troncales)) $param['troncales'] = $troncales;
        if (!empty($extension)) $param['extension'] = $extension;
        return $param;
    }

    private function _darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas)
    {
        if ($marcasAsignadas["esAdministrador"]) {
            
            $resultado = [
                "condicionSQL" => NULL,
                "parametrosSQL" => []
            ];

        } else {

            if (count($marcasAsignadas["marcas"]) > 0 && is_array($marcasAsignadas["marcas"])) {

                $condicionSQL = "AND (";

                foreach ($marcasAsignadas["marcas"] as $campo => $valor) {
                    
                    if ($campo) {
                        $condicionSQL .= " || cdr.marca = ?";
                    } else {
                        $condicionSQL .= " cdr.marca = ?";
                    }

                    $parametros[] = $valor["marca"];

                }

                $condicionSQL .= " )";
            
            } else {

                $condicionSQL = NULL;

                $parametros = array();

            }

            $resultado = [
                "condicionSQL" => $condicionSQL,
                "parametrosSQL" => $parametros
            ];

        }

        return $resultado;
    }

    private function _condicionesCallcenterLigero($whereFieldName)
    {

        $condicionSQL = <<<CONDICION_SQL
$whereFieldName
AND (DATE(calldate) BETWEEN ? AND ?)
AND (? IS NULL OR ? = "" OR IF (? = "",cdr.src LIKE CONCAT('%',?,'%'),cdr.src BETWEEN ? AND ?))
AND (? IS NULL OR ? = "" OR cdr.descanso LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ? = "" OR descansos.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ? = "" OR IF (? = "",cdr.agente LIKE CONCAT('%',?,'%'),cdr.agente BETWEEN ? AND ?))
AND (? IS NULL OR ? = "" OR agentes.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ? = "" OR IF (? = "",cdr.campana LIKE CONCAT('%',?,'%'),cdr.campana BETWEEN ? AND ?))
AND (? IS NULL OR ? = "" OR campanas.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ? = "" OR cdr.marca LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ? = "" OR marcas.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ? = "" OR IF (? = "",cdr.billsec >= ?,cdr.billsec BETWEEN ? AND ?))
CONDICION_SQL;

        return $condicionSQL;

    }

    private function _parametrosCallcenterLigero($param)
    {
        return [
            $param["date_start"], $param["date_end"],
            $param["extension_inicio"], $param["extension_inicio"], $param["extension_fin"], $param["extension_inicio"], $param["extension_inicio"], $param["extension_fin"],
            $param["descanso_prefijo"], $param["descanso_prefijo"], $param["descanso_prefijo"],
            $param["descanso_nombre"], $param["descanso_nombre"], $param["descanso_nombre"],
            $param["agente_numero_inicio"], $param["agente_numero_inicio"], $param["agente_numero_fin"], $param["agente_numero_inicio"], $param["agente_numero_inicio"], $param["agente_numero_fin"],
            $param["agente_nombre"], $param["agente_nombre"], $param["agente_nombre"],
            $param["campana_prefijo_inicio"], $param["campana_prefijo_inicio"], $param["campana_prefijo_fin"], $param["campana_prefijo_inicio"], $param["campana_prefijo_inicio"], $param["campana_prefijo_fin"],
            $param["campana_nombre"], $param["campana_nombre"], $param["campana_nombre"],
            $param["marca_prefijo"], $param["marca_prefijo"], $param["marca_prefijo"],
            $param["marca_nombre"], $param["marca_nombre"], $param["marca_nombre"],
            $param["billsec_inicio"], $param["billsec_inicio"], $param["billsec_fin"], $param["billsec_inicio"], $param["billsec_inicio"], $param["billsec_fin"],
        ];
    }

    /**
     * Procedimiento para listar los CDRs desde la tabla asterisk.cdr con varios
     * filtrados aplicados.
     *
     * @param   mixed   $param  Lista de parámetros de filtrado:
     *  date_start      Fecha y hora minima de la llamada, en formato 
     *                  yyyy-mm-dd hh:mm:ss. Si se omite, se lista desde la 
     *                  primera llamada.
     *  date_end        Fecha y hora máxima de la llamada, en formato 
     *                  yyyy-mm-dd hh:mm:ss. Si se omite, se lista hasta la 
     *                  última llamada.
     *  status          Estado de la llamada, guardado en el campo 'disposition'.
     *                  Si se especifica, puede ser uno de los valores siguientes:
     *                  ANSWERED, NO ANSWER, BUSY, FAILED
     *  calltype        Tipo de llamada. Se puede indicar "incoming" o "outgoing".
     *  troncales       Arreglo de troncales por el cual se debe filtrar las
     *                  llamadas según el valor almacenado en la columna 'channel'
     *                  o 'dstchannel', para calltype de tipo "incoming" o 
     *                  "outgoing", respectivamente. Se ignora si se omite un
     *                  valor para calltype.
     *  extension       Número de extensión para el cual filtrar los números. 
     *                  Este valor filtra por los campos 'src' y 'dst'.
     *  field_name
     *  field_pattern   Campo y subcadena para buscar dentro de los registros.
     *                  El valor de field_pattern puede ser un arreglo, o un
     *                  valor separado por comas, y buscará múltiples patrones.
     * @param   mixed   $limit  Máximo número de CDRs a leer, o NULL para todos
     * @param   mixed   $offset Inicio de lista de CDRs, si se especifica $limit
     *
     * @return  mixed   Estructura con los siguientes campos:
     *  total   integer     Número total de CDRs disponibles con los filtrados
     *  cdrs    mixed       Lista de los cdrs. Se devuelven los siguientes campos
     *                      en el orden en que se listan a continuación:
     *                      calldate, src, dst, channel, dstchannel, disposition, 
     *                      uniqueid, duration, billsec, accountcode
     */
    public function listarCDRs($param,$limit = NULL, $offset = 0, $marcasAsignadas)
    {
        $resultado = array();
        list($sWhere, $paramSQL) = $this->_construirWhereCDR($param);

        if (is_null($sWhere)) return NULL;

        $condicionMarcas = $this->_darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas);

        if ((count($condicionMarcas["parametrosSQL"]) > 0 && is_array($condicionMarcas["parametrosSQL"])) || $marcasAsignadas["esAdministrador"]) {

            $condicionSQLMarcas = $condicionMarcas["condicionSQL"];

            $paramSQL = array_merge($paramSQL, $condicionMarcas["parametrosSQL"]);

            $sPeticionSQL = <<<SENTENCIA_SQL
SELECT 
calldate, 
src, 
dst, 
channel, 
dstchannel, 
disposition,
uniqueid, 
duration, 
billsec,
recordingfile, 
cdr.marca AS marca_prefijo,
marcas.nombre AS marca_nombre, 
cdr.campana AS campana_prefijo,
campanas.nombre AS campana_nombre, 
cdr.agente AS agente_numero,
agentes.nombre AS agente_nombre,
cdr.descanso AS descanso_prefijo,
descansos.nombre AS descanso_nombre
FROM cdr
LEFT JOIN `ccl_ligero`.`descansos` ON cdr.descanso = descansos.prefijo
LEFT JOIN `ccl_ligero`.`marcas` ON cdr.marca = marcas.prefijo
LEFT JOIN `ccl_ligero`.`campanas` ON cdr.campana = campanas.prefijo
LEFT JOIN `ccl_ligero`.`agentes` ON cdr.agente = agentes.numero
WHERE
$sWhere
$condicionSQLMarcas
ORDER BY calldate DESC
SENTENCIA_SQL;

            if (!empty($limit)) {
                $sPeticionSQL .= " LIMIT ? OFFSET ?";
                array_push($paramSQL, $limit, $offset);
            }
            $resultado = $this->_DB->fetchTable($sPeticionSQL, TRUE, $paramSQL);
            if (!is_array($resultado)) {
                $this->errMsg = '(internal) Failed to fetch CDRs - '.$this->_DB->errMsg;
                return NULL;
            }
            return $resultado;
        } else {
            return NULL;
        }
    }

    /**
     * Procedimiento para contar los CDRs desde la tabla asterisk.cdr con varios
     * filtrados aplicados. Véase listarCDRs para los parámetros conocidos.
     *
     * @param   mixed   $param  Lista de parámetros de filtrado.
     * 
     * @return  mixed   NULL en caso de error, o número de CDRs del filtrado
     */
    public function contarCDRs($param, $marcasAsignadas)
    {
        list($sWhere, $paramSQL) = $this->_construirWhereCDR($param);
        if (is_null($sWhere)) return NULL;

        $condicionMarcas = $this->_darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas);

        if ((count($condicionMarcas["parametrosSQL"]) > 0 && is_array($condicionMarcas["parametrosSQL"])) || $marcasAsignadas["esAdministrador"]) {

            $condicionSQLMarcas = $condicionMarcas["condicionSQL"];

            $paramSQL = array_merge($paramSQL, $condicionMarcas["parametrosSQL"]);

            $sPeticionSQL = <<<SENTENCIA_SQL
SELECT
COUNT(src) 
FROM cdr
LEFT JOIN `ccl_ligero`.`descansos` ON cdr.descanso = descansos.prefijo
LEFT JOIN `ccl_ligero`.`marcas` ON cdr.marca = marcas.prefijo
LEFT JOIN `ccl_ligero`.`campanas` ON cdr.campana = campanas.prefijo
LEFT JOIN `ccl_ligero`.`agentes` ON cdr.agente = agentes.numero
WHERE
$sWhere
$condicionSQLMarcas
SENTENCIA_SQL;

            $r = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE, $paramSQL);
            if (!is_array($r)) {
                $this->errMsg = '(internal) Failed to count CDRs - '.$this->_DB->errMsg;
                return NULL;
            }

            return $r[0];

        } else {
            return 0;
        }

    }

    // Función de compatibilidad para código antiguo
    public function getNumCDR($date_start="", $date_end="", $field_name="", $field_pattern="",$status="ALL",$calltype="",$troncales=NULL, $extension="")
    {
        $param = $this->getParam($date_start,$date_end,$field_name,$field_pattern,$status,$calltype,$troncales,$extension);
        return $this->contarCDRs($param);
    }
    
    /**
     * Procedimiento para borrar los CDRs en la tabla asterisk.cdr que coincidan
     * con los filtros indicados.
     * @param   mixed   $param  Lista de parámetros de filtrado. Véase listarCDRs
     *                          para los parámetros permitidos.
     *
     * @return  bool    VERDADERO en caso de éxito, FALSO en caso de error.
     */
    public function borrarCDRs($param)
    {
        list($sWhere, $paramSQL) = $this->_construirWhereCDR($param);
        if (is_null($sWhere)) return NULL;

        // Borrado de los registros seleccionados
        $sPeticionSQL = 
            'DELETE cdr FROM cdr '. $sWhere;
        $r = $this->_DB->genQuery($sPeticionSQL, $paramSQL);
        if (!$r) {
            $this->errMsg = '(internal) Failed to delete CDRs - '.$this->_DB->errMsg;
        }
        return $r;
    }

    // Función de compatibilidad para código antiguo
    public function obtenerCDRs($limit, $offset, $date_start="", $date_end="", $field_name="", $field_pattern="",$status="ALL",$calltype="",$troncales=NULL, $extension="")
    {
        $param = $this->getParam($date_start, $date_end, $field_name, $field_pattern,$status,$calltype,$troncales, $extension);
        $r = $this->listarCDRs($param, $limit, $offset);
        return is_array($r) 
            ? array(
                'NumRecords'    =>  array($r['total']),
                'Data'          =>  $r['cdrs'],
                )
            : NULL;
    }

    // Función de compatibilidad para código antiguo
    public function Delete_All_CDRs($date_start="", $date_end="", $field_name="", $field_pattern="",$status="ALL",$calltype="",$troncales=NULL, $extension="")
    {
        $param = $this->getParam($date_start, $date_end, $field_name, $field_pattern,$status,$calltype,$troncales, $extension);
        return $this->borrarCDRs($param);
    }

}
?>