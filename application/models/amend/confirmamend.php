<?php

namespace models\amend;

/**
 * Description of confirmamend
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;

class confirmAmend extends Model {

    public function getRequest(&$post) {
        $o_resp = parse_xml($post['response']['content'][0]);
        $o_resp->registerXPathNamespace('ns', 'http://xml.agoda.com');
        if (isset($o_resp->ErrorMessages))
        {
            $strerr = '';
            foreach ($o_resp->ErrorMessages->ErrorMessage as $errMsg)
            {
                $strerr .= 'Error id : ' . $errMsg->attributes()->id . ', Error message : ' . $errMsg;
            }

            throw new \Exception($strerr, 2);
        }

        $AdditionalCharge = @array_shift($o_resp->xpath('//ns:AdditionalCharge'));
        $post['ItineraryID'] = (string) $o_resp->Amendment->Reference;

        $post['request'][0] = '<?xml version="1.0" encoding="utf-8" ?>'
                . '<ConfirmAmendmentRequest siteid="' . $post['Username'] . '" apikey="' . $post['Password'] . '" xmlns="http://xml.agoda.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                . '<BookingID>' . $o_resp->Amendment->BookingID . '</BookingID>'
                . '<Reference>' . $o_resp->Amendment->Reference . '</Reference>'
                . $AdditionalCharge->asXML()
                . '<CreditCardInfo>'
                . '<Cardtype>Visa</Cardtype>'
                . '<Number>1234567890001234</Number>'
                . '<ExpiryDate>112016</ExpiryDate>'
                . '<Cvc>832</Cvc>'
                . '<HolderName>Agoda Customer</HolderName>'
                . '<CountryOfIssue>NL</CountryOfIssue>'
                . '<IssuingBank>RABO</IssuingBank>'
                . '</CreditCardInfo>'
                . '</ConfirmAmendmentRequest>';
    }

}
