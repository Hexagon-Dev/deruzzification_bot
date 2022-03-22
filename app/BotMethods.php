<?php

namespace App;

use Throwable;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\Keyboard;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardMarkup;
use WeStacks\TeleBot\Objects\Keyboard\ReplyKeyboardRemove;
use WeStacks\TeleBot\Objects\KeyboardButton;

abstract class BotMethods
{
    public TeleBot $bot;
    public int $chatID;

    /**
     * @throws Throwable
     */
    public function sendNoKeyboardMessage(string $message)
    {
        $this->bot::sendMessage([
            'chat_id' => $this->chatID,
            'text' => $message,
            'reply_markup' => new ReplyKeyboardRemove([
                'remove_keyboard' => true,
            ]),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function sendKeyboardMessage(string $message, Keyboard $keyboard)
    {
        $this->bot::sendMessage([
            'chat_id' => $this->chatID,
            'text' => $message,
            'reply_markup' => $keyboard,
        ]);
    }

    /**
     * @param array $buttons
     * @return ReplyKeyboardMarkup
     * @throws Throwable
     */
    public function createKeyboard(array $buttons): ReplyKeyboardMarkup
    {
        return new ReplyKeyboardMarkup([
            'keyboard' => [
                $buttons
            ]
        ]);
    }

    /**
     * @param string $text
     * @return KeyboardButton
     * @throws Throwable
     */
    public function createButton(string $text): KeyboardButton
    {
        return new KeyboardButton([
            'text' => $text,
        ]);
    }

    /**
     * @param string $text
     * @return KeyboardButton
     * @throws Throwable
     */
    public function createLocationButton(string $text): KeyboardButton
    {
        return new KeyboardButton([
            'text' => $text,
            'request_location' => true,
        ]);
    }
}
