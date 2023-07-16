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
    
    public function __construct($data)
    {

        $this->messages = $data['messages'];
        $this->line_id = $data['line']['id'];
        $this->ip = (string) config('services.goip.udp_ip');
        $this->password = (string) config('services.goip.password');
    }

    public function handle()
    {
        try {
            $status = false;
            $sid = $this->getSessionUid();
            $port = $this->getUdpPort($this->line_id);
            $sms = new SocketSms(
                $this->ip, //"192.168.1.110"
                $port,//the line port
                $sid,
                $this->password, 
                ['timeout' => 5]
            );
            foreach($this->messages as $message) {
                try {
                    $phone = $message['phone'];
                    $msg = $message['message'];
                    $message_id = $message['id'];
                    $response = $sms->send($phone, $msg);
                    $raw = $response['raw'];
                    echo "Raw => {$raw}";
                    if (strpos(trim($raw), "OK") !== -1) {//check if success
                        $status = true;
                        echo "The message '$msg' sent to $phone\n\n";
                    } else {
                        echo "The message '$msg' did not sent to $phone\n\n";
                    }
                    //update sent status
                    $this->updateSentStatus($message_id, $status);
                } catch(Exception $e) {
                    echo $e->getMessage();
                }
            }
            //echo "All Selected messages are sent.\n\n";
            //free line when done
            $this->freeLine($this->line_id);
        } catch(Exception $e) {
            echo $e;
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
