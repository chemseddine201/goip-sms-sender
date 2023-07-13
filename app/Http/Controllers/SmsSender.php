<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use App\Models\Line;
use App\Models\SMS;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class SmsSender extends Controller
{
    protected $host;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->host = config('services.goip.host');
        $this->username = config('services.goip.username');
        $this->password = config('services.goip.password');
    }

    public function processByMessages() :void
    {
        try {
            $this->resetLines();
            //TODO: get multiple free lines then send messages
            while (true) {
                //
                try {
                    $line = null;
                    $jobs = [];
                    $processed = [];
                    // Select non sent messages from sms table
                    $messages = $this->getMessages(5);
                    // check if there is non sent messages
                    if ($messages->isEmpty()) {
                        echo "No messages to Send\n";
                        sleep(10); // Wait for 5 seconds before checking for new messages
                        continue;
                    }
        
                    foreach($messages as $message) {
                        //get the free line
                        $line = $this->getLine($message->operator_id);
                        if (is_null($line)) {
                            sleep(3);
                            continue;
                        }
                        
                        //set selected line busy
                        $this->setLineBusy($line->id, true);
                        //Set sms line selected
                        $this->setProcessing($message->id, $line->id);
                        //assing processed
                        $processed[] = [
                            'message_id' => $message->id,
                            'line_id' => $line->id
                        ];
                        //assing job
                        $jobs[] = new SendSmsJob([
                            'message' => $message->toArray(),
                            'line' => $line->toArray(),
                        ]);
                    }
                    //
                    Bus::batch($jobs)->onQueue('sms_queue')->then(function (Batch $batch) use ($processed) {
                        
                    })->catch(function (Batch $batch, Throwable $e) use ($processed) {
                        echo $e;
                        //$this->updateProcessed($processed);
                    })->finally(function (Batch $batch) use ($processed) {
                    })->dispatch();
                } catch (\Throwable $th) {
                    throw $th;
                }
            }
        } catch(Exception $e) {
            echo $e;
        }
    }

    /* public function processByLines() :void
    {
        //TODO: get multiple free lines then send messages
        while (true) {
            //
            $line = null;
            $jobs = [];
            $processed = [];
            // Select non sent messages from sms table
            $lines = $this->getFreeLines();
            // check if there is lines
            if ($lines->isEmpty()) {
                sleep(10); // Wait for 5 seconds before checking for new messages
                continue;
            }

            foreach($lines as $line) {
                //get the free line
                $message = $this->getMessage($line->operator_id);
                if (is_null($message)) {
                    sleep(10);
                    continue;
                }
                //
                //set line busy
                $this->setLineBusy($line->id, true);
                //Set sms line selected
                $this->setProcessing($message->id, $line->id);
                //assing processed
                $processed[] = [
                    'message_id' => $message->id,
                    'line_id' => $line->id
                ];
                //assing job
                $jobs[] = new SendSmsJob([
                    'message' => $message->toArray(),
                    'line' => $line->toArray(),
                ]);
            }
            //
            Bus::batch($jobs)->onQueue('sms_queue')->then(function (Batch $batch) use ($processed) {
                $this->updateProcessed($processed, true);
            })->catch(function (Batch $batch, Throwable $e) use ($processed) {
                $this->updateProcessed($processed);
            })->finally(function (Batch $batch) use ($processed) {
            })->dispatch();
        }
    } */


    private function getMessages (int $limit = 5)
    {
        return SMS::where('sent_status', 0)->where('line', 0)->where('processing', 0)->limit($limit)->get();
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
        return Line::where('busy', 0)->where('status', 1)->get();
    }
    
    private function setProcessing (int $id, int $line)
    {
        SMS::where('id', $id)->update(['line' => $line, 'processing' => 1]);
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

}
