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
  $Id: paloSantoAgentes.class.php,v 1.1 2016-02-24 11:02:39 Juan Almeida jalmeida@palosanto.com Exp $ */
class paloSantoAgentes{
    var $_DB;
    var $errMsg;

    function paloSantoAgentes(&$pDB)
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

    function getNumAgentes($filter_field, $filter_value, $filter_by_brand, $filter_by_campaign)
    {
        $where    = "";
        $arrParam = array();

        if(isset($filter_field) & $filter_field !=""){
            $where    = "WHERE a.$filter_field LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $filter_value;
        }

        if(isset($filter_by_brand) & $filter_by_brand !=""){
            $where    .= "AND m.nombre LIKE CONCAT('%',?,'%')";
            $arrParam[] = $filter_by_brand;
        }

        if(isset($filter_by_campaign) & $filter_by_campaign !=""){
            $where    .= "AND c.nombre LIKE CONCAT('%',?,'%')";
            $arrParam[] = $filter_by_campaign;
        }

        $query   = "SELECT 
          COUNT(c.prefijo) 
          FROM agentes AS a
          JOIN campanas AS c 
            ON c.prefijo = a.campana 
          JOIN marcas AS m 
            ON c.marca = m.prefijo 
          $where";

        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getAgentes($limit, $offset, $filter_field, $filter_value, $filter_by_brand, $filter_by_campaign)
    {
        $where    = "";
        $arrParam = null;

        if(isset($filter_field) & $filter_field !=""){
            $where    = "WHERE a.$filter_field LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $filter_value;
        }

        if(isset($filter_by_brand) & $filter_by_brand !=""){
            $where    .= "AND m.nombre LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $filter_by_brand;
        }

        if(isset($filter_by_campaign) & $filter_by_campaign !=""){
            $where    .= "AND c.nombre LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $filter_by_campaign;
        }

        $query   = "
          SELECT 
          a.*,
          m.nombre AS marca_nombre,
          c.nombre AS campana_nombre
          FROM agentes AS a
          JOIN campanas AS c 
            ON c.prefijo = a.campana 
          JOIN marcas AS m 
            ON c.marca = m.prefijo
          $where
          ORDER BY m.nombre, c.nombre, a.numero 
          LIMIT $limit 
          OFFSET $offset
        ";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
    }

    function getAgentesByCampana($campana)
    {
        $query = "SELECT * FROM agentes WHERE campana = ?";

        $result=$this->_DB->fetchTable($query, true, array($campana));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function deleteAgente($numero)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($numero) & $numero !=""){
          $where    = "WHERE numero = ?";
          $arrParam = array($numero);
          
          $query = "DELETE FROM agentes $where";

          $result = $this->_DB->genQuery($query, $arrParam);

          if($result==FALSE){
              $this->errMsg = $this->_DB->errMsg;
              return null;
          }

          return $result;

        } else {

          $this->errMsg = "No se ha seleccionado un agente";
          return null;

        }
    }

    function createAgente($numero, $nombre, $campana)
    {
        $arrParam = array($numero, $nombre, $campana);
          
        $query = "INSERT INTO agentes (numero, nombre, campana) VALUES(?, ?, ?)";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }

    function getAgenteByNumero($numero)
    {
        $where    = "";
        $arrParam = null;

        if(isset($numero) & $numero !=""){
          $where    = "WHERE a.numero = ?";
          $arrParam = array($numero);
        }

        $query   = "
          SELECT 
          a.*,
          m.nombre AS marca_nombre,
          c.nombre AS campana_nombre
          FROM agentes AS a
          JOIN campanas AS c 
            ON c.prefijo = a.campana 
          JOIN marcas AS m 
            ON c.marca = m.prefijo
          $where
        ";

        $result=$this->_DB->getFirstRowQuery($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function editarAgente($numero, $nombre)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($numero) & $numero !="" & isset($nombre) & $nombre !="") {
            $set      = "nombre = ?";
            $where    = "numero = ?";
            $arrParam = array($nombre, $numero);
        }
          
        $query = "UPDATE agentes SET $set WHERE $where";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }
}
