<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use WeStacks\TeleBot\Laravel\TeleBot;

class GetBotCommands extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bot:commands:get';

    /**
     * @var string
     */
    protected $description = 'Command description';

    public TeleBot $bot;


    public function handle(): void
    {
        $this->bot = new TeleBot();

        dump($this->bot::getLocalCommands());

        $this->info('Ready');
    }
}
