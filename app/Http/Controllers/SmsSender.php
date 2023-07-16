<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use App\Models\Line;
use App\Models\SMS;
use App\Services\SendMultipleMessages;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Pikart\Goip\Sms\SocketSms;
use Throwable;

class SmsSender extends Controller
{
    protected $sessions;
    protected $ip;
    protected $password;
    protected $messagesLimit;

    public function __construct()
    {
        $this->sessions = [];
        $this->messagesLimit = config('services.goip.messagesLimit');
        $this->ip = (string) config('services.goip.udp_ip');
        $this->password = (string) config('services.goip.password');
    }

    public function processByMessages() :void
    {
        try {
            $this->resetLines();
            while (true) {
                try {
                    $line = null;
                    $jobs = [];
                    // Select non sent messages from sms table
                    $messages = $this->getMessages($this->messagesLimit);//8
                    // check if there is non sent messages
                    if ($messages->isEmpty()) {
                        echo "No messages to Send\n";
                        sleep(10); // Wait for 10 seconds before checking for new messages
                        continue;
                    }

                    foreach($messages as $message) {
                        //get the free line
                        $line = $this->getLine($message->operator_id);
                        if (is_null($line)) {
                            sleep(1);
                            continue;
                        }

                        //set selected line busy
                        $this->setLineBusy($line->id, true);
                        //Set sms line selected
                        $this->setProcessing($message->id, $line->id);

                        $jobs[] = new SendSmsJob([
                            'message' => $message->toArray(),
                            'line' => $line->toArray(),
                        ]);
                    }
                    
                    //
                    Bus::batch($jobs)->onQueue('sms_queue')->dispatch();
    
                } catch (Throwable $th) {
                    throw $th;
                }
            }
        } catch(Exception $e) {
            echo $e;
        }
    } 

    public function processByLines() :void
    {
        try {
            $this->resetLines();
            while (true) {
                try {
                    //max 5 lines because goip supports only 5 sessions same time
                    $lines = $this->getFreeLines();
                    // check if there is non sent messages
                    if ($lines->isEmpty()) {
                        echo "No Free Lines\n";
                        sleep(10); // Wait for 10 seconds before checking for new messages
                        continue;
                    }
                    //all lines has the same session id
                    foreach ($lines as $line) {
                        $sid = $this->getSessionUid();
                        $port = $this->getUdpPort($line->id);
                        $messages = $this->getMessages($this->messagesLimit);//max line messages should be 10.
                        //get the free line
                        if ($messages->isEmpty()) {//wait then move to next free line
                            echo "No Messages to Send Via Line {$line->id}\n";
                            sleep(5);
                            continue;
                        }
                        $sms = new SendMultipleMessages(
                            $this->ip,
                            $port,
                            $sid,
                            $this->password, 
                            ['timeout' => 10]
                        );
                        //set selected line busy
                        $this->setLineBusy($line->id, true);
                        //Set sms line selected
                        $ids = $messages->pluck('id')->toArray();
                        $this->setMessagesProcessing($ids, $line->id);

                        $response = $sms->sendMultiple($messages);
                        //free line when done
                        $this->updateMessagesSentStatus($response);
                        $this->freeLine($line->id);
                    }
                    //remove the session id
                } catch (Throwable $th) {
                    echo $th->getMessage();
                }
            }
        } catch(Exception $e) {
            echo $e;
        }
    } 

    private function getMessages (int $limit = 5)
    {
        return SMS::with('operator')->where('sent_status', 0)->where('line', 0)->where('processing', 0)->limit($limit)->get();
    }
    
    private function getMessage (int $id)
    {
        return SMS::where('operator_id', $id)->where('sent_status', 0)->where('processing', 0)->first();
    }

    private function getLine (int $operator_id)
    {
        return Line::where('operator_id', $operator_id)->where('busy', 0)->where('status', 1)->first();
    }

    private function getFreeLines ()
    {
        return Line::where('busy', 0)->where('status', 1)->limit(5)->get();
    }
    
    private function setProcessing (int $id, int $line)
    {
        SMS::where('id', $id)->update(['line' => $line, 'processing' => 1]);
    }

    private function setMessagesProcessing (array $ids, int $line_id)
    {
        SMS::whereIn('id', $ids)->update(['line' => $line_id, 'processing' => 1]);
    }


    protected function updateSmsStatus($id, $status)
    {
        SMS::where('id', $id)->update(['sent_status' => $status]);
    }

    private function setLineBusy($id, $busy = false)
    {
         Line::where('id', $id)->update(['busy' => $busy]);
    }
    
    private function setAllSentSms($ids)
    {
        SMS::whereIn('id', $ids)->update(['sent_status' => 1]);
    }

//
    //http://192.168.8.1/default/en_US/send.html?u=admin&p=admin&m=0656181996&c=send+sms+from+laravel+app&l=2
   // http://192.168.8.1/default/en_US/send.html?u=admin&p=admin&m=0656181996&c=send+sms+from+laravel+app&l=2
    private function updateProcessed($records, bool $sent = false) : void {
        $sms_ids = [];
        foreach ($records as $record) {
            //
            $this->setLineBusy($record['line_id']);
            //assing id to ids array
            $sms_ids[] = $record['message_id'];
        }
        if ($sent) {
            $this->setAllSentSms($sms_ids);
        }
    }

    private function resetLines() {
        DB::table('lines')->update(['busy' => 0, 'jobs' => 0]);
    }

    private function updateSentStatus (int $message_id, bool $status) {
        SMS::where('id', $message_id)->update(['sent_status' => $status]);
    }
    private function updateMessagesSentStatus (array $response) {
        $success_ids = $response['success'];
        $fail_ids = $response['fails'];
        SMS::whereIn('id', $success_ids)->update(['sent_status' => 1]);
        SMS::whereIn('id', $fail_ids)->update(['sent_status' => 0]);
    }

    private function freeLongBusy() {
        Line::where('busy', 1)
        ->where('status', 1)
        ->where('updated_at', '<=', Carbon::now()->subMinutes(5)->toDateTimeString())
        ->update(['busy' => 0]);
        
        return response()->json(['status' => 'success'], 200);
    }

    private function getUdpPort(int $line_id) : int
    {
        return 10990+$line_id;
    }

    private function getSessionUid() : string
    {
        return strval(mt_rand(100000, 99999999));
    }

    private function freeLine (int $line_id) {
        Line::where('id', $line_id)->update(['busy' => false]);
    }


    function sessionExists($array, $key_to_check) {
        foreach ($array as $child_array) {
            if (array_key_exists($key_to_check, $child_array)) {
                return true;
            }
        }
        return false;
    }
}
