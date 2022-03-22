<?php

namespace App;

use App\Models\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\Update;

class UserRequest extends BotMethods
{
    public Update $update;
    public Model $user;

    /**
     * @throws Throwable
     */
    public function __construct(TeleBot $bot, Update $update, Model $user)
    {
        $this->update = $update;
        $this->user = $user;
        $this->bot = $bot;
        $this->chatID = $this->update->message->from->id;

        $this->process();
    }

    /**
     * @throws Throwable
     */
    public function process()
    {
        dump($this->update->message->text);
        dump(isset($this->update->message->text) && $this->update->message->text == 'Отмена заявки');
        if (isset($this->update->message->text) && $this->update->message->text == 'Отмена заявки') {
            $this->denyRequest();
            return;
        }

        switch ($this->user->status) {
            case null:
                $this->waitingPhoto();
                break;
            case 'waiting_photo':
                $this->processPhoto();
                break;
            case 'processed_photo':
                $this->waitingLocation();
                break;
            case 'waiting_location':
                $this->processLocation();
                break;
        }
    }

    /**
     * @throws Throwable
     */
    public function waitingPhoto()
    {
        $keyboard = $this->createKeyboard([$this->createButton('Отмена заявки')]);
        $this->sendKeyboardMessage('Пришлите фотографию.', $keyboard);
        $this->user->status = 'waiting_photo';
        $this->user->save();
    }

    /**
     * @throws Throwable
     */
    public function processPhoto()
    {
        if (!isset($this->update->message->photo)) {
            $keyboard = $this->createKeyboard([$this->createButton('Отмена заявки')]);
            $this->sendKeyboardMessage('Пришлите фотографию.', $keyboard);
            return;
        }

        $photos = $this->update->message->photo;
        $photo = end($photos);

        $fileRequest = $this->bot::getFile([
            'file_id' => $photo->file_id,
        ]);

        $url = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $fileRequest->file_path;

        $file = file_get_contents($url);
        $filename = Str::random();

        Storage::disk('local')->put($filename . '.jpg', $file);

        Request::query()->create([
            'creator_id' => $this->user->id,
            'photo' => $filename,
        ]);

        $this->user->status = 'processed_photo';
        $this->user->save();
        $keyboard = $this->createKeyboard([$this->createButton('Отмена заявки')]);
        $this->sendKeyboardMessage('Фотография загружена.', $keyboard);
        $this->waitingLocation();
    }

    /**
     * @throws Throwable
     */
    public function waitingLocation()
    {
        $buttons = [];

        $buttons[] = $this->createButton('Отмена заявки');
        $buttons[] = $this->createLocationButton('Отправить местоположение');

        $keyboard = $this->createKeyboard($buttons);

        $this->sendKeyboardMessage('Отправьте местоположение.', $keyboard);
        $this->user->status = 'waiting_location';
        $this->user->save();
    }

    /**
     * @throws Throwable
     */
    public function processLocation()
    {
        $request = Request::query()->where('creator_id', $this->user->id);

        $request->update([
            'creator_id' => $this->user->id,
            'latitude' => $this->update->message->location->latitude,
            'longitude' => $this->update->message->location->longitude,
        ]);

        $this->user->status = null;
        $this->user->save();

        $keyboard = $this->createKeyboard([$this->createButton('Создать заявку')]);
        $this->sendKeyboardMessage('Заявка была успешно создана с номером ' . $request->id, $keyboard);
    }

    /**
     * @throws Throwable
     */
    public function denyRequest()
    {
        if (!Request::query()->where('creator_id', $this->user->id)->exists()) {
            $this->sendNoKeyboardMessage('Заявка была отменена.');
            return;
        }

        $request = Request::query()->where('creator_id', $this->user->id)->first();

        if ($this->user->status === 'processed_photo' || $this->user->status === 'waiting_location') {
            Storage::disk('local')->delete($request->photo . '.jpg');
        }

        $request->delete();

        $this->user->status = '';
        $this->user->save();

        $this->sendNoKeyboardMessage('Заявка была отменена.');
    }
}
