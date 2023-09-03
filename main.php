<?php require_once('./loader.php');

$json = json_decode(file_get_contents('php://input'));

if (!isset($json->message) || !isset($json->message->chat) || !isset($json->message->chat->type)) {
    log::log('Not Message', 'log.html', 0);
    exit;
}

if ($json->message->chat->type != 'private') {
    log::log('Not Private', 'log.html', 0);
    exit;
}

$user_text = numberConvertor::persianNumberToEnglish($json->message->text);
$TelegramDb = new TelegramDb();
$TelegramContext = new TelegramContext($json);
$telegram_id = $json->message->chat->id;
$firstname = $json->message->chat->first_name;
$lastname = $json->message->chat->last_name ?? '';
$username = $json->message->chat->username ?? '';
if ($user_text == '/reset') {
    $TelegramDb->truncateUserTable();
    $TelegramContext->sendMessage($telegram_id, "reset shod");
    exit;
}

if (!$TelegramDb->checkUser($telegram_id)) {
    $user_refrence = 0;

    if (strlen($user_text) > 6 && substr($user_text, 0, 6) == '/start') {
        $ref = trim(substr($user_text, 6));
        $user_ref = $TelegramDb->getUserByTelegramId($ref);
        $user_refrence = $user_ref ? $user_ref['id'] : 0;
    }

    $TelegramDb->insertUser($telegram_id, $firstname, $lastname, $username, $user_refrence);
    $TelegramContext->start();
    exit;
}

$user = $TelegramDb->getUserByTelegramId($telegram_id);
new Story($user, $TelegramDb, $TelegramContext);