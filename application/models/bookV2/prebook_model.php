<?php

namespace models\bookV2;

/**
 * Description of prebook_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;
use Exception;

class prebook_model extends Model
{

    private $suppliercode = '';
    private $search_model = null;
    var $lineitemid = array();
    var $hotelmsg = '';
    protected $email = 'sample@agoda.com';

    public function __construct()
    {
        $CI = getInstance();
        $this->suppliercode = $CI->suppliercode;
        $this->search_model = $CI->search_model;
        unset($CI);
    }

    public function getRequest(array $post, \SimpleXMLElement $roomProduct)
    {
        $o_resp = parse_xml($post['response']['content'][0]);
        $o_resp->registerXPathNamespace('ns', 'http://xml.agoda.com');
        $searchid = current($o_resp->xpath('//@searchid'));

        return '<?xml version="1.0" encoding="utf-8" ?>'
                . '<BookingRequestV2 siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                . '<BookingDetails searchid="' . (string) $searchid . '" currency="' . $roomProduct->attributes()->currency . '" ratetype="' . $roomProduct->attributes()->ratetype . '" tag="' . $post['OSRefNo'] . '" AllowDuplication="false">'
                . $this->_requestRooms($post, $roomProduct)
                . '</BookingDetails>'
                . '<CustomerDetail>'
                . '<Language>en-us</Language>'
                . '<Title>Mr.</Title>'
                . '<FirstName>Sample</FirstName>'
                . '<LastName>Sample</LastName>'
                . '<Email>' . $this->email . '</Email>'
                . '<Phone>'
                . '<CountryCode>66</CountryCode>'
                . '<AreaCode>2</AreaCode>'
                . '<Number>6614565589</Number>'
                . '</Phone>'
                . '<Newsletter>true</Newsletter>'
                . '</CustomerDetail>'
                . $this->_requestGuestDetails($post)
                . '<PaymentDetails>'
                . '</PaymentDetails>'
                . '</BookingRequestV2>';
    }

    /**
     * 
     * @param array $post
     * @param \SimpleXMLElement $o_room
     * @return string
     */
    private function _requestRooms(array $post, \SimpleXMLElement $o_room)
    {
        $o_room->registerXPathNamespace('ns', 'http://xml.agoda.com');
        $o_surcharge = $o_room->xpath('.//ns:Surcharge');

        //surcharge.
        if (!empty($o_surcharge))
        {
            $hotelMsgComponents = array();
            $idx = 0;
            foreach ($o_room->RateInfo->Surcharges->Surcharge as $surcharge)
            {
                switch (strtoupper((string) $surcharge->attributes()->charge))
                {
                    case "EXCLUDED":
                        $hotelMsgComponents['exclude'][] = (string) $surcharge->Name . ' ' . (string) $surcharge->Rate->attributes()->inclusive . ' ' . (string) $o_room->attributes()->currency;
                        break;
                    case "MANDATORY":
                        $hotelMsgComponents['mandatory'][] = (string) $surcharge->Name;
                        unset($o_room->RateInfo->Surcharges->Surcharge[$idx]);
                        break;
                }
                $idx++;
            }

            if (!empty($hotelMsgComponents))
            {
                $this->hotelmsg = 'Pay at hotel : ' . implode(', ', $hotelMsgComponents['exclude']) . ". ";

                if (isset($hotelMsgComponents['mandatory']))
                {
                    $this->hotelmsg .= 'Price included : ' . implode(', ', $hotelMsgComponents['mandatory']);
                }
            }
        }

        $room = '';
        foreach ($post['prepareRequest'] as $roomType)
        {
            $room .= '<Room lineitemid="' . $o_room->attributes()->lineitemid . '" count="' . $roomType['totalroom'] . '" rateplan="' . $o_room->attributes()->rateplan . '" adults="' . $roomType['adult_request'] . '" children="0">'
                    . $o_room->RateInfo->Rate->asXML()
                    . $o_room->RateInfo->Surcharges->asXML()
                    . '</Room>';
        }

        return '<Rooms>' . $room . '</Rooms>';
    }

    /**
     * 
     * @param array $post
     * @return type
     */
    private function _requestGuestDetails(array $post)
    {
        $rs = $this->db->query('SELECT ShortName FROM countrys WHERE WSCode = "' . $post['PaxPassport'] . '"');
        if ($rs !== FALSE)
        {
            $copax = $rs->result_array();
        }
        $rs->free_result();


        $guestsdetails = '';
        $idxpax = 0;
        foreach ($post['prepareRequest'] as $roomType)
        {
            foreach ($post['Rooms'] as $reqRoom)
            {
                if ($roomType['fulltype'] != $reqRoom['RoomTypeName'])
                {
                    continue;
                }

                foreach ($reqRoom['PaxInformation'] as $pax)
                {
                    $_attr_primary_set = '';
                    if ($idxpax == 0)
                    {
                        $_attr_primary_set = ' Primary="true"';
                    }
                    $guestsdetails .= '<GuestDetail' . $_attr_primary_set . '>'
                            . '<Title>' . $pax->prefixName . '</Title>'
                            . '<FirstName>' . $pax->name . '</FirstName>'
                            . '<LastName>' . $pax->surName . '</LastName>'
                            . '<CountryOfPassport>' . $copax[0]['ShortName'] . '</CountryOfPassport>'
                            . '</GuestDetail>';
                    $idxpax++;
                }
            }
        }
        return '<GuestDetails>' . $guestsdetails . '</GuestDetails>';
    }

    /**
     * 
     * @param array $post
     * @return SimpleXMLElement
     */
    public function getRoomProduct(array $post)
    {
        $o_resp = parse_xml($post['response']['content'][0]);
        $o_resp->registerXPathNamespace('ns', 'http://xml.agoda.com');
        $roomToken = array();

        $dataRoom = $o_resp->xpath($post['query']);
        if (empty($dataRoom))
        {
            throw new Exception('no search product found.');
        }

        foreach ($dataRoom as $room)
        {
            foreach ($post['prepareRequest'] as $roomtype)
            {
                $roomToken[] = array(
                    'AdultNum' => $roomtype['adults'],
                    'ChildNum' => $roomtype['children'],
                    'RoomCatg' => (string) $room->attributes()->id,
                    'BKF' => $this->search_model->getIncludeMeal($room),
                    'ChildAge1' => $roomtype['age1'],
                    'ChildAge2' => $roomtype['age2'],
                    'PriceTotal' => $this->search_model->getPriceTotal($room, $post),
                    '_RoomItem' => $room
                );
            }
        }


        $_return = $this->search_model->getBestRoomPrice($roomToken);
        $_return = array_values($_return);
        return $_return[0]['_RoomItem'];
    }

    /**
     * 
     * @param array $post
     * @return type
     * @throws Exception
     */
    public function getRoomcategory(array $post)
    {
        $a_sp_roomcateg = GetListRoomCatgCode($post['RoomCatgWScode'], $this->suppliercode);
        if (count($a_sp_roomcateg) == 0)
        {
            throw new Exception('Room category not found.');
        }
        return $a_sp_roomcateg;
    }

    /**
     * 
     * @param array $post
     * @return type
     * @throws Exception
     */
    public function getMealType(array $post)
    {
        $a_meal = GetListMealTypeCode($post['BFType'], $this->suppliercode);
        if (count($a_meal) == 0)
        {
            throw new Exception('Meal type not found.');
        }

        return $a_meal;
    }

    /**
     * 
     * @param array $roomcategories
     * @param array $meals
     * @param array $post
     * @return string
     */
    public function getQueryProduct(array $roomcategories, array $meals, array $post)
    {
        $s_xpath_roomcatg = '//ns:Rooms/ns:Room[';
        foreach ($roomcategories as $room)
        {
            foreach ($meals as $meal)
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
        return $s_xpath_roomcatg;
    }

}
