<?php
class paloSantoReporteDescansos{
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
(DATE(dt.fecha_inicio) BETWEEN ? AND ?)
AND (? IS NULL OR dt.motivo_desconexion LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR dt.descanso LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR de.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR IF (? IS NULL,se.agente LIKE CONCAT('%',?,'%'),se.agente BETWEEN ? AND ?))
AND (? IS NULL OR ag.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR IF (? IS NULL,ag.campana LIKE CONCAT('%',?,'%'),ag.campana BETWEEN ? AND ?))
AND (? IS NULL OR ca.nombre LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ca.marca LIKE CONCAT('%',?,'%'))
AND (? IS NULL OR ma.nombre LIKE CONCAT('%',?,'%'))
CONDICION_SQL;

      return $condicionSQL;
    }

    protected function _darFormatoParametrosBusqueda($parametros = [])
    {
      return [
        $parametros["fecha_inicio"], $parametros["fecha_fin"],
        $parametros["motivo_desconexion"], $parametros["motivo_desconexion"],
        $parametros["descanso_prefijo"], $parametros["descanso_prefijo"],
        $parametros["descanso_nombre"], $parametros["descanso_nombre"],
        $parametros["agente_numero_inicio"], $parametros["agente_numero_fin"], $parametros["agente_numero_inicio"], $parametros["agente_numero_inicio"], $parametros["agente_numero_fin"],
        $parametros["agente_nombre"], $parametros["agente_nombre"],
        $parametros["campana_prefijo_inicio"], $parametros["campana_prefijo_fin"], $parametros["campana_prefijo_inicio"], $parametros["campana_prefijo_inicio"], $parametros["campana_prefijo_fin"],
        $parametros["campana_nombre"], $parametros["campana_nombre"],
        $parametros["marca_prefijo"], $parametros["marca_prefijo"],
        $parametros["marca_nombre"], $parametros["marca_nombre"],
      ];
    }

    protected function _darFormatoJoinTablas()
    {
      $joinsSQL = <<<JOINS_SQL
FROM descansos_tomados AS dt
INNER JOIN descansos AS de ON dt.descanso = de.prefijo
INNER JOIN sesiones AS se ON se.id = dt.login_logout_id
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
    public function obtenerTotalRegistros($parametros, $marcasAsignadas)
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
COUNT(dt.fecha_inicio)
$joinsSQL
$condicionSQL
$condicionSQLMarcas
SENTENCIA_SQL;

            $result=$this->_DB->fetchTable($sentenciaSQL, false, $parametrosBusqueda);

            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return 0;
            }
            return count($result);

        } else {
            return 0;
        }

        
    }

    public function obtenerRegistros($limit, $offset, $parametros, $marcasAsignadas)
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
dt.fecha_inicio,
dt.fecha_fin,
dt.descanso AS descanso_prefijo,
de.nombre AS descanso_nombre,
dt.motivo_desconexion,
se.agente AS agente_numero,
ag.nombre AS agente_nombre,
ag.campana AS campana_prefijo,
ca.nombre AS campana_nombre,
ca.marca AS marca_prefijo,
ma.nombre AS marca_nombre,
TIMESTAMPDIFF(SECOND,dt.fecha_inicio,IFNULL(dt.fecha_fin, NOW())) AS duracion
$joinsSQL
$condicionSQL
$condicionSQLMarcas
ORDER BY dt.fecha_inicio
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

}
