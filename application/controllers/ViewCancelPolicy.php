<?php

namespace controllers;

use core\Controller;
use \Exception;

class ViewCancelPolicy extends Controller {

    var $url;
    var $opts = array();
    var $suppliercode;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->suppliercode = GetSupplierCode();
        save_log_data();
    }

    public function index() {
        $post = XMLPost2Array($this->input->post());
        $post['Debug'] = $this->input->post('Debug');
        //if request from B2B.
        if (!isset($post['HotelId']) AND $post['HotelCode'] != '')
        {
            $post['HotelId'] = $post['HotelCode'];
        }

        $this->load->model('search_model');
        $this->load->model('policy_model');
        $this->load->library('httpcurl');
        try
        {
            //search products.
            $this->searchHotels($post);

            //view cancel policy.
            $policies = $this->policy_model->getPolicies($post);
        } catch (Exception $ex)
        {
            log_message('info', 'Exception : ' . $ex->getMessage() . '; LINE : ' . $ex->getLine() . '; FILE : ' . $ex->getFile());
            $policies = array();
        }

        $o_hotel = GetHotelCode($post['HotelId'], $this->suppliercode);
        $data['arrViewCancelPolicy'] = array(
            "HotelId" => $post['HotelId'],
            "HotelName" => $o_hotel->SpHotelName,
            "Policies" => $policies
        );
        unset($o_hotel);
        $this->load->view('policy_response', $data);
        xmllog21s('ViewCancelPolicy', $post);
    }

    public function searchHotels(&$post) {
        $this->search_model->fetchHotelList($post);
        $this->search_model->groupRoom($post);
        $this->search_model->request($post);

        //Send xml request to supplier.
        $post['response'] = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlService.svc/search_lrv2')
                ->setSSL(false)
                ->send($post['request']);

        if ($this->input->get_post('Debug') == 1)
        {
            echo "\n========= [" . __METHOD__ . "] ==========\n";
            print_r($post);
        }
    }

}
