<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRequest;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Throwable;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\KeyboardButton;
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

    /**
     * @var int
     */
    public int $lastID;

    /**
     * @var int
     */
    public int $chatID;

    /**
     * @var TeleBot
     */
    public TeleBot $bot;

    /**
     * @var Update
     */
    public Update $update;

    /**
     * @var Model
     */
    public Model $user;

    public function __construct()
    {
        parent::__construct();

        $this->lastID = $this->getLastID();
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function handle()
    {
        $this->bot = new TeleBot();

        $updates = $this->bot::getUpdates();

        foreach ($updates as $update)
        {
            $this->update = $update;

            $this->handleRequest();
        }

        sleep(1);
        $this->handle();
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function handleRequest()
    {
        if ($this->update->message->from->is_bot) {
            return;
        }

        if ($this->update->update_id <= $this->lastID) {
            return;
        }

        if (!$this->userExists()) {
            $this->createUser();
        }

        $this->user = User::query()->where('telegram_id', $this->update->message->from->id)->first();

        $this->setLastID($this->update->update_id);
        $this->chatID = $this->update->message->from->id;

        if (!is_null($this->user->status)) {
            $this->processRequest();
            return;
        }

        if (is_null($this->update->message->text)) {
            $this->sendMessage('Непонятна...');
            return;
        }

        switch($this->update->message->text) {
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
            case 'Создать заявку':
            case 'Отмена заявки':
                $this->processRequest();
                break;
            default:
                $this->sendMessage('Непонятна...');
        }
    }

    /**
     * @throws Throwable
     */
    public function processRequest()
    {
        new UserRequest($this->bot, $this->update, $this->user);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function menu()
    {
        $message = "/menu\n";
        $message .= "/ping\n";
        $message .= "/russian_warship\n";

        $this->sendMessage($message);
    }

    /**
     * @param string $message
     * @return void
     * @throws Throwable
     */
    public function sendMessage(string $message)
    {
        $keyboard = new ReplyKeyboardMarkup([
            'keyboard' => [
                [
                    new KeyboardButton([
                        'text' => 'Создать заявку'
                    ]),
                ]
            ]
        ]);

        $this->bot::sendMessage([
            'chat_id' => $this->chatID,
            'text' => $message,
            'reply_markup' => $keyboard,
        ]);
    }

    /**
     * @return int
     */
    protected function getLastID(): int
    {
        return File::exists($this->getLastIDFileName()) ? File::get($this->getLastIDFileName()) : 0;
    }

    /**
     * @param int $updateID
     * @return void
     */
    protected function setLastID(int $updateID): void
    {
        $this->lastID = $updateID;
        File::put($this->getLastIDFileName(), $updateID);
    }

    /**
     * @return string
     */
    protected function getLastIDFileName(): string
    {
        return storage_path('last.id');
    }

    public function userExists(): bool
    {
        return User::query()->where('telegram_id', $this->update->message->from->id)->exists();
    }

    public function createUser()
    {
        User::query()->create([
            'name' => $this->update->message->from->first_name,
            'telegram_id' => $this->update->message->from->id,
        ]);
    }
}
