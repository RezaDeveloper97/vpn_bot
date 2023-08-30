<?php
class Story
{
    public $user_text;
    public $user_id;
    public $TelegramDb;
    public $TelegramContext;
    public $dataStory;
    public function __construct($user_id, $TelegramDb, $TelegramContext)
    {
        $this->user_id = $user_id;
        $this->TelegramDb = $TelegramDb;
        $this->TelegramContext = $TelegramContext;
        $this->user_text = $this->TelegramContext->json->message->text;

        $story = $this->TelegramDb->getStory($this->user_id);

        $this->dataStory = $story['data'];
        $this->{$story['title']}();

    }

    public function iDontKnow()
    {
        $this->TelegramContext->sendMessage($this->TelegramContext->telegram_id, "متوجه درخواست شما نمی شوم");
    }

    public function chooseSellerOrBuyer()
    {
        if ($this->user_text == 'فروشنده') {
            $this->TelegramContext->seller_input_login_user();
            $this->TelegramDb->setStory($this->user_id, 'sellerLogin');
        } elseif ($this->user_text == 'خریدار') {

        } else {
            $this->iDontKnow();
        }
    }

    public function sellerLogin()
    {
        if ($this->user_text == 'انصراف') {
            $this->TelegramContext->backToStart();
            $this->TelegramDb->setStory($this->user_id, 'chooseSellerOrBuyer');
        } else {
            $this->TelegramContext->seller_input_login_pass();
            $this->TelegramDb->setStory($this->user_id, 'sellerLoginPass', $this->user_text);
        }
    }

    public function sellerLoginPass()
    {
        if ($this->user_text == 'انصراف') {
            $this->TelegramContext->backToStart();
            $this->TelegramDb->setStory($this->user_id, 'chooseSellerOrBuyer');
        } elseif ($this->dataStory == 'test' && $this->user_text == '1234') {
            $this->TelegramContext->welcome_seller();
            $this->TelegramDb->setStory($this->user_id, 'welcomeSeller', '');
        } else {
            $this->TelegramContext->seller_input_login_pass_wrong();
        }
    }

    public function welcomeSeller()
    {
        $this->iDontKnow();
    }
}