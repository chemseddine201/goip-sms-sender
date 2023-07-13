<?php

namespace App\Services;

use DOMDocument;
use Exception;
use GuzzleHttp\Client;

class GoipSmsService
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->host = config('services.goip.host');
        $this->port = config('services.goip.port');
        $this->username = config('services.goip.username');
        $this->password = config('services.goip.password');
    }

    private function getSmsKey() {
        $smskey = null;
        $client = new Client([
            'auth' => [
                $this->username, 
                $this->password
            ],
            'headers' => [
                'Authorization' => base64_encode("$this->username:$this->password"),
            ]
        ]);
        $res = $client->get($this->getKeyUrl());
        if($res->getStatusCode() === 200) {
            $body = $res->getBody()->getContents();
            $dom = new DOMDocument();
            @$dom->loadHTML($body);
            
            $inputElements = $dom->getElementsByTagName('input');
            foreach ($inputElements as $element) {
                if ($element->getAttribute('name') === 'smskey') {
                    $smskey = $element->getAttribute('value');
                    break;
                }
            }
        }
        echo "smskey founded => $smskey".PHP_EOL;
        return $smskey;
    }


    function getSendStatus ($res, $line) {
        $elm = null;
        $body = $res->getBody()->getContents();
        $dom = new DOMDocument();
        @$dom->loadHTML($body);
        $tds = $dom->getElementsByTagName('id');
        foreach ($tds as $element) {
            if ($element->getAttribute('id') === "send$line") {
                $elm = $element;
                break;
            }
        }
        return $elm;
    }
    public function sendSms($data)
    {
        try {
            echo "Sending".PHP_EOL;
            $line = $data['line']['id'];
            $client = new Client();
            $res = $client->request('POST', $this->getSendUrl(), [
                'auth' => [
                    $this->username, 
                    $this->password
                ],
                'headers' => [
                    'Authorization' => base64_encode("$this->username:$this->password"),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'smskey' => $data['message']['message_id'],
                    'action' => 'SMS',
                    'telnum' => $data['message']['phone'],
                    'smscontent' => $data['message']['message'],
                     "line$line" => 1,
                    'send' => 'Send'
                ]
            ]);
            if ($res->getStatusCode() === 200) {
                $body = $res->getBody()->getContents();
                //echo $this->getSendStatus($body, $line).PHP_EOL;
                //
                if (strpos($body, "Line $line Sending...") != -1) {
                    echo "Sent Success".PHP_EOL;
                    return true;
                }
            }
        } catch (Exception $e) {
            echo "Sending Error\n\n";
            echo $e->getMessage();
        } 
        return false;
    }


    protected function getKeyUrl()
    {
        return $this->port > 0 ? "http://{$this->host}:{$this->port}/default/en_US/tools.html?type=sms" : "http://{$this->host}/default/en_US/tools.html?type=sms";
    }
    
    protected function getSendUrl()
    {
        return $this->port > 0 ? "http://{$this->host}:{$this->port}/default/en_US/sms_info.html?type=sms" : "http://{$this->host}/default/en_US/sms_info.html?type=sms";
    }

}