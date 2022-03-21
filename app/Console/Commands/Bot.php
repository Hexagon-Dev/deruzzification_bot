<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\Update;

class Bot extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bot';

    /**
     * @var string
     */
    protected $description = 'Executes bot.';

    public int $lastID;
    public int $chatID;
    public TeleBot $bot;

    public function __construct()
    {
        parent::__construct();

        $this->lastID = $this->getLastID();
    }

    public function handle()
    {
        $this->bot = new TeleBot();

        $updates = $this->bot::getUpdates();

        foreach ($updates as $update)
        {
            $this->handleRequest($update);
        }

        sleep(1);
        $this->handle();
    }

    public function handleRequest(Update $update)
    {
        if ($update->message->from->is_bot) {
            return;
        }

        if ($update->update_id <= $this->lastID) {
            return;
        }

        $this->setLastID($update->update_id);
        $this->chatID = $update->message->from->id;

        switch($update->message->text) {
            case '/start':
            case '/menu':
                $this->menu();
                break;
            case '/ping':
                $this->sendMessage('pong');
                break;
            case '/russian_warship':
                $this->sendMessage('Русский военный корабль — иди нахуй!');
                break;
            default:
                $this->sendMessage('pong');
        }
    }

    public function menu()
    {
        $message = "/menu\n";
        $message .= "/ping\n";
        $message .= "/russian_warship\n";

        $this->sendMessage($message);
    }

    public function sendMessage(string $message)
    {
        $this->bot::sendMessage([
            'chat_id' => $this->chatID,
            'text' => $message,
        ]);
    }

    protected function getLastID(): int
    {
        return File::exists($this->getLastIDFileName()) ? File::get($this->getLastIDFileName()) : 0;
    }

    protected function setLastID(int $updateID): void
    {
        $this->lastID = $updateID;
        File::put($this->getLastIDFileName(), $updateID);
    }

    protected function getLastIDFileName(): string
    {
        return storage_path('last.id');
    }
}
