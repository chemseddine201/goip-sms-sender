<?php 
namespace App\Http\Controllers;

use App\Services\SendMultipleMessages;
use Illuminate\Database\Eloquent\Collection;
use Amp\Parallel\Worker\Task;

final class SendMessagesTask implements Task
{
    private $messages;
    private $line_id;
    private $sid;
    private $port;

    public function __construct(Collection $messages, string $sid, int $line_id)
    {
        $this->port = $this->getUdpPort($line_id);
        $this->messages = $messages;
        $this->line_id = $line_id;
        $this->sid = $sid;

    }

    public function run($channel, $cancellation) : array
    {
        echo "Running on line {$this->line_id}\n";
        $socket = new SendMultipleMessages(
            "192.168.1.110",
            $this->port,
            $this->sid,
            "admin", 
            ['timeout' => 10]
        );
       return $socket->sendMultiple($this->messages, $this->line_id);
    }

    private function getUdpPort(int $line_id) : int
    {
        return 10990+$line_id;
    }
}