<?php

namespace controllers;

/**
 * Description of CancelRSVN
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Controller;
use \Exception;

class CancelRSVN extends Controller
{

    var $temp_log = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        save_log_data();
    }

    public function index()
    {

        $post = XMLPost2Array($this->input->post());
        //array key for cancel reservation.
        if (isset($post['DocID']))
        {
            $post['HBooking'] = $post['DocID'];
            unset($post['DocID']);
        }

        $this->load->model('cancel_model');
        $this->load->library('httpcurl');

        $bookidlist = array_map('base64_decode', explode('#|#', $post['HBooking']));
        foreach ($bookidlist as $idx => $bookid)
        {
            //get booking split room type;
            list($post['bookRoomtypeId'], $hbpost) = explode(';', str_replace(':{', ';{', $bookid));
            $post = array_merge($post, isJson($hbpost, true));

            try
            {
                $this->requestCancel($post);
                $this->temp_log['request']['requestCancel'][$idx] = $post['request'];
                $this->temp_log['response']['requestCancel'][$idx] = $post['response'];

                $this->confirmCancel($post);
                $this->temp_log['request']['confirmCancel'][$idx] = $post['request'];
                $this->temp_log['response']['confirmCancel'][$idx] = $post['response'];
            } catch (Exception $ex)
            {
                log_message('INFO', 'Exception : ' . $ex->getMessage() . '; LINE : ' . $ex->getLine() . '; FILE : ' . $ex->getFile());
                $post['response']['error'][] = $ex->getMessage();
            }
        }

        //final result
        $is_result = 'true';
        $errmsg = '';
        if (isset($post['response']['error']))
        {
            $is_result = 'false';
            $errmsg = implode('; ', $post['response']['error']);
        }

        $this->load->view('cancel_response', array(
            "resno" => $post['ResNo'],
            "hbid" => $post['HBooking'],
            "errmsg" => $errmsg,
            "is_result" => $is_result
        ));

        xmllog21s('CancelRSVN', $this->temp_log);
    }

    private function requestCancel(&$post)
    {
        $post['request'] = array();
        $this->cancel_model->getCancel($post);
        //Send xml request to supplier.
        $post['response'] = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlBookService.svc/Cancel_Service')
                ->setSSL(true)
                ->send($post['request']);

        if ($this->input->get_post('Debug') == 1)
        {
            echo "\n========= [" . __METHOD__ . "] ==========\n";
            print_r($post);
        }
    }

    private function confirmCancel(&$post)
    {

        $this->cancel_model->getConfirm($post);

        //Send xml request to supplier.
        $post['response'] = $this->httpcurl
                ->setHeader('Content-Type', 'text/xml')
                ->setUri('XmlBookService.svc/ConfirmCancel_Service')
                ->setSSL(true)
                ->send($post['request']);

        if ($this->input->get_post('Debug') == 1)
        {
            echo "\n========= [" . __METHOD__ . "] ==========\n";
            print_r($post);
        }
    }

}
