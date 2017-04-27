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
  $Id: paloSantoMarcas.class.php,v 1.1 2016-02-22 09:02:19 Juan Almeida jalmeida@palosanto.com Exp $ */
class paloSantoMarcas{
    var $_DB;
    var $errMsg;

    function paloSantoMarcas(&$pDB)
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
    function getNumMarcas($filter_field = "", $filter_value = "")
    {
        $where    = "";
        $arrParam = null;

        if(isset($filter_field) & $filter_field !=""){
            $where    = "WHERE $filter_field LIKE CONCAT('%',?,'%')";
            $arrParam = array("$filter_value");
        }

        $query   = "SELECT COUNT(*) FROM marcas $where";

        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    // obtener las marcas
    function getMarcas($limit, $offset = 0, $filter_field = "", $filter_value = "")
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($filter_field) & $filter_field !=""){
            $where    = "WHERE $filter_field LIKE CONCAT('%',?,'%')";
            $arrParam = array("$filter_value");
        }

        $query   = "SELECT * FROM marcas $where ORDER BY prefijo ASC LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getMarcaByPrefijo($prefijo)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($prefijo) & $prefijo !=""){
            $where    = "WHERE prefijo = ?";
            $arrParam = array("$prefijo");
        }

        $query = "SELECT * FROM marcas $where";

        $result=$this->_DB->getFirstRowQuery($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function deleteMarcaByPrefijo($prefijo)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($prefijo) & $prefijo !=""){
          $where    = "WHERE prefijo = ?";
          $arrParam = array("$prefijo");
          
          $query = "DELETE FROM marcas $where";

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

    function createMarca($prefijo, $nombre, $descripcion)
    {
        $arrParam = array($prefijo, $nombre, $descripcion);
          
        $query = "INSERT INTO marcas (prefijo, nombre, descripcion) VALUES(?, ?, ?)";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }

    function editMarca($prefijo, $nombre, $descripcion)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($prefijo) & $prefijo !="") {
            $set      = "nombre = ?, descripcion = ?";
            $where    = "prefijo = ?";
            $arrParam = array($nombre, $descripcion, $prefijo);
        }
          
        $query = "UPDATE marcas SET $set WHERE $where";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }
}
