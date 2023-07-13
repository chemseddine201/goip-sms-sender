<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SmsSender;

class SendMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send messages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $controller = new SmsSender(); // make sure to import the controller
        $controller->processByMessages();
    }
}
