<?php require_once __DIR__ . '/loader.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", TRUE);
ini_set('error_log', "./my-errors.log");

function logFile($inp, $file = 'log.html', $file_type = 0)
{
    ob_start();
    var_dump($inp);
    $result = ob_get_clean();
    file_put_contents($file, "<pre>$result</pre>", $file_type);
}

$json = json_decode(file_get_contents('php://input'));
if (!isset($json->message) || !isset($json->message->chat) || !isset($json->message->chat->type)) {
    logFile('Not Message');
    exit;
}

if ($json->message->chat->type != 'private') {
    logFile('Not Private');
    exit;
}



$user_text = $json->message->text;

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
    $user_id = $TelegramDb->insertUser($telegram_id, $firstname, $lastname, $username);
    $TelegramContext->start();
    $TelegramDb->setStory($user_id, 'chooseSellerOrBuyer');
    exit;
} else {
    $user_id = $TelegramDb->getUserId($telegram_id);
}

new Story($user_id, $TelegramDb, $TelegramContext);