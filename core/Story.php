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
    public function __construct($user, $TelegramDb, $TelegramContext)
    {
        $this->user_id = $user['id'];
        $this->user_reference = $user['user_reference'];
        $this->TelegramDb = $TelegramDb;
        $this->TelegramContext = $TelegramContext;
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
            $this->TelegramContext->send_file($pdf_file_path, TextContext::get('listOfTheLatestPrices'));

        } else {
            $this->iDontKnow(MentContext::home());
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
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->iDontKnow(MentContext::cancel());
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
            $this->TelegramContext->payment($title, 'https://www.zarinpal.com/');
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->iDontKnow(MentContext::buy_package());
        }
    }
}