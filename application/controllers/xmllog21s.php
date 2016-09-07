<?php

namespace controllers;

/**
 * Description of xmllog21
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use core\Controller;

class xmllog21s extends Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('xmllog21s/xmllog21_model', 'xmllog');
    }

    public function index() {
        $this->load->view('xmllog21/xmllog21s_view');
    }

    public function getlist() {

        $data['dataSrc'] = $this->xmllog->fetchLogList($this->input->post());
        $this->output->setContentType('html');
        $this->load->view('xmllog21/xmllog21s_table_list_view', $data);
    }

    public function getDetails() {
        $data['row'] = $this->xmllog->fetchLogDetails($this->input->post());
        $this->load->view('xmllog21/xmllog21s_log_details_view', $data);
    }

}
