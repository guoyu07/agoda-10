<?php

$this->output->setContentType('xml');

$arrCompleteServices = array_filter($arrCompleteServices);
if (empty($arrCompleteServices))
{
    $arrCompleteServices = array();
}

if (isset($err['response']['errors']))
{
    $msg = '';
    foreach ($err['response']['errors'] as $err)
    {
        $msg .= $err;
    }
    echo $RP = f_error_Please_send_requestXML('BookingHotel', 'BookHotel_Response', $msg);
}
else
{
    $abooking = array(
        "ResNo" => randomID(),
        "Status" => $arrCompleteServices[0]['Status'],
        "PaxPassport" => $post[0]['PaxPassport'],
        "OSRefNo" => $post[0]['OSRefNo'],
        "FinishBook" => $post[0]['FinishBook'],
        "RPCurrency" => $arrCompleteServices[0]["RPCurrency"],
        "CompleteService" => $arrCompleteServices,
        "UnCompleteService" => array()
    );
    echo generateXMLBookHotelInfo($abooking);
}


