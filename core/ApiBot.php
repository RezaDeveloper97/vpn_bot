<?php
abstract class ApiBot
{
    use log;
    public static $api_link = '';
    public static $bot_link = '';

    public function __construct()
    {
        $this::$api_link = ConfigContext::get('api_link');
        $this::$bot_link = ConfigContext::get('bot_link');
    }

    public function sendMessage($chat_id, $text, $reply_markup = null, $pars_mode = null)
    {
        $apiUrl = $this::$api_link . "/sendMessage";

        $params = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $pars_mode
        ];

        if (is_array($reply_markup))
            $params['reply_markup'] = json_encode($reply_markup);
        elseif ($reply_markup === false)
            $params['reply_markup'] = json_encode(['hide_keyboard' => true]);


        if (isset($pars_mode))
            $params['parse_mode'] = $pars_mode;

        return $this->_sender($apiUrl, $params);
    }

    public function sendDocument($chat_id, $document, $caption = null, $reply_markup = null)
    {
        $apiUrl = $this::$api_link . "/sendDocument";

        $params = [
            'chat_id' => $chat_id,
            'document' => new \CURLFile($document)
        ];

        if (is_array($reply_markup))
            $params['reply_markup'] = json_encode($reply_markup);
        elseif ($reply_markup === false)
            $params['reply_markup'] = json_encode(['hide_keyboard' => true]);

        if (isset($caption))
            $params['caption'] = $caption;

        return $this->_fileSender($apiUrl, $params);
    }

    // private method start with _ (underline)

    private function _sender($url, $params)
    {
        return $this->_curlMaker($url, $params, true);
    }

    private function _fileSender($url, $params)
    {
        return $this->_curlMaker($url, $params, false);
    }

    private function _curlMaker($url, $params, bool $is_http_build_query = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $is_http_build_query ? http_build_query($params) : $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (ConfigContext::get('debug_mode') == 'true')
            $this::log($response);

        curl_close($ch);

        return $response;
    }

}