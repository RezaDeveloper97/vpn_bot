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
        return $stmt->fetchAll();
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

    public function sendMessage($chat_id, $text, $reply_markup = null, $pars_mode = null)
    {
        $apiUrl = $this::$api_link . "/sendMessage";

        $params = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $pars_mode
        ];

        if (is_array($reply_markup)) {
            $params['reply_markup'] = json_encode($reply_markup);
        } elseif ($reply_markup === false) {
            $params['reply_markup'] = json_encode(['hide_keyboard' => true]);
        }

        if (isset($pars_mode)) {
            $params['parse_mode'] = $pars_mode;
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

        $this::log($response);

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
        return $this->fetch("select * from users where telegram_id = ?", [$telegram_id]);
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

    public function setStory($user_id, $title, $data = null)
    {
        $this->query("REPLACE INTO `story`(`user_id`, `title`, `data`) VALUES (?,?,?)", [$user_id, $title, $data]);
    }

    public function setDataStory($user_id, $data)
    {
        $this->query("UPDATE `story` SET `data` = ? WHERE `user_id` = ?", [$data, $user_id]);
    }

    public function getStory($user_id)
    {
        return $this->fetch("SELECT * FROM `story` WHERE `user_id` = ?", [$user_id]);
    }

    public function checkUserPass($user_id, $username, $password)
    {
        return $this->fetch("SELECT * FROM `sellers` WHERE `user_id` = ? AND `username` = ? AND `password` = ?", [$user_id, $username, $password]);
    }

    public function getPackagesByParentId($parent_id)
    {
        return $this->fetchAll("SELECT * FROM `packages` WHERE `parent_id` = ?", [$parent_id]);
    }

    public function getPackageByParentIdAndTitle($parent_id, $title)
    {
        return $this->fetch("SELECT * FROM `packages` WHERE `parent_id` = ? AND `title` = ?", [$parent_id, $title]);
    }

    public function getFullTitlePackage($id)
    {
        $result = [];
        $this_package = $this->fetch("SELECT * FROM `packages` WHERE `id` = ?", [$id]);
        $result[] = $this_package['title'];

        if ($this_package['parent_id'] != 0) {
            $parentTitles = $this->getFullTitlePackage($this_package['parent_id']);
            $result = array_merge($result, $parentTitles);
        }

        return $result;
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

    public function backToHome()
    {
        $keyboard = $this->keyboardGenerator([
            [MentTextContext::get('buyers'), MentTextContext::get('change_price')],
            [MentTextContext::get('mylink')]
        ]);
        $this->sendMessage($this->telegram_id, "بازگشت به منوی اصلی", $keyboard);
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

        if (count($buyers) > 0) {

        } else {
            $this->sendMessage($this->telegram_id, "شما فعلا هیچ خریداری ندارید", $keyboard);
        }
    }

    public function change_price_loop(array $packages)
    {
        $packages = array_map(function ($item) {
            return [$item['title']];
        }, $packages);
        $packages[] = [MentTextContext::get('cancel')];
        $keyboard = $this->keyboardGenerator($packages);
        $this->sendMessage($this->telegram_id, "یکی از موارد زیر را انتخاب کنید", $keyboard);
    }

    public function price_package($title, $price)
    {
        $keyboard = $this->keyboardGenerator([
            [MentTextContext::get('change_price')],
            [MentTextContext::get('cancel')]
        ]);
        $this->sendMessage($this->telegram_id, "ایتم <b>$title</b> به قیمت <b>$price</b> می باشد.\nآیا میخواهید تغییر قیمت لحاظ کنید ؟", $keyboard, 'HTML');
    }

    public function send_new_price_package()
    {
        $this->sendMessage($this->telegram_id, "لطفا قیمت جدید این ایتم رو وارد کنید (فقط عدد)", false);
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
            return false;
        }

        $checkUserPass = $this->TelegramDb->checkUserPass($this->user_id, $this->dataStory, $this->user_text);

        if ($checkUserPass) {
            $this->TelegramContext->welcome_seller();
            $this->TelegramDb->setStory($this->user_id, 'welcomeSeller', '');
        } else {
            $this->TelegramContext->seller_input_login_pass_wrong();
        }
    }

    public function welcomeSeller()
    {
        if ($this->user_text == MentTextContext::get('mylink')) {
            $link = $this->TelegramContext::$bot_link . "?start=" . $this->TelegramContext->telegram_id;
            $this->TelegramContext->send_my_link($link);
        } elseif ($this->user_text == MentTextContext::get('buyers')) {
            $this->TelegramContext->send_my_buyers([]);
        } elseif ($this->user_text == MentTextContext::get('change_price')) {
            $parent_id = 0;
            $packages = $this->TelegramDb->getPackagesByParentId($parent_id);
            $this->TelegramContext->change_price_loop($packages);
            $this->TelegramDb->setStory($this->user_id, 'changePriceLoop', $parent_id);
        } else {
            $this->iDontKnow();
        }
    }

    public function changePriceLoop()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->setStory($this->user_id, 'welcomeSeller');
            return false;
        }

        $parent_id = $this->dataStory;
        $selected_package = $this->TelegramDb->getPackageByParentIdAndTitle($parent_id, $this->user_text);

        if ($selected_package) {
            $selected_parent_id = $selected_package['id'];
            $this->TelegramDb->setDataStory($this->user_id, $selected_parent_id);

            if ($selected_package['price'] != null) {

                $titles = $this->TelegramDb->getFullTitlePackage($selected_parent_id);

                $title = join(' ', array_reverse($titles));

                $this->TelegramContext->price_package($title, number_format($selected_package['price']));
                $this->TelegramDb->setStory($this->user_id, 'isChangePriceItem', $parent_id);

            } else {
                $packages = $this->TelegramDb->getPackagesByParentId($selected_parent_id);
                $this->TelegramContext->change_price_loop($packages);
            }
        } else {
            $this->iDontKnow();
        }
    }

    public function isChangePriceItem()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->setStory($this->user_id, 'welcomeSeller');
        } elseif($this->user_text == MentTextContext::get('change_price')) {
            $this->TelegramContext->send_new_price_package();
            $this->TelegramDb->setStory($this->user_id, 'newPricePackage', $this->dataStory);
            
        } else {
            $this->iDontKnow();
        }
    }

    public function newPricePackage()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->setStory($this->user_id, 'welcomeSeller');
        } elseif(is_numeric($this->user_text)) {
            // $this->TelegramContext->send_new_price_package();
            // $this->TelegramDb->setStory($this->user_id, 'newPricePackage', $this->dataStory);
            $this->TelegramContext->sendMessage($this->TelegramContext->telegram_id, "True");
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