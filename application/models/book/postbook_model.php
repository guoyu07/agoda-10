<?php

namespace models\book;

/**
 * Description of postbook_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;
use \Exception;

class postbook_model extends Model
{

    var $cache_bookingid = array();
    protected $CI = null;

    public function __construct()
    {
        $this->CI = &getInstance();
    }

    public function getBookingDetails(&$post)
    {
        foreach ($post['response']['content'] as $idx => $resp)
        {
            $o_xml = parse_xml($resp);
            if (isset($o_xml->ErrorMessages))
            {
                $strerr = '';
                foreach ($o_xml->ErrorMessages->ErrorMessage as $errmsg)
                {
                    $strerr .= 'Error id : ' . $errmsg->attributes()->id . ', Error message : ' . (string) $errmsg;
                }
                throw new Exception($strerr);
            }

            $this->cache_bookingid[$idx] = array(
                'id' => (string) $o_xml->BookingDetails->Booking->attributes()->id,
                'ItineraryID' => (string) $o_xml->BookingDetails->Booking->attributes()->ItineraryID);

            $post['request'][$idx] = '<?xml version="1.0" encoding="utf-8" ?>'
                    . '<BookingDetailsRequestV2 siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                    . '<BookingID>' . $this->cache_bookingid[$idx]['id'] . '</BookingID>'
                    . '</BookingDetailsRequestV2>';
        }
    }

    public function getResponse(&$post)
    {
         $post['book_roomtype_info'] = array();
        foreach ($post['response']['content'] as $idx => $resp)
        {

            $o_xml = parse_xml($resp);
            if (isset($o_xml->ErrorMessages))
            {
                $strerr = '';
                foreach ($o_xml->ErrorMessages->ErrorMessage as $errmsg)
                {
                    $strerr .= 'Error id : ' . $errmsg->attributes()->id . ', Error message : ' . (string) $errmsg;
                }
                log_message('NOTICE', $strerr);
                unset($o_xml);
            }

            for ($index = 0; $index < $post['flagType'][$idx]['totalroom']; $index++)
            {
                $post['book_roomtype_info'][] = array(
                    'currency' => (string) $o_xml->Bookings->Booking->Payment->PaymentRateInclusive->attributes()->currency,
                    'price' => floatval($o_xml->Bookings->Booking->Payment->PaymentRateInclusive)  / $post['flagType'][$idx]['totalroom'],
                    'rtype' => $post['flagType'][$idx]['shorttype'],
                    'adults' => $post['flagType'][$idx]['adults'],
                    'child' => $post['flagType'][$idx]['children'],
                    'age1' => $post['flagType'][$idx]['age1'],
                    'age2' => $post['flagType'][$idx]['age2'],
                    'status' => (string) $o_xml->Bookings->Booking->Status
                        ) + $this->cache_bookingid[$idx];
            }
        }

        $a_comp = $this->getCompleteService($post);
        $a_comp['RoomCatg'] = $this->getRoomCatg($post);
        $a_comp['RoomCatg'][0]['Room'] = $this->getRoomType($post, $idx);

        foreach ($a_comp['RoomCatg'][0]['Room'] as &$night)
        {
            $night['NightPrice'] = $this->getNightPrice($night['TotalPrice'], $post);
        }

        //collect last generate hbooking id.
        //use auto cancel part when system was emergency.
        array_push($this->CI->cache_bookingid_generated, $a_comp['Id']);
        return $a_comp;
    }

    /**
     *
     * @param type $post
     * @return type
     */
    public function getCompleteService($post)
    {
        //Amendment status
        $status = 'CONF';
        if ((isset($post['OrgResId']) AND $post['OrgResId'] != '') AND ( isset($post['OrgHBId']) AND $post['OrgHBId'] != ''))
        {
            $status = 'AMENDCONF';
        }

        //concat id
        $id = '';
        $ref = '';
        foreach ($post['book_roomtype_info'] as $item)
        {
            $id .= $item['id'] . ',';
            $ref .= substr($id, 0, -1) . "|" . $item['ItineraryID'] . ',';
            $currency = $item['currency'];
            //temporary close multi roomtype
            break;
        }

        $a_id['HotelId'] = $post['HotelId'];
        $a_id['RoomCatgWScode'] = $post['RoomCatgWScode'];
        $a_id['BFType'] = $post['BFType'];
        $a_id['FromDt'] = $post['FromDt'];
        $a_id['ToDt'] = $post['ToDt'];

        //add lineitemid for getcancelpolicy
        $a_id['line'] = $this->CI->prebook_model->lineitemid;

        $keys = array_values(preg_grep("/Nbr/", array_keys($post)));
        foreach ($keys as $kNbr)
        {
            $a_id[$kNbr] = $post[$kNbr];
        }
        $arrCompleteService = array(
            'Id' => base64_encode(substr($id, 0, -1) . ':' . json_encode($a_id)),
            'RefHBId' => substr($ref, 0, -1),
            'CanAmend' => 'False',
            'VoucherNo' => 'NONE',
            'Message' => $this->CI->prebook_model->hotelmsg,
            'EMG' => '',
            'VoucherDt' => date('Y-m-d'),
            'RPCurrency' => $currency,
            'Status' => $status,
            'InternalCode' => _SUPPLIERCODE,
            'HotelId' => $post['HotelId'],
            'HotelName' => htmlspecialchars((string) GetHotelCode($post['HotelId'], GetSupplierCode())->SpHotelName),
            'FromDt' => $post['FromDt'],
            'ToDt' => $post['ToDt'],
            'RoomCatg' => array()
        );
        return $arrCompleteService;
    }

    /**
     *
     * @param type $post
     * @return type
     */
    private function getRoomCatg($post)
    {

        $arrRoomCatg[0] = array(
            'CatgId' => $post['RoomCatgWScode'],
            'CatgName' => $post['RoomCatgName'],
            'Market' => '',
            'Avail' => 'Y',
            'BFType' => $post['BFType'],
            'RequestDes' => $post['RequestDes'],
            'Room' => array(),
        );
        return $arrRoomCatg;
    }

    /**
     *
     * @param type $post
     * @return type
     */
    private function getRoomType($post)
    {
        $arrRooms = array();
        foreach ($post['Rooms'] as $idxRoom => $room)
        {
            $Totalprice = $post['book_roomtype_info'][$idxRoom]['price'];
            $arrRooms[$idxRoom] = array(
                "ServiceNo" => date("YmdHis") . rand(0, 999),
                "RoomType" => $room['RoomTypeName'],
                "SeqNo" => intval($idxRoom + 1),
                "AdultNum" => intval($room['AdultNum']),
                "ChildAge1" => $room['Age1'] == "0" ? "" : intval($room['Age1']),
                "ChildAge2" => $room['Age2'] == "0" ? "" : intval($room['Age2']),
                "TotalPrice" => $Totalprice,
                "CommissionPrice" => "0.00",
                "NetPrice" => $Totalprice,
                "NightPrice" => array(),
                "PaxInformation" => $room['PaxInformation_request']
            );
        }
        return $arrRooms;
    }

    /**
     *
     * @param type $AvgPriceRoom
     * @param type $post
     * @return type
     */
    private function getNightPrice($AvgPriceRoom, $post)
    {
        $arrNightPrice = array();
        if ($AvgPriceRoom > 0)
        {
            $indexNight = 0;
            while ($indexNight < $post['Night'])
            {
                $arrNightPrice[$indexNight] = Array
                    (
                    "AccomPrice" => cal_Price($AvgPriceRoom / $post['Night']),
                    "ChildMinAge" => 2,
                    "ChildMaxAge" => 17,
                    "ChildInfo" => "",
                    "MinstayDay" => "0.00",
                    "MinstayType" => "NONE",
                    "MinstayRate" => "0.00",
                    "MinstayPrice" => "0.00",
                    "CompulsoryName" => "NONE",
                    "CompulsoryPrice" => "0.00",
                    "SupplementName" => "NONE",
                    "SupplementPrice" => "0.00",
                    "PromotionName" => "NONE",
                    "PromotionValue" => "False",
                    "PromotionBFPrice" => "0",
                    "EarlyBirdType" => "NONE",
                    "EarlyBirdRate" => "0.00",
                    "EarlyBirdPrice" => "0.00",
                    "CommissionType" => "NONE",
                    "CommissionRate" => "0.00",
                    "CommissionPrice" => "0.00"
                );
                $indexNight++;
            }
        }
        return $arrNightPrice;
    }

    function getCache_bookingid()
    {
        return $this->cache_bookingid;
    }

}
