<?php

namespace controllers;

/**
 * Description of BookHotel
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Controller;
use \Exception;

class BookHotel extends Controller
{

    var $suppliercode;
    var $temp_log = array();
    var $cache_bookingid_generated = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->suppliercode = GetSupplierCode();
        save_log_data();
    }

    /**
     * start process.
     */
    public function index()
    {
        $a_post = convertXMLPOSTBooking($this->input->post());
        //load
        $this->load->model('search_model');
        $this->load->model('chkallotment_model');
        $this->load->model('policy_model');
        $this->load->model('book/prebook_model');
        $this->load->model('book/postbook_model');
        $this->load->library('httpcurl');

        $arrCompleteServices = array();
        $err = array();
        foreach ($a_post as $idx => &$post)
        {
            try
            {
                //search available product.
                $this->searchProduct($post);
                $this->temp_log['request']['search'][$idx] = $post['request'];
                $this->temp_log['response']['search'][$idx] = $post['response'];
                
                //generate booking request and send booking.
                $this->prebooking($post);
                $this->temp_log['request']['prebook'][$idx] = $post['request'];
                $this->temp_log['response']['prebook'][$idx] = $post['response'];

                //post booking after get response.
                $arrCompleteServices[$idx] = $this->postbooking($post);
                $this->temp_log['request']['postbook'][$idx] = $post['request'];
                $this->temp_log['response']['postbook'][$idx] = $post['response'];
                $is_error = 'FALSE';
            } catch (Exception $ex)
            {
                $is_error = 'TRUE';
                log_message('NOTICE', 'Exception : ' . $ex->getMessage() . '; LINE : ' . $ex->getLine() . '; FILE : ' . $ex->getFile());
                $err['response']['errors'][$idx] = $ex->getMessage();

                //auto cancel booked item when system error report
                if (count($this->postbook_model->getCache_bookingid()) > 0)
                {
                    $this->autocancel($post);
                    $this->temp_log['request']['auto_cancel'][$idx] = $post['request'];
                    $this->temp_log['response']['auto_cancel'][$idx] = $post['response'];
                }
            }
        }

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
        @$this->load->view('book_response', array('post' => $a_post, 'arrCompleteServices' => $arrCompleteServices, 'err' => $err));
        xmllog21s('BookingHotel', $this->temp_log);
    }

    /**
     * Search product available.
     * @param array $post
     */
    private function searchProduct(&$post)
    {
        $this->search_model->fetchHotelList($post);
        $this->search_model->groupRoom($post);
        $this->search_model->request($post);

        //$this->chkallotment_model->request($post);
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

    /**
     * get request product from search and create request booking.
     * @param array $post
     */
    private function prebooking(&$post)
    {
        //check available allotment.
        //$roomQuery = $this->prebook_model->getQueryRoom($post);
        /*if ($this->chkallotment_model->isAllotment($post, $roomQuery) == FALSE)
        {
            throw new Exception('Room allotment unavailable.');
        }
        unset($roomQuery);*/

        //create booking request.
        $this->prebook_model->getRequest($post);
        $post['response'] = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlBookService.svc/book_v2')
                ->setSSL(true)
                ->send($post['request']);

        if ($this->input->get_post('Debug') == 1)
        {
            echo "\n========= [" . __METHOD__ . "] ==========\n";
            print_r($post);
        }
    }

    /**
     * after booking process and translate to xml booking in gateway 21 format.
     * @param array $post
     * @return type
     */
    private function postbooking(&$post)
    {
        $this->postbook_model->getBookingDetails($post);
        $post['response'] = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlBookService.svc/bookdetail_v2')
                ->setSSL(true)
                ->send($post['request']);

        if ($this->input->get_post('Debug') == 1)
        {
            echo "\n========= [" . __METHOD__ . "] ==========\n";
            print_r($post);
        }
        return $this->postbook_model->getResponse($post);
    }

    /**
     * when system detected error booking or emergency case. cancell all transection.(rollback)
     * @param type $post
     */
    private function autocancel(&$post)
    {
        $this->load->model('cancel_model');
        foreach ($this->postbook_model->getCache_bookingid() as $hbid)
        {
            try
            {
                //get booking split room type;
                $post['bookRoomtypeId'] = $hbid['id'];

                $this->cancel_model->getCancel($post);
                //Send xml request to supplier.
                $post['response'] = $this->httpcurl
                        ->setHeader('Content-Type', 'text/xml')
                        ->setUri('XmlBookService.svc/Cancel_Service')
                        ->setSSL(true)
                        ->send($post['request']);

                $this->cancel_model->getConfirm($post);
                //Send xml request to supplier.
                $post['response'] = $this->httpcurl
                        ->setHeader('Content-Type', 'text/xml')
                        ->setUri('XmlBookService.svc/ConfirmCancel_Service')
                        ->setSSL(true)
                        ->send($post['request']);
            } catch (Exception $ex)
            {
                log_message('NOTICE', 'Exception : ' . $ex->getMessage() . '; LINE : ' . $ex->getLine() . '; FILE : ' . $ex->getFile());
            }
        }
    }

}
