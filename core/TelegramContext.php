<?php
class TelegramContext extends ApiBot
{
    use log;
    use keyboardGenerator;
    public $json = '';
    public $telegram_id = '';
    public $firstname = '';
    public $lastname = '';
    public $username = '';

    public function __construct($json)
    {
        parent::__construct();
        $this->json = $json;
        $this->telegram_id = $this->json->message->chat->id;
        $this->firstname = $this->json->message->chat->first_name;
        $this->lastname = $this->json->message->chat->last_name ?? '';
        $this->username = $this->json->message->chat->username ?? '';

        if (ConfigContext::get('debug_mode'))
            $this::log($this->json);
    }

    public function start()
    {
        $this->sendMessage($this->telegram_id, TextContext::get('welcome', [$this->firstname]), false);
    }

    public function import_name()
    {
        $this->sendMessage($this->telegram_id, TextContext::get('inportName'), false);
    }

    public function wrong_name_length()
    {
        $this->sendMessage($this->telegram_id, TextContext::get('wrongNameLength'), false);
    }

    public function wrong_name_persian()
    {
        $this->sendMessage($this->telegram_id, TextContext::get('wrongNamePersian'), false);
    }

    public function backToHome()
    {
        $keyboard = MentContext::home();
        $this->sendMessage($this->telegram_id, TextContext::get('backToHome'), $keyboard);
    }

    public function send_my_link($link)
    {
        $keyboard = MentContext::home();
        $this->sendMessage($this->telegram_id, TextContext::get('myLink', [$link]), $keyboard);
    }

    public function send_my_buyers(array $buyers)
    {
        if (count($buyers) > 0) {
            $keyboard = [];
            $keyboard[] = [MentTextContext::get('cancel')];

            $buyers = array_map(function ($item) {
                return ["$item[telegram_id] - $item[firstname]"];
            }, $buyers);

            $keyboard = self::keyboardGenerator(array_merge($keyboard, $buyers));

            $this->sendMessage($this->telegram_id, TextContext::get('chooseOne'), $keyboard);
        } else {
            $keyboard = MentContext::home();
            $this->sendMessage($this->telegram_id, TextContext::get('notingCustomers'), $keyboard);
        }
    }

    public function send_my_customers(array $customers)
    {
        if (count($customers) > 0) {
            $keyboard = [];
            $keyboard[] = [MentTextContext::get('cancel')];

            $customers = array_map(function ($item) {
                return [$item['vpn_username']];
            }, $customers);

            $keyboard = self::keyboardGenerator(array_merge($keyboard, $customers));

            $this->sendMessage($this->telegram_id, TextContext::get('chooseOne'), $keyboard);
        } else {
            $keyboard = MentContext::home();
            $this->sendMessage($this->telegram_id, TextContext::get('notingCustomers'), $keyboard);
        }
    }

    public function send_customer_informations($package_name, $server, $username, $password, $register_date, $capacity, $withKeyboard = true)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => MentTextContext::get('renew_vpn'), 'callback_data' => "renew_vpn-$username"],
                    ['text' => MentTextContext::get('expansion_capacity_vpn'), 'callback_data' => "expansion_capacity_vpn-$username"],
                ],
            ],
        ];
        $this->sendMessage($this->telegram_id, TextContext::get('customerInformations', [$package_name, $server, $username, $password, $register_date, $capacity]), $withKeyboard ? $keyboard : null);
    }

    public function choose_one(array $packages)
    {
        $packages = array_map(function ($item) {
            return [$item['title']];
        }, $packages);

        $packages[] = [MentTextContext::get('cancel')];
        $keyboard = self::keyboardGenerator($packages);
        $this->sendMessage($this->telegram_id, TextContext::get('chooseOne'), $keyboard);
    }

    public function payment($text, $url)
    {
        $this->sendMessage($this->telegram_id, $text, [
            'inline_keyboard' => [
                [
                    ['text' => MentTextContext::get('payment'), 'url' => $url],
                ],
            ],
        ], 'HTML');
    }

    public function price_package($title, $price, $orginal_price)
    {
        $keyboard = MentContext::change_price_package();
        $orginal_price_text = $price == $orginal_price ? '' : TextContext::get('orginalPriceText', [$orginal_price]);
        $this->sendMessage($this->telegram_id, TextContext::get('pricePackage', [$title, $price, $orginal_price_text]), $keyboard, 'HTML');
    }

    public function buy_package($title, $price)
    {
        $keyboard = MentContext::buy_package();
        $this->sendMessage($this->telegram_id, TextContext::get('buyPackage', [$title, $price]), $keyboard, 'HTML');
    }

    public function send_new_price_package()
    {
        $keyboard = MentContext::cancel();
        $this->sendMessage($this->telegram_id, TextContext::get('newPricePackage'), $keyboard);
    }

    public function low_price_package_wrong()
    {
        $this->sendMessage($this->telegram_id, TextContext::get('lowPricePackageWrong'));
    }

    public function changed_new_price_package()
    {
        $this->sendMessage($this->telegram_id, TextContext::get('SuccessfullyDoing'));
    }

    public function addToPrices($number, $percentage)
    {
        $keyboard = [];
        $keyboard[] = [TextContext::get('addToPricesByCash', [$number])];
        $keyboard[] = [TextContext::get('addToPricesByPercentage', [$percentage])];
        $keyboard[] = [MentTextContext::get('cancel')];
        $keyboard = self::keyboardGenerator($keyboard);
        $this->sendMessage($this->telegram_id, TextContext::get('addToPrices', [$number, $percentage]), $keyboard);
    }

    public function send_file($docfilePath, $caption = null, $reply_markup = null)
    {
        $this->sendDocument($this->telegram_id, $docfilePath, $caption, $reply_markup);
    }
}