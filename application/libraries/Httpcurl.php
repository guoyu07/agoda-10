<?php

namespace libraries;

/**
 * Description of Transmission
 *
 * @author Kawin Glomjai <g.kawin@live.com>
 */
use \Exception;

class Httpcurl
{

    protected $Header = array();
    protected $Uri = null;
    protected $Opt = array();
    protected $Address = '';
    protected $SSL = false;

    public function __construct()
    {
        $CI = &getInstance();
        $CI->load->helper('http');
        //auto detect uri request.
        $this->Address = config_item(_AGODA_STAGE . '_url');
        $this->SSL = false;
    }

    public function setHeader($key, $val)
    {
        if (!isset($this->Opt[CURLOPT_HTTPHEADER]))
        {
            $this->Opt[CURLOPT_HTTPHEADER] = array();
        }
        array_push($this->Opt[CURLOPT_HTTPHEADER], $key . ':' . $val . ';');
        return $this;
    }

    public function setUri($uri)
    {
        $this->Uri = $uri;
        return $this;
    }

    public function send($request = array(), &$ref = NULL)
    {
        if (empty($request))
        {
            throw new Exception('Check your request.');
        }

        if (!is_array($request))
        {
            $request = array($request);
        }

        if ($this->SSL === true)
        {
            $a_url = parse_url($this->Address);
            if ($a_url['scheme'] == 'http')
            {
                $this->Address = str_replace('http', 'https', $this->Address);
            }

            $this->Opt[CURLOPT_SSL_VERIFYHOST] = 2;
            $this->Opt[CURLOPT_SSL_VERIFYPEER] = true;
            //CURL_SSLVERSION_TLSv1_2 
            $this->Opt[CURLOPT_SSLVERSION] = 6;
        }

        if ($this->SSL === false)
        {
            $a_url = parse_url($this->Address);
            if ($a_url['scheme'] == 'https')
            {
                $this->Address = str_replace('https', 'http', $this->Address);
            }

            if (isset($this->Opt[CURLOPT_SSL_VERIFYHOST]))
            {
                unset($this->Opt[CURLOPT_SSL_VERIFYHOST]);
            }

            if (isset($this->Opt[CURLOPT_SSL_VERIFYPEER]))
            {
                unset($this->Opt[CURLOPT_SSL_VERIFYPEER]);
            }
            if (isset($this->Opt[CURLOPT_SSLVERSION]))
            {
                unset($this->Opt[CURLOPT_SSLVERSION]);
            }
        }

        $response = httpcurl($request, $this->Address . $this->Uri, $this->Opt);

        if ($ref != NULL)
        {
            $ref = $response;
            return;
        }
        return $response;
    }

    public function setSSL($isSSL = false)
    {
        if (!is_bool($isSSL))
        {
            throw new Exception('Boolean type was acceptable.');
        }

        $this->SSL = $isSSL;
        return $this;
    }

}
