<?php

namespace models;

/**
 * Description of search_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;
use \SimpleXMLElement;
use \Exception;

class search_model extends Model
{

    var $fetch_data_count = 0;
    var $chunk_hotel_list = 30;
    var $sessionid = NULL;
    var $cache_xpath = array();
    var $suppliercode = '';
    protected $hotelMsg = '';
    protected $emuMaxAge = 0;

    /**
     * construction and initilize.
     */
    public function __construct()
    {
        $CI = getInstance();
        $this->suppliercode = $CI->suppliercode;
        $this->sessionid = date('YmdHis') . rand(0, 999);
        unset($CI);
    }

    /**
     * Get hotels list in the city.
     * @staticvar int $count
     * @param array $post
     * @return FALSE;
     * @throws Exception
     */
    public function fetchHotelList(array &$post)
    {
        if (isset($post["flagAvail"]) AND ( strtoupper($post["flagAvail"]) == 'N' OR strtoupper($post["flagAvail"]) == 'FALSE'))
        {
            throw new Exception('On request room doesn\'t support');
        }

        $strSQL = 'SELECT SpHotelCode'
                . ' FROM'
                . ' spcitys ci,'
                . ' spcountrys co, '
                . _SUPPLIERCODE . '_pfmappings pf'
                . ' WHERE'
                . ' pf.SupplierCode = "' . $this->suppliercode . '"'
                . ' AND co.SupplierCode = "' . $this->suppliercode . '"'
                . ' AND ci.SupplierCode = "' . $this->suppliercode . '"';

        if ($post['HotelId'] != NULL OR $post['HotelId'] != '')
        {
            $strSQL .= ' AND pf.SpCountry = co.SpCountryCode'
                    . ' AND pf.SpCity = ci.SpCityCode'
                    . ' AND pf.WSCode = "' . $post['HotelId'] . '"'
                    . ' GROUP BY pf.SpHotelCode';
        }
        else
        {
            $strSQL .= ' AND ci.WSCode = "' . $post['DestCity'] . '"'
                    . ' AND co.WSCode = "' . $post['DestCountry'] . '"'
                    . ' AND pf.SpCountry = co.SpCountryCode'
                    . ' AND pf.SpCity = ci.SpCityCode'
                    . ' LIMIT ' . $this->fetch_data_count . ',' . $this->chunk_hotel_list . "\n";
        }

        $rs = $this->db->query($strSQL);
        if ($rs === FALSE)
        {
            return;
        }

        //If not result, exit recursive process.
        if ($rs->num_rows() == 0)
        {
            return;
        }

        $result = $rs->result_array();
        foreach ($result as $k => $val)
        {
            $post['SpHotelList'][] = $val['SpHotelCode'];
        }

        $this->fetch_data_count += $this->chunk_hotel_list;


        if ($post['HotelId'] == NULL OR $post['HotelId'] == '')
        {
            $this->fetchHotelList($post);
        }
    }

    /**
     * Build request use by xml style.
     * @param array $post
     * @throws Exception
     */
    public function request(array &$post)
    {
        if (!isset($post['SpHotelList']))
        {
            throw new Exception('Hotel not available in database, please check your HotelCode.');
        }

        //check hotel request.
        $post['SpHotelList'] = array_chunk($post['SpHotelList'], $this->chunk_hotel_list);

        //temporary close
        //2015-08-19
        if (count($post['prepareRequest']) > 1)
        {
            throw new Exception('multi room type temporary close.');
        }

        //build xml request.
        foreach ($post['prepareRequest'] as $type)
        {
            foreach ($post['SpHotelList'] as $h_chunk)
            {
                $TypeCode = '4';
                if (!isset($post['HotelId']) OR $post['HotelId'] == NULL OR $post['HotelId'] == '')
                {
                    $TypeCode = '6';
                }
                $post['request'][] = '<?xml version="1.0" encoding="utf-8" ?>'
                        . '<AvailabilityRequestV2 siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" async="false" waittime="12" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                        . '<Type>' . $TypeCode . '</Type>'
                        . '<Id>' . implode(',', $h_chunk) . '</Id>'
                        . '<Radius>0</Radius>'
                        . '<Latitude>0</Latitude>'
                        . '<Longitude>0</Longitude>'
                        . '<CheckIn>' . $post['FromDt'] . '</CheckIn>'
                        . '<CheckOut>' . $post['ToDt'] . '</CheckOut>'
                        . '<Rooms>' . $type['totalroom'] . '</Rooms>'
                        . '<Adults>' . $type['adult_request'] . '</Adults>'
                        . '<Children>0</Children>'
                        . '<Language>en-us</Language>'
                        . '<Currency>USD</Currency>'
                        . '</AvailabilityRequestV2>';
                $post['flagType'][] = $type;
            }
        }
    }

    /**
     * call and insert products rate into database.
     * @param type $post
     * @return boolean
     */
    public function response($post)
    {

        if (function_exists('supplierCreateTemporary'))
        {
            $tmpSupplier = supplierCreateTemporary($this->sessionid);
        }

        $arrResultPost = array();
        foreach ($post['response']['content'] as $idx => $response)
        {
            $o_resp = parse_xml($response);
            if (isset($o_resp->ErrorMessages))
            {
                log_message('warning', 'some query unable process. line : ' . __LINE__ . ', file : ' . __FILE__ . ', method : ' . __METHOD__);
                continue;
            }

            $o_resp->registerXPathNamespace('ns', 'http://xml.agoda.com');
            $arrResultPost = $this->parseHotel($o_resp, $post, $post['flagType'][$idx]);

            //same roomtype.
            for ($index = 0; $index < $post['flagType'][$idx]['totalroom']; $index++)
            {
                $rs = $this->db->query(generateInsertbatch($arrResultPost, $tmpSupplier));
                if ($rs === FALSE)
                {
                    log_message('warning', 'some query unable process.');
                    continue;
                }
            }
        }
        return $this->sessionid;
    }

    /**
     * set and sort prepare hold data before insert into database.
     * @param SimpleXMLElement $o_resp
     * @param type $post
     * @return type
     */
    public function parseHotel(SimpleXMLElement $o_resp, $post, $type)
    {
        $query = '//ns:Hotel';
        $hotellist = $o_resp->xpath($query);
        $_return = array();
        if (empty($hotellist) OR empty($type))
        {
            return array();
        }

        foreach ($hotellist as $hotel)
        {
            $hotel->registerXPathNamespace('ns', 'http://xml.agoda.com');
            $periodAge = $this->getRangeChildAge($hotel);
            $a_hotelItem = array();
            $x_query = './/ns:Room'
                    . '[ns:RemainingRooms >= ' . $type['totalroom'] . ']'
                    . '[((ns:MaxRoomOccupancy/@normalbedding + ns:MaxRoomOccupancy/@extrabeds) * ' . $type['totalroom'] . ') >= ' . $type['adult_request'] . ']' . $this->getQueryServiceCode($post);

            $this->cache_xpath[$x_query] = $hotel->xpath($x_query);
            foreach ($this->cache_xpath[$x_query] as $room)
            {
                $BKF = $this->getIncludeMeal($room);
                $a_hotelItem[] = array(
                    'SessionId' => $this->sessionid,
                    'SpHotelCode' => (string) $hotel->Id,
                    'RoomCatg' => (string) $room->attributes()->id,
                    'RoomType' => $type['shorttype'],
                    'BKF' => $BKF,
                    'Avail' => 'Y',
                    'PriceTotal' => $this->getPriceTotal($room, $post, $type['totalroom']),
                    'Currency' => (string) $room->attributes()->currency,
                    'AdultNum' => $type['adults'],
                    'ChildNum' => $type['children'],
                    'ChildAge1' => $type['age1'],
                    'ChildAge2' => $type['age2'],
                    'CancelPolicyID' => base64_encode(json_encode(array(
                        'id' => (string) $room->attributes()->id,
                        'bkf' => $BKF
                    ))),
                    'MinAge' => $periodAge->Min,
                    'MaxAge' => $periodAge->Max,
                    'ChildOverAge' => '',
                    'CanAmend' => 'N',
                    'CreateDt' => date('Y-m-d H:i:s'),
                    "HotelMessage" => $this->getHotelMessage()
                );
            }
            $_return = array_merge($_return, $this->getBestRoomPrice($a_hotelItem));
        }
        return $_return;
    }

    /**
     * Calculate price total
     * pricetotal = (inclusive x request rooms x nights) + surcharge
     * @param SimpleXMLElement $room
     * @param type $post
     * @param type $num_rooms
     * @return type
     */
    public function getPriceTotal(SimpleXMLElement $room, array $post, $num_rooms = 1)
    {
        $price = 0.0;
        $this->setHotelMessage('');
        if (isset($room->RateInfo->Rate))
        {
            $price = floatval($room->RateInfo->Rate->attributes()->inclusive) * $post['Night'];
        }

        if (isset($room->RateInfo->Surcharges))
        {
            $total_surcharge = 0;
            $hotelMsg = '';
            $hotelMsg_mandatory = '';
            $hotelMessage = '';
            foreach ($room->RateInfo->Surcharges->Surcharge as $s_charge)
            {
                if (strtolower($s_charge->attributes()->charge) == 'mandatory')
                {
                    $total_surcharge += doubleval($s_charge->Rate->attributes()->inclusive);
                    $hotelMsg_mandatory .= (string) $s_charge->Name . ', ';
                }

                if (strtolower($s_charge->attributes()->charge) == 'excluded')
                {
                    $hotelMsg .= (string) $s_charge->Name . ' ' . (string) $s_charge->Rate->attributes()->inclusive . ' ' . (string) $room->attributes()->currency . ', ';
                }
            }

            $hotelMessage .= ($hotelMsg != '') ? 'Pay at hotel : ' . substr($hotelMsg, 0, -2) : '';
            $hotelMessage .= ($hotelMsg_mandatory != '') ? 'Price included : ' . substr($hotelMsg_mandatory, 0, -2) : '';
            if ($hotelMessage != '')
            {
                $this->setHotelMessage($hotelMessage);
            }

            if ($total_surcharge > 0)
            {
                //add surcharge avg per rooms.
                //ticket bug : search n book price with surcharge price doesn't match.
                $surcharge_per_night_room = ($total_surcharge / $post['Night']) / $num_rooms;
                $price = ($surcharge_per_night_room + doubleval($room->RateInfo->Rate->attributes()->inclusive)) * $post['Night'];
            }
        }
        return $price;
    }

    /**
     *
     * @param SimpleXMLElement $room
     * @param array $post
     */
    public function getHotelMessage()
    {
        return $this->hotelMsg;
    }

    /**
     *
     * @param SimpleXMLElement $room
     * @param array $post
     */
    public function setHotelMessage($hotelMsg = '')
    {
        $this->hotelMsg = htmlspecialchars($hotelMsg);
    }

    /**
     * Meal incluse transform.
     * @param SimpleXMLElement $room
     * @return string
     */
    public function getIncludeMeal(SimpleXMLElement $room)
    {
        if (!isset($room->RateInfo->Included))
        {
            return 'Room Only';
        }

        return (string) $room->RateInfo->Included;
    }

    /**
     * Range of children
     * if not range from supplier, default 2 - 12 years.
     * @param SimpleXMLElement $hotel
     * @return \stdClass
     */
    public function getRangeChildAge(SimpleXMLElement $hotel)
    {
        $o = new \stdClass();
        if (isset($hotel->PaxSettings))
        {
            $o->Min = intval($hotel->PaxSettings->attributes()->infantage);
            $o->Max = intval($hotel->PaxSettings->attributes()->childage);
            if ($o->Max == 0)
            {
                $o->Max = 18;
            }
        }
        else
        {
            $o->Min = 2;
            $o->Max = 12;
        }

        return $o;
    }

    /**
     * get best lowest price.
     * @param type $hotelItem
     * @return type
     */
    public function getBestRoomPrice($hotelItem)
    {

        $lowestprice = array();
        foreach ($hotelItem as $idx => $item)
        {
            $lowestprice[$item['RoomCatg'] . $item['BKF'] . $item['AdultNum'] . $item['ChildNum'] . $item['ChildAge1'] . $item['ChildAge2']][$idx] = $item['PriceTotal'];
        }

        $_return = array();
        foreach ($lowestprice as $item)
        {
            $_return[] = $hotelItem[array_search(min($item), $item)];
        }
        unset($lowestprice);
        return $_return;
    }

    /**
     * destory cache array ,free memory.
     */
    public function __destruct()
    {
        $this->cache_xpath = array();
    }

    /**
     * 
     * @param array $post
     * @throws Exception
     */
    public function groupRoom(array &$post)
    {
        //room request.
        $roomtype = getRoomType($post);
        if (count($roomtype) > 9)
        {
            throw new Exception('Maximun 9 rooms per request.');
        }

        //group room request
        $adult_request = 0;
        foreach ($roomtype as $type)
        {
            //Halt system when Request extra bed for child.
            //in case 2ad+1ch , 2ad+2ch RQ = N
            if ($type['rqbed'] == 'N' AND $type['adults'] == 2 AND $type['children'] > 0)
            {
                throw new Exception('not support case : 2 adults with children and require child beds. (2adults + 1,2 children, reqbed=N)');
            }

            if ($type['rqbed'] == 'Y' AND $type['adults'] == 2 AND $type['shorttype'] != 'TPL')
            {
                throw new Exception('check your RoomType name.');
            }

            $type['age1'] = $type['age1'] == "" ? 0 : $type['age1'];
            $type['age2'] = $type['age2'] == "" ? 0 : $type['age2'];
            $type['adult_request'] = ($type['adults'] + $type['children']) * $type['totalroom'];


            $post['prepareRequest'][$type['fulltype'] . $type['adults'] . $type['children'] . $type['age1'] . $type['age2']] = $type;
        }
    }

    /**
     * 
     * @param array $post
     * @return boolean
     * @throws Exception
     */
    public function getQueryServiceCode(array $post)
    {
        if (!isset($post['ServiceCode']) OR empty($post['ServiceCode']))
        {
            return false;
        }

        $roomcatgCode = GetListRoomCatgCode($post['ServiceCode'], $this->suppliercode);

        if (empty($roomcatgCode))
        {
            throw new Exception('not roomcategory found.');
        }


        foreach ($roomcatgCode as $code)
        {
            $str .= '(@id = "' . $code['SpCode'] . '") or ';
        }

        return '[' . substr($str, 0, -4) . ']';
    }

}
