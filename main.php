<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", TRUE);
ini_set('error_log', "./my-errors.log");

function logFile($inp, $file = 'log.html', $file_type = 0)
{
    log::log($inp, $file, $file_type);
}

trait log
{
    public static function log($inp, $file = 'log.html', $file_type = 0)
    {
        ob_start();
        var_dump($inp);
        $result = ob_get_clean();
        file_put_contents($file, "<pre>$result</pre>", $file_type);
    }
}

class Database
{
    use log;
    private $host = 'localhost';
    private $db_name = 'yatash_bot';
    private $username = 'yatash_botadmin';
    private $password = '&]$g~k?sz}{3';
    private $conn;

    public function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->conn = new \PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            $this::log($e->getMessage(), 'dblog.html');
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function query($query, $params = [])
    {
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($query, $params = [])
    {
        $stmt = $this->query($query, $params);
        return json_encode($stmt->fetchAll());
    }

    public function fetch($query, $params = [])
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function truncateUserTable()
    {
        try {
            $this->query('TRUNCATE TABLE `users`');
            $this->query('TRUNCATE TABLE `story`');
        } catch (PDOException $e) {
            $this::log($e->getMessage(), 'dblog.html');
        }
    }

}

class ApiBot
{
    use log;
    public static $api_link = 'https://api.telegram.org/bot6365565872:AAHWnyrWlm1xYZAezPxMktYwRVRVrY_-Osg';
    public static $bot_link = 'http://t.me/secure_net_vpn_bot';

    public function sendMessage($chat_id, $text, $reply_markup = null)
    {
        $apiUrl = $this::$api_link . "/sendMessage";

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

class TelegramDb extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkUser($telegram_id)
    {
        $user = $this->fetch("select * from users where telegram_id = ?", [$telegram_id]);
        return $user;
    }

    public function insertUser($telegram_id, $firstname, $lastname, $username)
    {
        // $this::log("INSERT INTO `users`(`telegram_id`, `firstname`, `lastname`, `username`) VALUES ('$telegram_id', '$firstname', '$lastname', '$username')");
        $this->query("INSERT INTO `users`(`telegram_id`, `firstname`, `lastname`, `username`) VALUES (?,?,?,?)", [$telegram_id, $firstname, $lastname, $username]);
        return $this->lastInsertId();
    }

    public function getUserId($telegram_id)
    {
        $user = $this->fetch("SELECT * FROM `users` WHERE `telegram_id` = ?", [$telegram_id]);
        return $user['id'];
    }

    public function setStory($user_id, $title, $data = null): void
    {
        $this->query("REPLACE INTO `story`(`user_id`, `title`, `data`) VALUES (?,?,?)", [$user_id, $title, $data]);
    }

    public function getStory($user_id)
    {
        return $this->fetch("SELECT * FROM `story` WHERE `user_id` = ?", [$user_id]);
    }
}

class MentTextContext
{
    public static function get(string $name): string
    {
        $menu = parse_ini_file('content.ini', true);
        return $menu['menu'][$name];
    }

}


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

        $this::log($this->json);
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
        $keyboard = $this->keyboardGenerator([[MentTextContext::get('seller'), MentTextContext::get('buyer')]]);
        $this->sendMessage($this->telegram_id, "سلام $this->firstname به ربات خرید و فروش VPN خوش آمدید 🌺🤩", $keyboard);
    }

    public function backToStart()
    {
        $keyboard = $this->keyboardGenerator([[MentTextContext::get('seller'), MentTextContext::get('buyer')]]);
        $this->sendMessage($this->telegram_id, "بازگشت به منوی اصلی", $keyboard);
    }

    public function seller_input_login_user()
    {
        $keyboard = $this->keyboardGenerator([[MentTextContext::get('cancel')]]);
        $this->sendMessage($this->telegram_id, "لطفا نام کاربری خود را وارد کنید", $keyboard);
    }

    public function seller_input_login_pass()
    {
        $keyboard = $this->keyboardGenerator([[MentTextContext::get('cancel')]]);
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
            [MentTextContext::get('buyers'), MentTextContext::get('change_price')],
            [MentTextContext::get('mylink')]
        ]);
        $this->sendMessage($this->telegram_id, "فروشنده محترم خوش آمدید", $keyboard);
    }

    public function send_my_link($link)
    {
        $keyboard = $this->keyboardGenerator([
            [MentTextContext::get('buyers'), MentTextContext::get('change_price')],
            [MentTextContext::get('mylink')]
        ]);
        $this->sendMessage($this->telegram_id, "این لینک معرفی شماست؛ هر فردی که از طریق این لینک وارد شود و VPN خریداری کند، موجودی حساب شما افزایش می‌یابد.\n $link", $keyboard);
    }

    public function send_my_buyers(array $buyers)
    {
        $keyboard = $this->keyboardGenerator([
            [MentTextContext::get('buyers'), MentTextContext::get('change_price')],
            [MentTextContext::get('mylink')]
        ]);

        if(count($buyers) > 0) {

        } else {
            $this->sendMessage($this->telegram_id, "شما فعلا هیچ خریداری ندارید", $keyboard);
        }
    }
}

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
        if ($this->user_text == MentTextContext::get('seller')) {
            $this->TelegramContext->seller_input_login_user();
            $this->TelegramDb->setStory($this->user_id, 'sellerLogin');
        } elseif ($this->user_text == MentTextContext::get('buyer')) {

        } else {
            $this->iDontKnow();
        }
    }

    public function sellerLogin()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToStart();
            $this->TelegramDb->setStory($this->user_id, 'chooseSellerOrBuyer');
        } else {
            $this->TelegramContext->seller_input_login_pass();
            $this->TelegramDb->setStory($this->user_id, 'sellerLoginPass', $this->user_text);
        }
    }

    public function sellerLoginPass()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
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
        if($this->user_text == MentTextContext::get('mylink')) {
            $link = $this->TelegramContext::$bot_link . "?start=" . $this->TelegramContext->telegram_id;
            $this->TelegramContext->send_my_link($link);
        } elseif($this->user_text == MentTextContext::get('buyers')) {
            $this->TelegramContext->send_my_buyers([]);
        } else {
            $this->iDontKnow();
        }
    }
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