<?php 

namespace App\Services;

use Exception;
use Pikart\Goip\Sms\SocketSms;

class SendMultipleMessages extends SocketSms {

    public function __construct(
        string $host,
        int $port,
        string $uniqueSessionIdentifier,
        string $password,
        ?array $options = null
    ) {
        parent::__construct($host, $port, $uniqueSessionIdentifier, $password, $options);
    }

    public function sendMultiple ($messages, $line_id) : array {
        $success = [];
        $fails = [];
        //
        foreach ($messages as $message) {
            $phone = $message->phone;
            $id = $message->id;
            
            $content = str_replace(["\n", "\t", "\r"], " ", $message->message);

            try {
                echo "Message => $content | Phone => $phone\n";

                $this->sendBulkSmsRequest($content);
                $this->waitForResponse('sendBulkSmsRequest', 'PASSWORD');
                $this->sendAuthRequest();
                $this->waitForResponse('sendAuthRequest', 'SEND');
                $this->sendNumberRequest($phone);
                $response = $this->waitForResponse('sendNumberRequest', 'OK');
                $this->sendEndRequest();
                //$this->waitForResponse('sendEndRequest', 'DONE');
                $resp = str_replace(array("\r", "\n"), '', $response);

                if(strpos($resp, "OK") !== -1) {
                    $success [] = $id;
                } else {
                    $fails[] = $id;
                }
                echo "Response => $resp\n";
                usleep(5000);
            } catch (Exception $e) {
                echo $e->getMessage();
                $fails[] = $id;
            }
        }
        return [
          "line_id" => $line_id,
           "success" => $success,
           "fails" => $fails
        ];
    }
}