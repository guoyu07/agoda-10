<?php

namespace models;

/**
 * Description of cancel_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;
use \Exception;

class cancel_model extends Model
{

    public function getCancel(&$post)
    {

        if (!isset($post['bookRoomtypeId']) OR $post['bookRoomtypeId'] == '')
        {
            throw new Exception('no booking id.');
        }

        $book_room_id = explode(',', $post['bookRoomtypeId']);

        //cancel room type
        foreach ($book_room_id as $bookid)
        {
            $post['request'][] = '<?xml version="1.0" encoding="utf-8" ?>'
                    . '<CancellationRequestV2 siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                    . '<BookingID>' . $bookid . '</BookingID>'
                    . '</CancellationRequestV2>';
        }
    }

    public function getConfirm(&$post)
    {
        if (!isset($post['bookRoomtypeId']) OR $post['bookRoomtypeId'] == '')
        {
            throw new Exception('no booking id.');
        }

        $post['request'] = array_filter($post['request']);
        if (!empty($post['request']))
        {
            $post['request'] = NULL;
        }

        //get info from cancel request.
        $book_room_id = explode(',', $post['bookRoomtypeId']);

        foreach ($post['response']['content'] as $idx => $resp)
        {
            $o_xml = parse_xml($resp);
            if ($o_xml->attributes() != '200')
            {
                $err = '';
                foreach ($o_xml->ErrorMessages->ErrorMessage as $errmsg)
                {
                    $err .= (string) $errmsg . "; ";
                }
                log_message('INFO', substr($err, 0, -2) . " (Booking ID : " . $book_room_id[$idx] . ")");
                continue;
            }

            $xml_refund = '';
            if (isset($o_xml->CancellationSummary->Refund))
            {
                $xml_refund = $o_xml->CancellationSummary->Refund->asXML();
            }

            $post['request'][] = '<?xml version="1.0" encoding="utf-8" ?>'
                    . '<ConfirmCancellationRequestV2 siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                    . '<BookingID>' . $o_xml->CancellationSummary->BookingID . '</BookingID>'
                    . '<Reference>' . $o_xml->CancellationSummary->Reference . '</Reference>'
                    . '<CancelReason>17</CancelReason>'
                    . $xml_refund
                    . '</ConfirmCancellationRequestV2>';
        }

        //check request
        if ($post['request'] == NULL)
        {
            throw new Exception('Cancel Confirm request was problem.');
        }
    }

}
