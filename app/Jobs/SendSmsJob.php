<?php

namespace App\Jobs;

use App\Models\SMS;
use App\Models\Line;
use Pikart\Goip\Sms\HttpSms;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Services\GoipSmsService;
use Exception;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


//use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, Batchable, SerializesModels;
    public $tries = 3;
    public $maxExceptions = 3;

    protected $data;
    private $url;
    private $line_id;
    private $message_id;
    private $message;
    private $phone;

    public function __construct($data)
    {
        $this->url = $this->getGoipUrl();
        $this->data = $data;
        $this->line_id = $this->data['line']['id'];
        $this->message_id = $this->data['message']['id'];
        $this->message = preg_replace('/\s+/', ' ', trim($this->data['message']['message']));
        $this->phone = $this->data['message']['phone'];
    }

    public function handle(GoipSmsService $smsService)
    {
        try {
            $status = false;
            $sms = new HttpSms($this->url , $this->line_id, 'admin', 'admin');
            $sms->setStatusCheckTries(15);
            $sms->setGuzzleTimeout(10);
            $response = $sms->send($this->phone, $this->message);
            //
            echo "Raw => {$response['raw']}\n";
            echo "Status => {$response['status']}\n";
            //
            if ($response["status"] === "send") {
                $status = true;
                echo "The message '$this->message' sent to $this->phone\n\n";
            } else {
                echo "The message '$this->message' did not sent to $this->phone\n\n";
            }
            //
            $this->updateData($this->line_id, $this->message_id, $status);
        } catch(Exception $e) {
            echo $e->getMessage();
            $this->updateData($this->line_id, $this->message_id, false);
        }
    }
    
    private function updateData(int $line_id, int $message_id, bool $status = false)
    {
        Line::where('id', $line_id)->update(['busy' => false]);
        SMS::where('id', $message_id)->update(['sent_status' => $status]);
    }

    private function getGoipUrl() {
        $host = config('services.goip.host');
        $port = config('services.goip.port');
        $query = null;//array
        //
        $url = "http://$host";
        //append port
        if ($port) {
            $url .= ":$port";
        }
        //append query
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
    
        return $url;
    }
}
