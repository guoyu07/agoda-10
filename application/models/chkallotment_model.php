<?php

namespace models;

/**
 * Description of chkAllotment_model
 *
 * @author Tong
 */
use core\Model;
use \Exception;

class chkallotment_model extends Model
{

    public function request(&$post)
    {
        if (!isset($post['prepareRequest']))
        {
            throw new Exception('Please call fetchHotelList and request function before use. (search_model)');
        }

        //build room reuqest.
        $numAdults = 0;
        foreach ($post['prepareRequest'] as $roomrequest)
        {
            $numAdults += $roomrequest['adult_request'];
        }

        $numRooms = count($post['prepareRequest']);

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
                    . '<Rooms>' . $numRooms . '</Rooms>'
                    . '<Adults>' . $numAdults . '</Adults>'
                    . '<Children>0</Children>'
                    . '<Language>en-us</Language>'
                    . '<Currency>USD</Currency>'
                    . '</AvailabilityRequestV2>';
        }
         
        //move to fist array
        $last = end($post['request']);
        array_pop($post['request']);
        array_unshift($post['request'], $last);
        unset($last);
    }

    /**
     * solution for problem 'last remaining room' (E-mail reference).
     * @param array $post
     * @param string $roomQuery
     * @return boolean
     * @throws Exception
     */
    public function isAllotment(&$post, $roomQuery)
    {
        if ($roomQuery == '' OR $roomQuery == NULL)
        {
            throw new Exception('Check room xpath query.');
        }
        
        
        $o_resp = array_shift($post['response']['content']);
        $o_resp = parse_xml($o_resp);
        $o_resp->registerXPathNamespace('ns', 'http://xml.agoda.com');

        $a_request_data = $o_resp->xpath($roomQuery);
        $a_request_data = array_filter($a_request_data);
        if (empty($a_request_data))
        {
            return FALSE;
        }
        
        //if pass remove first request/response
        array_shift($post['request']);
        array_shift($post['response']['info']);
        return TRUE;
    }

}
