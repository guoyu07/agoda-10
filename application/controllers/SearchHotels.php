<?php

namespace controllers;

use core\Controller;
use \Exception;

/**
 * Description of SearchHotels
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
class SearchHotels extends Controller
{

    var $url;
    var $opts = array();
    var $suppliercode;

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
        $post['Debug'] = $this->input->post('Debug');
        $sessionid = '';

        $this->load->model('search_model');
        $this->load->library('httpcurl');

        try
        {
            $this->search_model->fetchHotelList($post);
            $this->search_model->groupRoom($post);
            $this->search_model->request($post);
            
            //Send xml request to supplier.
            $this->benchmark->mark('search_exec_time_start');
            $post['response'] = $this->httpcurl
                    ->setHeader('Content-Type', 'text/xml')
                    ->setUri('XmlService.svc/search_lrv2')
                    ->setSSL(false)
                    ->send($post['request']);
            $this->benchmark->mark('search_exec_time_stop');
            
            //post search products.
            $sessionid = $this->search_model->response($post);
        } catch (Exception $ex)
        {
            log_message('info', 'Exception : ' . $ex->getMessage() . '; LINE : ' . $ex->getLine() . '; FILE : ' . $ex->getFile());
            $post['response']['errors'][] = $ex->getMessage();
        }

        @$this->load->view('search_response', array('sessionId' => $sessionid, 'post' => $post));
        collect_statis_search($post);
    }

}
