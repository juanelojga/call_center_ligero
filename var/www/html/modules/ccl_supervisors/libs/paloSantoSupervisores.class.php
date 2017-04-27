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
class paloSantoSupervisores{
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

    public function borrarMarcaAsignadaSupervisor($marca, $id_supervisor)
    {
        $where    = "";
        $arrParam = null;
        
        if(isset($marca) & $marca !="" & isset($id_supervisor) & $id_supervisor !=""){
          $where    = "WHERE (marca = ? AND id_supervisor = ?)";
          $arrParam = array($marca, $id_supervisor);
          
          $query = "DELETE FROM supervisores_marcas $where";

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

    public function obtenerMarcasAsignadasSupervisor($id_supervisor)
    {
        $where    = "";
        $arrParam = array();

        if(isset($id_supervisor) & $id_supervisor !=""){
            $where    = "WHERE id_supervisor LIKE CONCAT('%',?,'%') ";
            $arrParam[] = $id_supervisor;
        }

        $query   = "
          SELECT *
          FROM supervisores_marcas
          $where
          ORDER BY marca
        ";

        $result=$this->_DB->fetchTable($query, true, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result;
    }

    public function asignarMarcaSupervisor($marca, $id_supervisor)
    {
        $arrParam = array($marca, $id_supervisor);
          
        $query = "INSERT INTO supervisores_marcas (marca, id_supervisor) VALUES(?, ?)";

        $result = $this->_DB->genQuery($query, $arrParam);

        if($result==FALSE){
          $this->errMsg = $this->_DB->errMsg;
          return null;
        }

        return $result;
    }

    public function verificarMarcaAsignadaSupervisor($marca, $id_supervisor)
    {
        $where    = "";
        $arrParam = array();

        if(isset($marca) & $marca !="" & isset($id_supervisor) & $id_supervisor !=""){
          $where    = "WHERE (marca = ? AND id_supervisor = ?)";
          $arrParam = array($marca, $id_supervisor);
        }

        $query   = "
          SELECT 
          COUNT(*) 
          FROM supervisores_marcas
          $where
        ";

        $result=$this->_DB->getFirstRowQuery($query, false, $arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }

        if ($result[0] > 0) {
          return TRUE;
        } else {
          return FALSE;
        }

    }

}
