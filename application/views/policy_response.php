<?php

$this->output->setContentType('xml');


if (isset($arrViewCancelPolicy))
{
    echo f_RP_ViewCancelPolicy($arrViewCancelPolicy);
}
else
{
    echo f_RP_GetCancelPolicy_V2($getpolicy);
}



