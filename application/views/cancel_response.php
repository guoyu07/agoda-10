<?php
$this->output->setContentType('xml');
$xmlResponse = array(
    "ResNo" => $resno,
    "Hbooking" => $hbid,
    "ErrorDescription" => $errmsg,
    "CancelResult" => $is_result
);

echo f_RP_CancelRsvn($xmlResponse);
