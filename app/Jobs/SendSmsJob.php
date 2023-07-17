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
use Pikart\Goip\Sms\SocketSms;

//use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, Batchable, SerializesModels;
    //protected $data;
    private $messages;
    private $ip;
    private $password;
    private $line_id;
    
    public function __construct($messages = [])
    {

        $this->messages = $messages;

    }

    public function handle()
    {
        try {
            //code...
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    
    private function updateData(int $line_id, int $message_id, bool $status = false)
    {
        Line::where('id', $line_id)->update(['busy' => false]);
        SMS::where('id', $message_id)->update(['sent_status' => $status]);
    }

    private function freeLine (int $line_id) {
        Line::where('id', $line_id)->update(['busy' => false]);
    }

    private function updateSentStatus (int $message_id, bool $status) {
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


    private function getUdpPort(int $line_id) : int
    {
        return 10990+$line_id;
    }

    private function getSessionUid() : string
    {
        return strval(mt_rand(10000000, 99999999));
    }
}
