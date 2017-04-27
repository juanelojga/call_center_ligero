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
  $Id: paloSantoCampanas.class.php,v 1.1 2016-02-23 09:02:30 Juan Almeida jalmeida@palosanto.com Exp $ */
class paloSantoCampanas{
    var $_DB;
    var $errMsg;

    function paloSantoCampanas(&$pDB)
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

    // Obtener la cantidad de marcas
    function getNumCampanas($filter_field, $filter_value, $filter_parent)
    {
        $where    = "";
        $arrParam = array();

        if(isset($filter_field) & $filter_field !=""){
            $where    = "WHERE c.$filter_field LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $filter_value;
        }

        if(isset($filter_parent) & $filter_parent !=""){
            $where    .= "AND m.nombre LIKE CONCAT('%',?,'%')";
            $arrParam[] = $filter_parent;
        }

        $query   = "SELECT 
          COUNT(c.prefijo) 
          FROM campanas AS c
          JOIN marcas AS m ON c.marca = m.prefijo 
          $where";

        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getCampanas($limit, $offset, $filter_field, $filter_value, $filter_parent)
    {
        $where    = "";
        $arrParam = null;

        if(isset($filter_field) & $filter_field !=""){
            $where    = "WHERE c.$filter_field LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $filter_value;
        }

        if(isset($filter_parent) & $filter_parent !=""){
            $where    .= "AND m.nombre LIKE CONCAT('%',?,'%')";
            $arrParam[] = $filter_parent;
        }

        $query   = "
          SELECT 
          c.*,
          m.nombre AS marca_nombre 
          FROM campanas AS c
          JOIN marcas AS m ON c.marca = m.prefijo 
          $where
          ORDER BY m.nombre, c.prefijo 
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

    function getCampanasByMarca($marca)
    {
        $where    = "";
        $arrParam = null;

        if(isset($marca) & $marca !=""){
          $where    = "WHERE marca = ?";
          $arrParam = array($marca);
        }

        $query   = "
          SELECT *
          FROM campanas
          $where
        ";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
    }

    function deleteCampanaByPrefijoAndMarca($prefijo, $marca)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($prefijo) & $prefijo !="" & isset($marca) & $marca !=""){
          $where    = "WHERE prefijo = ? AND marca = ?";
          $arrParam = array($prefijo, $marca);
          
          $query = "DELETE FROM campanas $where";

          $result = $this->_DB->genQuery($query, $arrParam);

          if($result==FALSE){
              $this->errMsg = $this->_DB->errMsg;
              return null;
          }

          return $result;

        } else {

          $this->errMsg = "No se ha seleccionado una marca";
          return null;

        }
    }

    function getCampanaByPrefijoAndMarca($prefijo, $marca)
    {
        $where    = "";
        $arrParam = null;

        if(isset($prefijo) & $prefijo !="" & isset($marca) & $marca !=""){
          $where    = "WHERE c.prefijo = ? AND c.marca = ?";
          $arrParam = array($prefijo, $marca);
        }

        $query   = "
          SELECT 
          c.*,
          m.nombre AS marca_nombre 
          FROM campanas AS c
          JOIN marcas AS m ON c.marca = m.prefijo 
          $where
        ";

        $result=$this->_DB->getFirstRowQuery($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function createCampana($prefijo, $nombre, $descripcion, $marca)
    {
        $arrParam = array($prefijo, $nombre, $descripcion, $marca);
          
        $query = "INSERT INTO campanas (prefijo, nombre, descripcion, marca) VALUES(?, ?, ?, ?)";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }

    function editCampana($prefijo, $nombre, $descripcion, $marca)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($prefijo) & $prefijo !="") {
            $set      = "nombre = ?, descripcion = ?";
            $where    = "prefijo = ? AND marca = ?";
            $arrParam = array($nombre, $descripcion, $prefijo, $marca);
        }
          
        $query = "UPDATE campanas SET $set WHERE $where";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }
}
