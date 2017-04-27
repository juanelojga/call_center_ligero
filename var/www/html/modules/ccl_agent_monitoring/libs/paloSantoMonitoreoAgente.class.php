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
  $Id: paloSantoDescansos.class.php,v 1.1 2016-02-23 09:02:30 Juan Almeida jalmeida@palosanto.com Exp $ */
class paloSantoMonitoreoAgente{
    var $_DB;
    var $errMsg;

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

    protected function _darFormatoCondicionBusqueda()
    {
      $condicionSQL = <<<CONDICION_SQL
WHERE
(? IS NULL OR se.extension LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR se.agente LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ag.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ag.campana LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ca.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ca.marca LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ma.nombre LIKE CONCAT('%',?,'%'))
AND (se.fecha_fin IS NULL)
CONDICION_SQL;

      return $condicionSQL;
    }

    protected function _darFormatoParametrosBusqueda($parametros = [])
    {
      return [
        $parametros["extension"], $parametros["extension"],
        $parametros["agente_numero"], $parametros["agente_numero"],
        $parametros["agente_nombre"], $parametros["agente_nombre"],
        $parametros["campana_prefijo"], $parametros["campana_prefijo"],
        $parametros["campana_nombre"], $parametros["campana_nombre"],
        $parametros["marca_prefijo"], $parametros["marca_prefijo"],
        $parametros["marca_nombre"], $parametros["marca_nombre"],
      ];
    }

    protected function _darFormatoJoinTablas()
    {
      $joinsSQL = <<<JOINS_SQL
FROM sesiones AS se
INNER JOIN agentes AS ag ON se.agente = ag.numero
INNER JOIN campanas AS ca ON ag.campana = ca.prefijo
INNER JOIN marcas AS ma ON ca.marca = ma.prefijo
JOINS_SQL;

      return $joinsSQL;
    }

    protected function _darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas)
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
                        $condicionSQL .= " || ca.marca = ?";
                    } else {
                        $condicionSQL .= " ca.marca = ?";
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

    // Obtener la cantidad de Descansos
    public function obtenerTotalAgentesConectados($parametros, $marcasAsignadas)
    {
        $joinsSQL = $this->_darFormatoJoinTablas();

        $condicionSQL = $this->_darFormatoCondicionBusqueda();

        $parametrosBusqueda = $this->_darFormatoParametrosBusqueda($parametros);

        $condicionMarcas = $this->_darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas);

        if ((count($condicionMarcas["parametrosSQL"]) > 0 && is_array($condicionMarcas["parametrosSQL"])) || $marcasAsignadas["esAdministrador"]) {

            $condicionSQLMarcas = $condicionMarcas["condicionSQL"];

            $parametrosBusqueda = array_merge($parametrosBusqueda, $condicionMarcas["parametrosSQL"]);

            $sentenciaSQL = <<<SENTENCIA_SQL
SELECT
COUNT(se.extension)
$joinsSQL
$condicionSQL
$condicionSQLMarcas
SENTENCIA_SQL;

            $result=$this->_DB->getFirstRowQuery($sentenciaSQL, false, $parametrosBusqueda);

            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return 0;
            }
            return $result[0];

        } else {
            return 0;
        }
    }

    public function obtenerAgentesConectados($limit, $offset, $parametros, $marcasAsignadas)
    {
        $joinsSQL = $this->_darFormatoJoinTablas();

        $condicionSQL = $this->_darFormatoCondicionBusqueda();

        $parametrosBusqueda = $this->_darFormatoParametrosBusqueda($parametros);

        $condicionMarcas = $this->_darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas);

        if ((count($condicionMarcas["parametrosSQL"]) > 0 && is_array($condicionMarcas["parametrosSQL"])) || $marcasAsignadas["esAdministrador"]) {

            $condicionSQLMarcas = $condicionMarcas["condicionSQL"];

            $parametrosBusqueda = array_merge($parametrosBusqueda, $condicionMarcas["parametrosSQL"]);

            $sentenciaSQL = <<<SENTENCIA_SQL
SELECT
se.id,
se.extension,
se.fecha_inicio AS inicio_sesion,
se.agente AS agente_numero,
ag.nombre AS agente_nombre,
ag.campana AS campana_prefijo,
ca.nombre AS campana_nombre,
ca.marca AS marca_prefijo,
ma.nombre AS marca_nombre
$joinsSQL
$condicionSQL
$condicionSQLMarcas
ORDER BY se.fecha_inicio
LIMIT $limit
OFFSET $offset
SENTENCIA_SQL;

            $result=$this->_DB->fetchTable($sentenciaSQL, true, $parametrosBusqueda);

            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return 0;
            }
            return $result;

        } else {

            return 0;

        }
    }

    function obtenerInformacionSesionPorId($id)
    {
        $arrParam = [$id];

        $sentenciaSQL = <<<SENTENCIA_SQL
SELECT
se.id AS sesion_id
FROM sesiones AS se
WHERE
se.id = ?
AND (se.fecha_fin IS NULL)
SENTENCIA_SQL;

        $result=$this->_DB->getFirstRowQuery($sentenciaSQL, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    public function terminarDescansoTomado($login_logout_id)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($login_logout_id) & $login_logout_id !="") {
            $set      = "fecha_fin = NOW(), motivo_desconexion = ?";
            $where    = "login_logout_id = ? AND fecha_fin IS NULL";
            $arrParam = array('supervisor', $login_logout_id);
        }
          
        $query = "UPDATE descansos_tomados SET $set WHERE $where";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }

    public function terminarSesion($sesion)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($sesion) & $sesion !="") {
            $set      = "fecha_fin = NOW(), motivo_desconexion = ?";
            $where    = "id = ?";
            $arrParam = array('supervisor', $sesion);
        }
          
        $query = "UPDATE sesiones SET $set WHERE $where";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }
}
