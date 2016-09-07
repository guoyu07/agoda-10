<?php

namespace models;

/**
 * Description of policy_model
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Model;
use \Exception;

class policy_model extends Model
{

    var $suppliercode = '';

    public function __construct()
    {
        $CI = getInstance();
        $this->suppliercode = $CI->suppliercode;
    }

    public function getPolicies(&$post)
    {
        $this->decodeCancelPolicy($post);
        $policies = array();
        foreach ($post['response']['content'] as $idx_response => $resp)
        {
            $o_xml = parse_xml($resp);
            $o_xml->registerXPathNamespace('ns', 'http://xml.agoda.com');

            foreach ($post['CancelPolicyID'] as $idx_policy => $policyId)
            {
                //when call from get policy
                if (isset($post['line']))
                {
                    $query_xpath = '//ns:Room[@id = "' . $policyId['id'] . '" and @lineitemid = "' . $post['line'][$idx_response] . '"]';
                }
                else
                {
                    $query_xpath = '//ns:Room[@id = "' . $policyId['id'] . '"]';
                }

                $a_policy_item = $o_xml->xpath($query_xpath);
                $a_policy_item = array_filter($a_policy_item);
                if (empty($a_policy_item))
                {
                    continue;
                }

                $oRoomcatg = GetListWscodeRoomCatgs($policyId['id'], $this->suppliercode);
                $policy = $a_policy_item[0]->Cancellation;
                $policy->registerXPathNamespace('ns', 'http://xml.agoda.com');
                $a_policy_param = $policy->xpath('.//ns:PolicyParameter[@days > 0]');

                //bot 15343
                //add condition for filter about after effective policy.
                //$a_policy_date = $policy->xpath('.//ns:PolicyDate[not(@after)][ns:Rate/@inclusive > 0]');
                $a_policy_date = $policy->xpath('.//ns:PolicyDate[ns:Rate/@inclusive > 0]');

                if (empty($a_policy_date) OR empty($a_policy_param))
                {
                    continue;
                }

                foreach ($a_policy_param as $idx => $penalty)
                {

                    $exdays = intval($penalty->attributes()->days);
                    //when date equal checkin.
                    if ($exdays == 0)
                    {
                        continue;
                    }

                    //exday with non refund
                    if ($exdays >= 365)
                    {
                        $exdays = datediffST(date('Y-m-d'), $post['FromDt']);

                        //when policy using "after date charge."
                        //Ticket : 16550
                        if (isset($a_policy_date[$idx]->attributes()->after))
                        {
                            $exdays -= 1;
                        }

                        if ($exdays == 0)
                        {
                            $exdays = 1;
                        }
                    }

                    //penalty types
                    switch (strtoupper((string) $penalty->attributes()->charge))
                    {
                        case 'N': //night case.
                            $night = intval($penalty);
                            $charge_rate = floatval($a_policy_date[$idx]->Rate->attributes()->inclusive) * $night;
                            $charge_type = 'Amount';
                            break;
                        case 'P':
                            $charge_type = 'Percent';
                            $charge_rate = intval($penalty);
                            break;
                    }

                    //calc FromDate.
                    $date_modify = new \DateTime($post['FromDt']);
                    $date_modify->modify('-' . $exdays . ' day');
                    $fromdt = $date_modify->format('Y-m-d');
                    if (($exdays == 1) && (strtotime($fromdt) < strtotime(date('Y-m-d'))))
                    {
                        $fromdt = date('Y-m-d');
                    }

                    //BFType
                    $bkf = GetList21MealTypeCode($policyId['bkf'], $this->suppliercode)->MealTypeCode;
                    $policy_key = (string) $oRoomcatg->wscode . $bkf . $fromdt;
                    if (array_key_exists($policy_key, $policies) AND $charge_type == 'Amount')
                    {
                        $policies[$policy_key]['ChargeRate'] += $charge_rate;
                    }
                    else
                    {
                        $policies[$policy_key] = array(
                            'BFType' => $bkf,
                            'RoomCatgCode' => (string) $oRoomcatg->wscode,
                            'RoomCatgName' => (string) $oRoomcatg->SpLongName,
                            'FromDate' => $fromdt,
                            'ToDate' => $post['FromDt'],
                            'ExCancelDays' => $exdays,
                            'ChargeType' => $charge_type,
                            'ChargeRate' => $charge_rate,
                            'Description' => (string) $policy->PolicyText,
                            'Currency' => (string) $a_policy_item[0]->attributes()->currency
                        );
                    }
                }
            }
        }
        $policies = array_values($policies);
        return $policies;
    }

    public function decodeCancelPolicy(&$post)
    {
        $post['CancelPolicyID'] = array_map('base64_decode', explode('#|#', $post['CancelPolicyID']));
        foreach ($post['CancelPolicyID'] as &$policy)
        {
            $policy = isJson($policy, true);
        }
    }

}
