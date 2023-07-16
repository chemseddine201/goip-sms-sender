<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use App\Models\Line;
use App\Models\SMS;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Pikart\Goip\Sms\SocketSms;
use Throwable;

class SmsSender extends Controller
{
    protected $host;
    protected $username;
    protected $password;
    protected $messagesLimit;

    public function __construct()
    {
        $this->messagesLimit = config('services.goip.messagesLimit');
    }

    public function processByMessages() :void
    {
        try {
            $this->resetLines();
            while (true) {
                //
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
                //
                try {
                    $jobs = [];
                    //max 5 lines because goip supports only 5 sessions same time
                    $lines = $this->getFreeLines();
                    // check if there is non sent messages
                    if ($lines->isEmpty()) {
                        echo "No Free Lines\n";
                        sleep(10); // Wait for 10 seconds before checking for new messages
                        continue;
                    }
                    $sid = $this->getSessionUid();
                    foreach($lines as $line) {
                        $port = $this->getUdpPort($line->id);
                        $sms = new SocketSms(
                            "192.168.1.110", //"192.168.1.110"
                            $port,//the line port
                            $sid,
                            "admin", 
                            ['timeout' => 5]
                        );
                        //get the free line
                        $messages = $this->getMessages($this->messagesLimit);//max line messages should be 10.
                        if ($messages->isEmpty()) {//wait then move to next free line
                            echo "No Messages to Send Via Line {$line->id}\n";
                            sleep(5);
                            continue;
                        }
                        //set selected line busy
                        $this->setLineBusy($line->id, true);
                        //Set sms line selected
                        $ids = $messages->pluck('id')->toArray();
                        $this->setMessagesProcessing($ids, $line->id);
                        /* 
                            $jobs[] = new SendSmsJob([
                                'messages' => $messages->toArray(),
                                'line' => $line->toArray(),
                            ]); 
                        */
                        $status = false;
                        $sid = $this->getSessionUid();
                        $port = $this->getUdpPort($line->id);
                        
                        foreach($messages->toArray() as $message) {
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
                        $this->freeLine($line->id);
                    }
                    
                    //
                   // Bus::batch($jobs)->onQueue('sms_queue')->dispatch();
    
                } catch (Throwable $th) {
                    throw $th;
                }
            }
        } catch(Exception $e) {
            echo $e;
        }
    } 
    /* public function processByMessages() :void
    {
        try {
            $this->resetLines();
            while (true) {
                //
                try {
                    // Select non sent messages from sms table
                    $messages = $this->getMessages($this->messagesLimit);//8
                    // check if there is non sent messages
                    if ($messages->isEmpty()) {
                        echo "No messages to Send\n";
                        sleep(10); // Wait for 10 seconds before checking for new messages
                        continue;
                    }

                    $jobs = $messages->map(function ($message) {
                        $line = $this->getLine($message->operator_id);
                        if (is_null($line)) {
                            return null;
                        }
                        
                        //set selected line busy
                        $this->setLineBusy($line->id, true);
                        //Set sms line selected
                        $this->setProcessing($message->id, $line->id);
                        
                        return new SendSmsJob([
                            'message' => $message->toArray(),
                            'line' => $line->toArray(),
                        ]);
                    })->filter()->groupBy('operator.name')->toArray();
                    
                    foreach ($jobs as $operator => $operatorJobs) {
                        Bus::batch($operatorJobs)->onQueue($operator)->dispatch();
                    }
                } catch (Throwable $th) {
                    throw $th;
                }
            }
        } catch(Exception $e) {
            echo $e;
        }
    } */

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
        return strval(mt_rand(10000000, 99999999));
    }

}
