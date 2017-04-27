<?php
class paloSantoReporteLogin{
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
(DATE(se.fecha_inicio) BETWEEN ? AND ?)
AND (? IS NULL OR IF (? IS NULL,se.extension LIKE CONCAT('%',?,'%'),se.extension BETWEEN ? AND ?))
AND (? IS NULL OR se.motivo_desconexion LIKE CONCAT('%',?,'%'))
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
        $parametros["fecha_inicio"], $parametros["fecha_fin"],
        $parametros["fecha_inicio"], $parametros["fecha_fin"],
        $parametros["fecha_inicio"], $parametros["fecha_fin"],
        $parametros["fecha_inicio"], $parametros["fecha_fin"],
        $parametros["fecha_inicio"], $parametros["fecha_fin"],
        $parametros["extension_inicio"], $parametros["extension_fin"], $parametros["extension_inicio"], $parametros["extension_inicio"], $parametros["extension_fin"],
        $parametros["motivo_desconexion"], $parametros["motivo_desconexion"],
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
    public function obtenerTotalRegistros($parametros, $marcasAsignadas)
    {

        $joinsSQL = $this->_darFormatoJoinTablas();

        $condicionSQL = $this->_darFormatoCondicionBusqueda();

        $parametrosBusqueda = $this->_darFormatoParametrosBusqueda($parametros);

        $condicionMarcas = $this->_darFormatoCondicionBusquedaMarcasAsignadas($marcasAsignadas);

        if ((count($condicionMarcas["parametrosSQL"]) > 0 && is_array($condicionMarcas["parametrosSQL"])) || $marcasAsignadas["esAdministrador"]) {

            $condicionSQLMarcas = $condicionMarcas["condicionSQL"];

            $parametrosBusqueda = array_merge($parametrosBusqueda, $condicionMarcas["parametrosSQL"]);

            $parametrosBusqueda = array_slice($parametrosBusqueda, 10);

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
se.extension,
se.fecha_inicio,
se.fecha_fin,
se.motivo_desconexion,
TIMESTAMPDIFF(SECOND,se.fecha_inicio,IFNULL(se.fecha_fin, NOW())) AS duracion_sesion,
(
  SELECT COUNT(*)
  FROM `asteriskcdrdb`.`cdr`
  WHERE
  (DATE(cdr.calldate) BETWEEN ? AND ?)
  AND se.id = cdr.login_logout_id
  AND cdr.descanso IS NULL
) AS numero_llamadas_activo,
(
  SELECT SUM(cdr.billsec)
  FROM `asteriskcdrdb`.`cdr`
  WHERE
  (DATE(cdr.calldate) BETWEEN ? AND ?)
  AND se.id = cdr.login_logout_id
  AND cdr.descanso IS NULL
) AS tiempo_llamadas_activo,
(
  SELECT
  SUM(UNIX_TIMESTAMP(IFNULL(dt.fecha_fin, NOW())) - UNIX_TIMESTAMP(dt.fecha_inicio))
  FROM descansos_tomados AS dt
  WHERE
  (DATE(dt.fecha_inicio) BETWEEN ? AND ?)
  AND dt.login_logout_id = se.id
) AS tiempo_en_descanso,
(
  SELECT SUM(cdr.billsec)
  FROM `asteriskcdrdb`.`cdr`
  WHERE
  (DATE(cdr.calldate) BETWEEN ? AND ?)
  AND se.id = cdr.login_logout_id
  AND cdr.descanso IS NOT NULL
) AS tiempo_llamadas_descanso,
(
  SELECT COUNT(*)
  FROM `asteriskcdrdb`.`cdr`
  WHERE
  (DATE(cdr.calldate) BETWEEN ? AND ?)
  AND se.id = cdr.login_logout_id
  AND cdr.descanso IS NOT NULL
) AS numero_llamadas_descanso,
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

}
