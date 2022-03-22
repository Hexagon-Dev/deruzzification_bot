<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;
use WeStacks\TeleBot\Interfaces\TelegramObject;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\BotCommand;

class SetBotCommands extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bot:commands:set';

    /**
     * @var string
     */
    protected $description = 'Command description';

    public TeleBot $bot;

    /**
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->bot = new TeleBot();

        $commands = [];

        foreach (config('bot_commands') as $command) {
            $commands[] = $this->createBotCommand($command);
        }

        dump($commands);

        if ($this->bot::setMyCommands($commands)) {
            $this->info('Commands were successfully set!');
            return;
        }

        $this->error('Something went wrong!');
    }

    /**
     * @param array $command
     * @return TelegramObject
     * @throws Throwable
     */
    public function createBotCommand(array $command): TelegramObject
    {
        return BotCommand::create([
            'command' => $command['command'],
            'description' => $command['description'],
        ]);
    }
}
