<?php

namespace controllers;

/**
 * Description of GetCancelPolicy
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Controller;
use \Exception;

class GetCancelPolicy extends Controller
{

    var $url;
    var $opts = array();
    var $suppliercode;
    var $temp_log = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->suppliercode = GetSupplierCode();
        save_log_data();
    }

    public function index()
    {
        $post = XMLPost2Array($this->input->post());
        if (isset($post['LoginName']))
        {
            $post['Username'] = $post['LoginName'];
            unset($post['LoginName']);
        }

        if (isset($post['DocID']))
        {
            $post['HBooking'] = $post['DocID'];
            unset($post['DocID']);
        }

        $this->load->model('search_model');
        $this->load->model('policy_model');
        $this->load->library('httpcurl');


        $bookidlist = array_map('base64_decode', explode('#|#', $post['HBooking']));
        $Policies = array();
        foreach ($bookidlist as $idx => $bookid)
        {
            //get booking split room type;
            list($book_room_id, $hbpost) = explode(';', str_replace(':{', ';{', $bookid));
            $post = array_merge($post, isJson($hbpost, true));

            try
            {
                $this->searchProduct($post);
                $Policies = array_merge($Policies, $this->policyItem($post));
            } catch (Exception $ex)
            {
                log_message('info', 'Exception : ' . $ex->getMessage() . '; LINE : ' . $ex->getLine() . '; FILE : ' . $ex->getFile());
            }
        }

        unset($bookidlist);
        $o_hotel = @GetHotelCode($post['HotelId'], $this->suppliercode);
        $arrMakePolicyXML = array(
            "ResNo" => $post['ResNo'],
            "HBooking" => $post['HBooking'],
            "HotelId" => $post['HotelId'],
            "HotelName" => $o_hotel->SpHotelName,
            "arrPolicy" => $Policies
        );


        $this->load->view('policy_response', array('getpolicy' => $arrMakePolicyXML));
        xmllog21s('GetCancelPolicy', $post);
    }

    private function searchProduct(&$post)
    {
        //reset when exitst data.
        //15329
        if (!empty($post['SpHotelList']))
        {
            $post['SpHotelList'] = array();
        }
        $this->search_model->fetchHotelList($post);
        $this->search_model->groupRoom($post);
        $this->search_model->request($post);

        //Send xml request to supplier.
        $post['response'] = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlService.svc/search_lrv2')
                ->setSSL(false)
                ->send($post['request']);

        $post['Night'] = datediffST($post['FromDt'], $post['ToDt']);
        $sessionid = $this->search_model->response($post);
        $post['response_xml_search_prod'] = @$this->load->view('search_response', array('sessionId' => $sessionid, 'post' => $post), true);

        if ($this->input->get_post('Debug') == '1')
        {
            echo "\n========= [searchProduct] ==========\n";
            print_r($post);
        }
    }

    private function policyItem(&$post)
    {
        $o_xml = parse_xml($post['response_xml_search_prod']);
        $policyid = $o_xml->xpath('//@CancelPolicyId');
        $policyid = explode('#|#', (string) $policyid[0]);
        $roomcategories = $o_xml->xpath('//RoomCateg');

        foreach ($roomcategories as $idx => $item)
        {
            if ($item->attributes()->Code . $item->attributes()->BFType == $post['RoomCatgWScode'] . $post['BFType'])
            {
                $post['CancelPolicyID'] = $policyid[$idx];
            }
        }

        if ($this->input->get_post('Debug') == '1')
        {
            echo "\n========= [" . __METHOD__ . "] ==========\n";
            print_r($post);
        }

        return $this->policy_model->getPolicies($post);
    }

}
