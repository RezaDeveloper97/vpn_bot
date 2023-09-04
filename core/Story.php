<?php
final class Story
{
    use log;
    use numberConvertor;
    public $user_text;
    public $user_id;
    public $user_reference = 0;
    public $TelegramDb;
    public $TelegramContext;
    public $dataStory;
    public function __construct($json)
    {
        $this->TelegramDb = new TelegramDb();
        $this->TelegramContext = new TelegramContext($json);

        $user = $this->TelegramDb->getUserByTelegramId($this->TelegramContext->telegram_id);

        $this->user_id = $user['id'];
        $this->user_reference = $user['user_reference'];
        $this->user_text = numberConvertor::persianNumberToEnglish($this->TelegramContext->json->message->text);

        $story = $this->TelegramDb->getStory($this->user_id) ?: ['title' => 'welcomeSeller', 'data' => null];

        $this->dataStory = $story['data'];
        $this->{$story['title']}();
    }

    public function iDontKnow($keyboard = null)
    {
        $this->TelegramContext->sendMessage($this->TelegramContext->telegram_id, TextContext::get('iDontKnow'), $keyboard);
    }

    public function welcomeSeller()
    {
        if ($this->user_text == MentTextContext::get('mylink')) {

            $link = $this->TelegramContext::$bot_link . "?start=" . $this->TelegramContext->telegram_id;
            $this->TelegramContext->send_my_link($link);

        } elseif ($this->user_text == MentTextContext::get('buy_vpn')) {

            $parent_id = 0;
            $packages = $this->TelegramDb->getPackagesByParentId($this->user_reference, $parent_id);
            $this->TelegramContext->choose_one($packages);
            $this->TelegramDb->setStory($this->user_id, 'buyVPNLoop', $parent_id);

        } elseif ($this->user_text == MentTextContext::get('my_customers')) {

            $myCustomers = $this->TelegramDb->getMyCustomers($this->user_id);
            $this->TelegramContext->send_my_customers($myCustomers);
            $this->TelegramDb->setStory($this->user_id, 'myCustomers');

        } elseif ($this->user_text == MentTextContext::get('buyers')) {

            $this->TelegramContext->send_my_buyers([]);

        } elseif ($this->user_text == MentTextContext::get('change_price')) {

            $parent_id = 0;
            $packages = $this->TelegramDb->getPackagesByParentId($this->user_reference, $parent_id);
            $this->TelegramContext->choose_one($packages);
            $this->TelegramDb->setStory($this->user_id, 'changePriceLoop', $parent_id);

        } elseif ($this->user_text == MentTextContext::get('prices_list')) {

            $pdf_path = __DIR__ . '/users_pdf/';
            if (!is_dir($pdf_path))
                mkdir($pdf_path);
            $pdf_path .= $this->user_id;
            if (!is_dir($pdf_path))
                mkdir($pdf_path);

            $user_packages = [];
            array_map(function ($row) use (&$user_packages) {
                $user_packages[$row['package_id']] = $row['price'];
                return $row['price'];
            }, $this->TelegramDb->getAllUserPackages($this->user_id));

            $pdf_file_path = $pdf_path . '/PricesList.pdf';
            $PackageTree = new PackageTree();
            $generateTable = $PackageTree->generateTable($this->TelegramDb->getAllPackages($this->user_reference), $user_packages);

            createPDF::pricesList($generateTable, $pdf_file_path);
            $this->TelegramContext->send_file($pdf_file_path, TextContext::get('listOfTheLatestPrices'), MentContext::home());

        } else {
            $this->iDontKnow(MentContext::home());
        }
    }

