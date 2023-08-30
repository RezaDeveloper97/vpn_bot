<?php

class ApiBot
{
    use log;
    public $api_link = 'https://api.telegram.org/bot6365565872:AAHWnyrWlm1xYZAezPxMktYwRVRVrY_-Osg';

    public function sendMessage($chat_id, $text, $reply_markup = null)
    {
        $apiUrl = $this->api_link . "/sendMessage";

        $params = [
            'chat_id' => $chat_id,
            'text' => $text
        ];

        if (is_array($reply_markup)) {
            $params['reply_markup'] = json_encode($reply_markup);
        } elseif ($reply_markup === false) {
            $params['reply_markup'] = json_encode(['hide_keyboard' => true]);
        }

        $this->_sender($apiUrl, $params);
    }

    // private method start with _ (underline)

    private function _sender($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

}