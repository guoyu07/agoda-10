<?php

if (isset($post['Debug']) AND $post['Debug'] == 1)
{
    print_r($post);
}

if (!isset($CI))
{
    $CI = &getInstance();
}

$CI->output->setContentType('xml');

if (!isset($post))
{
    $post = XMLPost2Array($CI->input->post());
}

//if not request compress data,set output as xml.
$Accesspage = '';
if (isset($post['Compress']) AND strtoupper($post['Compress']) == 'TRUE')
{
    $Accesspage = 'B2B';
}

if (isset($post['response']['errors']))
{
    $msg = '';
    foreach ($post['response']['errors'] as $err)
    {
        $msg .= $err;
    }
    $RP = f_error_Please_send_requestXML('Service_SearchHotel', 'SearchHotel_Response', $msg);
}
else
{
    $arrRP = f_create_text_rp_searchhotels($post, $CI->input->get());
    $RP = createRPSearch($sessionId, $arrRP);
}

//Last response for search.
// if RP variable is not set, i guess their come from exception error....understand?
echo compressXML($post['Compress'], $RP, $Accesspage);
