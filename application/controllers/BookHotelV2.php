<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace controllers;

/**
 * Description of BookHotelV2
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Controller;
use Exception;

class BookHotelV2 extends Controller
{

    private $requests = array();
    private $responses = array();
    public $suppliercode = 0;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->suppliercode = GetSupplierCode();
        save_log_data();
    }

    public function __destruct()
    {
        $this->request = array();
        $this->response = array();
    }

    public function index()
    {
        $this->load->library('httpcurl');
        $a_post = convertXMLPOSTBooking($this->input->post());
        try
        {
            $this->search($a_post);
            $this->prebook($a_post);
            $this->getBooking($a_post);
        } catch (Exception $exc)
        {
            print_r($exc->getMessage());
        }

        print_r(collectLog());
        exit;

        //concat room category when :::
        //1. same hotel(HOTEL,XML) and same period(XML)
        if ($a_post[0]['isConcatHBID'] == 'TRUE' && $is_error == 'FALSE')
        {
            $id = '';
            $ref = '';
            foreach ($arrCompleteServices as $item)
            {
                $id .= $item['Id'] . '#|#';
                $ref .= $item['RefHBId'] . '#|#';
            }
            foreach ($arrCompleteServices as &$fill)
            {
                $fill['Id'] = substr($id, 0, -3);
                $fill['RefHBId'] = substr($ref, 0, -3);
            }
        }

        //final part
        $this->load->view('book_response', array('post' => $a_post, 'arrCompleteServices' => $arrCompleteServices, 'err' => $err));
        xmllog21s('BookingHotel', $this->temp_log);
    }

    /**
     * 
     * @param array $a_post
     * @throws Exception
     */
    private function search(array $a_post)
    {
        if (empty($a_post))
        {
            throw new Exception('check request array.');
        }
        //load
        $this->load->model('search_model');
        $_request = array();
        foreach ($a_post as $post)
        {
            $this->search_model->fetchHotelList($post);
            $this->search_model->request($post);
            $_request = array_merge($_request, $post['request']);
        }

        $this->requests = $_request;
        $this->responses = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlService.svc/search_lrv2')
                ->setSSL(false)
                ->send($this->requests);

        collectLog(__FUNCTION__, $this->requests, $this->responses);
    }

    /**
     * 
     * @param array $a_post
     * @throws Exception
     */
    private function prebook(array $a_post)
    {
        if (empty($a_post))
        {
            throw new Exception('check request array.');
        }

        $this->load->model('bookV2/prebook_model');
        $_request = array();
        foreach ($a_post as $idx => $post)
        {
            $post['response']['content'][0] = $this->responses['content'][$idx];
            $post['query'] = $this->prebook_model->getQueryProduct(
                    $this->prebook_model->getRoomcategory($post), $this->prebook_model->getMealType($post), $post
            );

            //return reference variable $post['prepareRequest'];
            $this->search_model->groupRoom($post);
            $roomProduct = $this->prebook_model->getRoomProduct($post);
            $_request[] = $this->prebook_model->getRequest($post, $roomProduct);
        }

        $this->requests = $_request;
        $this->responses = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlBookService.svc/book_v2')
                ->setSSL(false)
                ->send($this->requests);

        collectLog(__FUNCTION__, $this->requests, $this->responses);
    }

    /**
     * 
     * @param array $a_post
     * @throws Exception
     */
    private function getBooking(array $a_post)
    {
        if (empty($a_post))
        {
            throw new Exception('check request array.');
        }

        $this->load->model('bookV2/postbook_model');
        $_request = array();
        foreach ($a_post as $idx => $post)
        {
            $post['response']['content'][0] = $this->responses['content'][$idx];
            $this->postbook_model->getBookingDetails($post);
            $_request = array_merge($_request, $post['request']);
        }

        $this->requests = $_request;
        $this->responses = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlBookService.svc/bookdetail_v2')
                ->setSSL(false)
                ->send($this->requests);
        collectLog(__FUNCTION__, $this->requests, $this->responses);
    }

    /**
     * 
     * @param array $a_post
     */
    private function postbook(array $a_post)
    {
        if (empty($a_post))
        {
            throw new Exception('check request array.');
        }

        $_request = array();
        foreach ($a_post as $idx => $post)
        {
            $post['response']['content'][0] = $this->responses['content'][$idx];
            //return reference variable $post['prepareRequest'];
            $this->search_model->groupRoom($post);
            $this->postbook_model->getResponse($post);
        }

        collectLog(__FUNCTION__, $this->requests, $this->responses);
    }

}
