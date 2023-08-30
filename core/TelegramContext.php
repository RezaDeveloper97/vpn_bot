<?php
class TelegramContext extends ApiBot
{
    use log;
    public $json = '';
    public $telegram_id = '';
    public $firstname = '';
    public $lastname = '';
    public $username = '';

    public function __construct($json)
    {
        $this->json = $json;
        $this->telegram_id = $this->json->message->chat->id;
        $this->firstname = $this->json->message->chat->first_name;
        $this->lastname = $this->json->message->chat->last_name ?? '';
        $this->username = $this->json->message->chat->username ?? '';

        $this->log($this->json);
    }

    public function keyboardGenerator($keyboard)
    {
        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }
    public function start()
    {
        $keyboard = $this->keyboardGenerator([['فروشنده', 'مشتری']]);
        $this->sendMessage($this->telegram_id, "سلام $this->firstname به ربات خرید و فروش VPN خوش آمدید 🌺🤩", $keyboard);
    }

    public function backToStart()
    {
        $keyboard = $this->keyboardGenerator([['فروشنده', 'مشتری']]);
        $this->sendMessage($this->telegram_id, "بازگشت به منوی اصلی", $keyboard);
    }

    public function seller_input_login_user()
    {
        $keyboard = $this->keyboardGenerator([['انصراف']]);
        $this->sendMessage($this->telegram_id, "لطفا نام کاربری خود را وارد کنید", $keyboard);
    }

    public function seller_input_login_pass()
    {
        $keyboard = $this->keyboardGenerator([['انصراف']]);
        $this->sendMessage($this->telegram_id, "لطفا رمز عبور خود را وارد کنید", $keyboard);
    }

    public function seller_input_login_pass_wrong()
    {
        $this->sendMessage($this->telegram_id, "نام کاربری و رمز عبور اشتباه است");
        $this->seller_input_login_pass();
    }

    public function welcome_seller()
    {
        $keyboard = $this->keyboardGenerator([
            ['خریداران', 'تغییر قیمت'],
            ['لینک من']
        ]);
        $this->sendMessage($this->telegram_id, "فروشنده محترم خوش آمدید", $keyboard);
    }
}