    public function myCustomers()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
            return false;
        }

        $myCustomers = $this->TelegramDb->getMyCustomers($this->user_id);

        $customers = array_map(function ($item) {
            return $item['vpn_username'];
        }, $myCustomers);

        if (in_array($this->user_text, $customers)) {
            $vpn = $this->TelegramDb->getCustomerByUsername($this->user_id, $this->user_text);
            $this->TelegramContext->send_customer_informations($vpn['vpn_server'], $vpn['vpn_username'], $vpn['vpn_password'], $vpn['register_date']);

            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->iDontKnow();
        }
    }

    public function changePriceLoop()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
            return false;
        }

        $parent_id = $this->dataStory;
        $selected_package = $this->TelegramDb->getPackageByParentIdAndTitle($this->user_reference, $parent_id, $this->user_text);

        if ($selected_package) {
            $parent_id = $selected_package['id'];
            $this->TelegramDb->setDataStory($this->user_id, $parent_id);

            if ($selected_package['price'] != null) {

                $title = $this->TelegramDb->getFullTitlePackage($this->user_reference, $parent_id);

                $price = $this->TelegramDb->getPricePackageByUserId($this->user_reference, $this->user_id, $parent_id);

                $this->TelegramContext->price_package($title, number_format($price), number_format($selected_package['price']));
                $this->TelegramDb->setStory($this->user_id, 'isChangePriceItem', $parent_id);
            } else {
                $packages = $this->TelegramDb->getPackagesByParentId($this->user_reference, $parent_id);
                $this->TelegramContext->choose_one($packages);
            }
        } else {
            $this->iDontKnow();
        }
    }

    public function isChangePriceItem()
    {
        if ($this->user_text == MentTextContext::get('no_answar')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } elseif ($this->user_text == MentTextContext::get('yes_answar')) {
            $this->TelegramContext->send_new_price_package();
            $this->TelegramDb->setStory($this->user_id, 'newPricePackage', $this->dataStory);
        } else {
            $this->iDontKnow(MentContext::change_price_package());
        }
    }

    public function newPricePackage()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } elseif (is_numeric($this->user_text)) {

            $this_packge_price = $this->TelegramDb->getOrginalPricePackage($this->user_reference, $this->dataStory);

            if (floatval($this_packge_price) >= floatval($this->user_text)) {
                $this->TelegramContext->low_price_package_wrong();
                return false;
            }

            $this->TelegramDb->addUserPackage($this->user_id, $this->dataStory, $this->user_text);
            $this->TelegramContext->changed_new_price_package();

            $percentage = ((floatval($this->user_text) - floatval($this_packge_price)) / floatval($this_packge_price)) * 100;
            $percentage = ceil($percentage);

            $cash = floatval($this->user_text) - floatval($this_packge_price);

            $this->TelegramContext->addToPrices(number_format($cash), $percentage);
            $this->TelegramDb->setStory($this->user_id, 'addToPrices', json_encode(['percentage' => $percentage, 'cash' => $cash, 'package_id' => $this->dataStory]));
        } else {
            $this->iDontKnow(MentContext::cancel());
        }
    }

    public function addToPrices()
    {
        $js = json_decode($this->dataStory);
        $percentage = $js->percentage;
        $package_id = $js->package_id;
        $cash = $js->cash;

        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } elseif ($this->user_text == TextContext::get('addToPricesByPercentage', [$percentage])) {

            $this->TelegramDb->addUserPackageByPercentage($this->user_id, $percentage, $this->user_reference, $package_id);
            $this->TelegramContext->changed_new_price_package();

            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } elseif ($this->user_text == TextContext::get('addToPricesByCash', [number_format($cash)])) {

            $this->TelegramDb->addUserPackageByCash($this->user_id, $cash, $this->user_reference, $package_id);
            $this->TelegramContext->changed_new_price_package();

            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->iDontKnow(MentContext::questionYesNo());
        }
    }

    public function buyVPNLoop()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
            return false;
        }

        $parent_id = $this->dataStory;
        $selected_package = $this->TelegramDb->getPackageByParentIdAndTitle($this->user_reference, $parent_id, $this->user_text);

        if ($selected_package) {
            $parent_id = $selected_package['id'];
            $this->TelegramDb->setDataStory($this->user_id, $parent_id);

            if ($selected_package['price'] != null) {

                $title = $this->TelegramDb->getFullTitlePackage($this->user_reference, $parent_id);

                $this->TelegramContext->buy_package($title, number_format($selected_package['price']));
                $this->TelegramDb->setStory($this->user_id, 'buyVPN', $parent_id);
            } else {
                $packages = $this->TelegramDb->getPackagesByParentId($this->user_reference, $parent_id);
                $this->TelegramContext->choose_one($packages);
            }
        } else {
            $this->iDontKnow();
        }
    }

    public function buyVPN()
    {
        if ($this->user_text == MentTextContext::get('no_answar')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } elseif ($this->user_text == MentTextContext::get('yes_answar')) {
            $title = $this->TelegramDb->getFullTitlePackage($this->user_reference, $this->dataStory);
            $price = number_format($this->TelegramDb->getOrginalPricePackage($this->user_reference, $this->dataStory));
            $title .= ' ' . TextContext::get('withPrice', [$price]);
            $this->TelegramContext->payment($title, 'https://yatash.ir/bot/callback.php?tid=' . $this->TelegramContext->telegram_id . '&pid=' . $this->dataStory);
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->iDontKnow(MentContext::buy_package());
        }
    }
}