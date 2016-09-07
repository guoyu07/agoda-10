<?php

namespace models\amend;

/**
 * Description of amendrequest
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;

class requestAmend extends Model {

    public function getRequest(&$post) {

        if (!isset($post['room_request']))
        {
            return;
        }

        //Amendment request
        if ((isset($post['OrgResId']) AND $post['OrgResId'] != '') AND ( isset($post['OrgHBId']) AND $post['OrgHBId'] != ''))
        {
            $orgHBID = isJson(base64_decode($post['OrgHBId']), true);
            $post['request'] = '<?xml version="1.0" encoding="utf-8" ?>'
                    . '<AmendmentRequest siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                    . '<BookingID>' . $orgHBID['BookingId'] . '</BookingID>'
                    . '<Current>'
                    . '<CheckIn>' . $orgHBID['FromDt'] . '</CheckIn>'
                    . '<CheckOut>' . $orgHBID['ToDt'] . '</CheckOut>'
                    . '</Current>'
                    . '<Requested>'
                    . '<CheckIn>' . $post['FromDt'] . '</CheckIn>'
                    . '<CheckOut>' . $post['ToDt'] . '</CheckOut>'
                    . '</Requested>'
                    . '</AmendmentRequest>';
        }

        unset($post['response_gateway']);
        //unset($post['room_request']);
        $post['request'] = array($post['request']);
    }

}
