<?php

namespace models\book;

/**
 * Description of prebook_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;
use \Exception;

class prebook_model extends Model
{

    var $suppliercode = '';
    var $search_model = null;
    var $lineitemid = array();
    var $hotelmsg = '';

    public function __construct()
    {
        $CI = &getInstance();
        if ($CI->search_model instanceof \models\search_model)
        {
            $this->search_model = $CI->search_model;
        }
        else
        {
            $CI->load->model('search_model');
            $this->search_model = $CI->search_model;
        }
        $this->suppliercode = $CI->suppliercode;
        unset($CI);
    }

    /**
     * 
     * @param array $post
     * @throws Exception
     */
    public function getRequest(array &$post)
    {

        $query_rooms = $this->getQueryRoom($post);
        //if multi room type, use multi search.
        foreach ($post['response']['content'] as $idx => &$resp)
        {
            $o_xml = parse_xml($resp);
            $o_xml->registerXPathNamespace('ns', 'http://xml.agoda.com');
            $a_request_data = $o_xml->xpath('//@searchid | ' . $query_rooms);

            //if selected room no exists.
            if (!isset($a_request_data[1]) OR ! isset($a_request_data[0]))
            {
                $errmsg = 'Product not found.(Room name = ' . $post['RoomCatgName'] . ', Room code = ' . $post['RoomCatgWScode'] . ', Meal = ' . $post['BFType'] . ')';
                throw new Exception($errmsg);
            }

            $searchid = $a_request_data[0];
            $o_room = $this->getBestprice($a_request_data[1], $post['flagType'][$idx], $post);
            unset($a_request_data);

            //if surcharge was available.
            $surchargeText = '';
            $surcharge = '';
            $surchargeTag = '<Surcharges>';
            if (isset($o_room->RateInfo->Surcharges))
            {
                $hotelMessage = '';
                $idx_charge = 0;

                foreach ($o_room->RateInfo->Surcharges->Surcharge as $s_charge)
                {
                    if (strtolower($s_charge->attributes()->charge) == 'excluded')
                    {
                        $hotelMsg .= (string) $s_charge->Name . ' ' . (string) $s_charge->Rate->attributes()->inclusive . ' ' . (string) $o_room->attributes()->currency . ', ';
                        //unset($o_room->RateInfo->Surcharges->Surcharge[$idx_charge]);
                    }
                    //bot 16333
                    if (strtolower($s_charge->attributes()->charge) == 'mandatory')
                    {
                        $surchargeText .= $s_charge->asXML();
                        $hotelMsg_mandatory .= (string) $s_charge->Name . ', ';
                        //unset($o_room->RateInfo->Surcharges->Surcharge[$idx_charge]);
                    }
                    $idx_charge++;
                }

                $hotelMessage .= ($hotelMsg != '') ? 'Pay at hotel : ' . $hotelMsg : '';
                $hotelMessage .= ($hotelMsg_mandatory != '') ? 'Price included : ' . substr($hotelMsg_mandatory, 0, -2) . ' charges., ' : '';
                $this->hotelmsg = substr($hotelMessage, 0, -2);
            }
            $surchargeEndTag = '</Surcharges>';
            $surcharge = ($surchargeText != '') ? $surchargeTag . $surchargeText . $surchargeEndTag : '';

            //store lineitemid use for get cancel policy
            //temporary close multi room type
            //array_push($this->lineitemid, (string) $o_room->attributes()->lineitemid);
            $this->lineitemid[0] = (string) $o_room->attributes()->lineitemid;

            $post['request'][$idx] = '<?xml version="1.0" encoding="utf-8" ?>'
                    . '<BookingRequestV2 siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                    . '<BookingDetails searchid="' . $searchid . '" currency="' . $o_room->attributes()->currency . '" ratetype="' . $o_room->attributes()->ratetype . '" tag="' . $post['OSRefNo'] . '" AllowDuplication="false">'
                    . '<Rooms>'
                    . '<Room lineitemid="' . $o_room->attributes()->lineitemid . '" count="' . $post['flagType'][$idx]['totalroom'] . '" rateplan="' . $o_room->attributes()->rateplan . '" adults="' . $post['flagType'][$idx]['adult_request'] . '" children="0">'
                    . $o_room->RateInfo->Rate->asXML()
                    . $surcharge
                    . '</Room>'
                    . '</Rooms>'
                    . '</BookingDetails>'
                    . '<CustomerDetail>'
                    . '<Language>en-us</Language>'
                    . '<Title>Mr.</Title>'
                    . '<FirstName>Sample</FirstName>'
                    . '<LastName>Sample</LastName>'
                    . '<Email>sample@agoda.com</Email>'
                    . '<Phone>'
                    . '<CountryCode>66</CountryCode>'
                    . '<AreaCode>2</AreaCode>'
                    . '<Number>6614565589</Number>'
                    . '</Phone>'
                    . '<Newsletter>true</Newsletter>'
                    . '</CustomerDetail>'
                    . '<GuestDetails>'
                    . $this->_guestDeatils($post, $idx)
                    . '</GuestDetails>'
                    . '<PaymentDetails>'
                    . '</PaymentDetails>'
                    . '</BookingRequestV2>';
        }
    }

    /**
     * 
     * @param array $post
     * @param int $idx
     * @return string
     */
    private function _guestDeatils(array $post, $idx)
    {
        //guests
        //GuestDetails
        $rs = $this->db->query('SELECT ShortName FROM countrys WHERE WSCode = "' . $post['PaxPassport'] . '"');
        if ($rs !== FALSE)
        {
            $copax = $rs->result_array();
        }
        $rs->free_result();

        $idxpax = 0;
        $guestsdetails = '';
        foreach ($post['Rooms'] as $room)
        {
            if ($room['ShortRoomTypeName'] != $post['flagType'][$idx]['shorttype'])
            {
                continue;
            }

            foreach ($room['PaxInformation'] as $pax)
            {
                $_attr_primary_set = '';
                if ($idxpax == 0)
                {
                    $_attr_primary_set = ' Primary="true"';
                }

                if (!is_object($pax))
                {
                    $pax = (object) $pax;
                }
                $guestsdetails .= '<GuestDetail' . $_attr_primary_set . '>'
                        . '<Title>' . preg_replace('/mr/i', 'Mr.', $pax->prefixName) . '</Title>'
                        . '<FirstName>' . $pax->name . '</FirstName>'
                        . '<LastName>' . $pax->surName . '</LastName>'
                        . '<CountryOfPassport>' . $copax[0]['ShortName'] . '</CountryOfPassport>'
                        . '</GuestDetail>';
                $idxpax++;
            }
        }
        return $guestsdetails;
    }

    /**
     * 
     * @param array $post
     * @return string
     * @throws Exception
     */
    public function getQueryRoom(array $post)
    {
        $a_sp_roomcateg = @GetListRoomCatgCode($post['RoomCatgWScode'], $this->suppliercode);
        $a_sp_roomcateg = array_filter($a_sp_roomcateg);
        if (empty($a_sp_roomcateg))
        {
            throw new Exception('no room available.');
        }



        $a_meal = @GetListMealTypeCode($post['BFType'], $this->suppliercode);
        $a_meal = array_filter($a_meal);
        if (empty($a_sp_roomcateg))
        {
            throw new Exception('no room available.');
        }

        $s_xpath_roomcatg = '//ns:Rooms/ns:Room[';
        foreach ($a_sp_roomcateg as $room)
        {
            foreach ($a_meal as $meal)
            {
                $s_xpath_roomcatg .= '(@id = "' . $room['SpCode'] . '" and .//ns:RateInfo/ns:Included="' . trim($meal['SpCode']) . '") or ';
            }

            //Room only when node RateInfo > Included not exists.
            if (strtoupper($post['BFType']) == "RO")
            {
                $s_xpath_roomcatg .= '(@id = "' . $room['SpCode'] . '" and not(.//ns:RateInfo/ns:Included)) or ';
            }
        }

        $s_xpath_roomcatg = substr($s_xpath_roomcatg, 0, -4) . ']';
        unset($a_sp_roomcateg);
        return $s_xpath_roomcatg;
    }

    /**
     * 
     * @param \SimpleXMLElement $o_room
     * @param array $a_flag_type
     * @param array $post
     * @return \SimpleXMLElement
     */
    public function getBestprice(\SimpleXMLElement $o_room, array $a_flag_type, array $post)
    {
        if ($o_room instanceof \SimpleXMLElement)
        {
            return $o_room;
        }

        //re array format for get lowest price.
        $data = array();
        foreach ($o_room as $room)
        {
            $data[] = array(
                'AdultNum' => $a_flag_type['adults'],
                'ChildNum' => $a_flag_type['children'],
                'RoomCatg' => (string) $room->attributes()->id,
                'BKF' => $this->search_model->getIncludeMeal($room),
                'ChildAge1' => $a_flag_type['age1'],
                'ChildAge2' => $a_flag_type['age2'],
                'PriceTotal' => $this->search_model->getPriceTotal($room, $post),
                '_RoomItem' => $room
            );
        }

        $_return = $this->search_model->getBestRoomPrice($data);
        unset($data);
        return $_return[0]['_RoomItem'];
    }

}
