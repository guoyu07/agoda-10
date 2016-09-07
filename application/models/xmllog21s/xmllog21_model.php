<?php

namespace models\xmllog21s;

/**
 * Description of xmllog21_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;

class xmllog21_model extends Model {

    public function fetchLogList($post) {

        $post = array_filter($post);
        $str_condition = '';
        foreach ($post as $k => $v)
        {
            $str_condition .= $k . " like '%" . $v . "%' AND ";
        }

        $strSQL = "SELECT id,XMLService,CreateDate  FROM xmllog21s where " . $str_condition . " SupplierCode = '" . GetSupplierCode() . "'";

        $rs = $this->db->query($strSQL);
        if ($rs === FALSE)
        {
            return array();
        }


        $data_fetch = $rs->result_array();

        $aaData = array();
        foreach ($data_fetch as $row)
        {
            $aaData[] = array($row['id'], $row['XMLService'], $row['CreateDate']);
        }

        return json_encode($aaData);
    }

    public function fetchLogDetails($post) {
        $strSQL = "SELECT `Id`,"
                . "`XMLService`,"
                . "`RQLog21`,"
                . "`RPLog21`,"
                . "`SupRQLog`,"
                . "`SupRPLog`,"
                . "`CreateDate`,"
                . "`ConfirmNo`"
                . " FROM xmllog21s"
                . " WHERE"
                . " Id = '" . $post['ID'] . "'"
                . " AND"
                . " SupplierCode = '" . GetSupplierCode() . "'";
        $rs = $this->db->query($strSQL);
        if ($rs === FALSE)
        {
            return array();
        }
        
        return @array_shift($rs->result_array());
    }

}
