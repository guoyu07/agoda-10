<?php

namespace controllers;

use core\Controller;

class welcome extends Controller
{

    public $url_logo = 'public/images/logo/Logo.jpg';
    public $size_x = '214';
    public $size_y = '51';

    public function index()
    {
        $display['mnu_header']['SearchHotels'] = array(
            'href' => '#SearchHotels',
            'id' => 'SearchHotels',
            'position' => 'left',
            'order' => 1,
            'title' => 'get hotel products'
        );
        $display['mnu_header']['ViewCancelPolicy'] = array(
            'href' => '#ViewCancelPolicy',
            'id' => 'ViewCancelPolicy',
            'position' => 'left',
            'order' => 2,
            'title' => 'view cancel policies'
        );
        $display['mnu_header']['BookHotel'] = array(
            'href' => '#BookHotel',
            'id' => 'BookHotel',
            'position' => 'left',
            'order' => 3,
            'title' => 'booking hotel'
        );
        $display['mnu_header']['GetCancelPolicy'] = array(
            'href' => '#GetCancelPolicy',
            'id' => 'GetCancelPolicy',
            'position' => 'left',
            'order' => 4,
            'title' => 'get policy'
        );
        $display['mnu_header']['CancelRSVN'] = array(
            'href' => '#CancelRSVN',
            'id' => 'CancelRSVN',
            'position' => 'left',
            'order' => 5,
            'title' => 'Cancel reservation.'
        );
        /* $display['mnu_header']['Amend'] = array(
          'href' => '#Amend',
          'id' => 'Amend',
          'position' => 'left',
          'order' => 5,
          'title' => 'Modification reservation.'
          ); */

        $display['mnu_header']['Log post details'] = array(
            'href' => '#' . _SUPPLIERCODE . 'Logdetail',
            'id' => 'Logdetail',
            'position' => 'right',
            'order' => 1,
            'title' => _SUPPLIERCODE . 'Logdetail'
        );

        $display['mnu_header']['xmllog21s'] = array(
            'href' => '#' . _SUPPLIERCODE . 'xmllog21s',
            'id' => 'xmllog21s',
            'position' => 'right',
            'order' => 2,
            'title' => 'View Log records.'
        );

        $display['logo_url'] = 'getlogo?w=200&h=50';
        $display['supplier_url'] = 'http://www.agoda.com';

        /**
         * ribbon
         */
        $display['ribbon']['css'] = 'corner-ribbon top-right sticky ';
        if (ENVIRONMENT == 'development')
        {
            $display['ribbon']['css'] .= 'red shadow';
        }

        if (ENVIRONMENT == 'testing')
        {
            $display['ribbon']['css'] .= 'orange shadow';
        }

        if (ENVIRONMENT == 'production')
        {
            $display['ribbon']['css'] .= 'green shadow';
        }

        $display['ribbon']['envText'] = ENVIRONMENT;
        $this->load->view('welcome', $display);
    }

    public function getLogo()
    {

        $f_info = pathinfo($this->url_logo);

        $get_w = $this->input->get('w');
        $get_h = $this->input->get('h');
        // Get new sizes
        list($width, $height) = getimagesize($this->url_logo);
        $newwidth = $get_w === FALSE ? $this->size_x : $get_w;
        $newheight = $get_h === FALSE ? $this->size_y : $get_h;


        $thumb = imagecreatetruecolor($newwidth, $newheight);
        //compress picture size
        if (strtolower($f_info['extension']) == 'jpg')
        {
            $image = imagecreatefromjpeg($this->url_logo);
            $call = 'imagejpeg';
            $content = 'image/jpeg';
        }

        if (strtolower($f_info['extension']) == 'png')
        {
            $image = imagecreatefrompng($this->url_logo);
            $call = 'imagepng';
            $content = 'image/jpeg';
        }

        if (strtolower($f_info['extension']) == 'gif')
        {
            $image = imagecreatefromgif($this->url_logo);
            $call = 'imagegif';
            $content = 'image/gif';
        }
        @header('Content-Type : ' . $content);
        imagecopyresized($thumb, $image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        call_user_func($call, $thumb);
        imagedestroy($thumb);
    }

}
