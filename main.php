<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", TRUE);
ini_set('error_log', "./my-errors.log");
require_once('./tcpdf/tcpdf.php');

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
        file_put_contents($file, "<meta charset='utf-8'><pre>$result</pre>", $file_type);
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

    public function sendDocument($chat_id, $document, $caption = null)
    {
        $apiUrl = $this::$api_link . "/sendDocument";

        $params = [
            'chat_id' => $chat_id,
            'document' => new \CURLFile($document)
        ];

        if (isset($caption)) {
            $params['caption'] = $caption;
        }

        $this->_fileSender($apiUrl, $params);
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

    private function _fileSender($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        $this::log($response);

        curl_close($ch);

        return $response;
    }

}

class PackageTree
{
    use log;
    public function buildTree($packages, $parentId)
    {
        $tree = [];

        foreach ($packages as $package) {
            if ($package['parent_id'] == $parentId) {
                $children = self::buildTree($packages, $package['id']);
                if ($children) {
                    $package['children'] = $children;
                }
                $tree[] = $package;
            }
        }

        return $tree;
    }

    public function buildArrays($packages)
    {
        $tree = [];

        foreach ($packages as $package) {
            if ($package['children']) {
                if (isset($package['children'][0]['children'])) {
                    $tree[$package['title']] = $this->buildArrays($package['children']);
                } else {
                    $res = [];
                    foreach ($package['children'] as $row) {
                        $res[$row['id']] = $row['price'];
                    }
                    $tree[$package['title']] = $res;
                }
            }
        }

        return $tree;
    }

    public function generateTable($packages, $user_packages)
    {
        $trees = $this->buildTree($packages, 0);
        $trees = $this->buildArrays($trees);
        $this::log($trees, 'tree2.html');

        $table = <<<EOD
<table dir="rtl" cellspacing="10" cellpadding="5" border="0" align="center">
<tr>
    <td style="padding:5px;background-color:#2f5496;color:#FFFFFF;border:5px solid #2f5496;">تعداد کاربر</td>
    <td style="padding:5px;background-color:#2f5496;color:#FFFFFF;border:5px solid #2f5496;">ترافیک (GB)</td>
    <td style="padding:5px;background-color:#2f5496;color:#FFFFFF;border:5px solid #2f5496;">قیمت یک ماهه</td>
    <td style="padding:5px;background-color:#2f5496;color:#FFFFFF;border:5px solid #2f5496;">قیمت سه ماهه</td>
    <td style="padding:5px;background-color:#2f5496;color:#FFFFFF;border:5px solid #2f5496;">قیمت شش ماهه</td>
</tr>
EOD;

        foreach ($trees as $keyTree => $tree) {
            foreach ($tree as $keyThisPackages => $thisPackages) {
                $table .= '<tr>';
                $table .= '<td style="padding:5px;background-color:#d9d9d9;color:#000000;border:5px solid #d9d9d9;">' . $keyTree . '</td>';
                $table .= '<td style="padding:5px;background-color:#d9d9d9;color:#000000;border:5px solid #d9d9d9;">' . $keyThisPackages . '</td>';
                foreach ($thisPackages as $keyPackage => $package) {
                    $price = isset($user_packages[$keyPackage]) ? number_format($user_packages[$keyPackage]) : number_format($package);
                    $table .= '<td style="padding:5px;background-color:#d9d9d9;color:#000000;border:5px solid #d9d9d9;">' . $price . '</td>';
                }
                $table .= '</tr>';
            }
        }

        $table .= '</table>';

        return $table;
    }
}

class createPDF
{
    public static function pricesList(string $tbl, $pdf_file_path)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language dependent data:
        $lg = array();
        $lg['a_meta_charset'] = 'UTF-8';
        $lg['a_meta_dir'] = 'rtl';
        $lg['a_meta_language'] = 'fa';
        $lg['w_page'] = 'page';
        $pdf->setLanguageArray($lg);

        $pdf->SetFont(' XNazanin ', '', 15, '', true);

        $pdf->AddPage();

        $pdf->Write(0, 'لیست قیمت ها', '', 0, 'R', true, 0, false, false, 0);

        $pdf->writeHTML($tbl, true, false, false, false, '');
        $pdf->Output($pdf_file_path, 'F');
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

    public function insertUser($telegram_id, $firstname, $lastname, $username, $user_reference)
    {
        $this->query("INSERT INTO `users`(`telegram_id`, `firstname`, `lastname`, `username`, `user_reference`) VALUES (?,?,?,?,?)", [$telegram_id, $firstname, $lastname, $username, $user_reference]);
        return $this->lastInsertId();
    }

    public function getUserId($telegram_id)
    {
        $user = $this->fetch("SELECT * FROM `users` WHERE `telegram_id` = ?", [$telegram_id]);
        return $user['id'];
    }

    public function setStory($user_id, $title, $data = null)
    {
        $now_story = $this->getStory($user_id);
        if ($now_story) {
            if (isset($data))
                $this->query(" UPDATE `story` SET `title` = ?, `data` = ? WHERE `user_id` = ?", [$title, $data, $user_id]);
            else
                $this->query(" UPDATE `story` SET `title` = ? WHERE `user_id` = ?", [$title, $user_id]);
        } else
            $this->query(" INSERT INTO `story`(`user_id`, `title`, `data`) VALUES (?,?,?)", [$user_id, $title, $data]);
    }

    public function setDataStory($user_id, $data)
    {
        $this->query("UPDATE `story` SET `data` = ? WHERE `user_id` = ?", [$data, $user_id]);
    }

    public function getStory($user_id)
    {
        return $this->fetch("SELECT * FROM `story` WHERE `user_id` = ?", [$user_id]);
    }

    public function delStory($user_id)
    {
        $this->query("DELETE FROM `story` WHERE `user_id` = ?", [$user_id]);
    }

    public function checkUserPass($user_id, $username, $password)
    {
        return $this->fetch("SELECT * FROM `sellers` WHERE `user_id` = ? AND `username` = ? AND `password` = ?", [$user_id, $username, $password]);
    }

    public function getAllPackages()
    {
        return $this->fetchAll("SELECT * FROM `packages`");
    }

    public function getAllUserPackages($user_id)
    {
        return $this->fetchAll("SELECT * FROM `user_packages` WHERE `user_id` = ?", [$user_id]);
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

    public function getOrginalPricePackage($package_id)
    {
        $package = $this->fetch("SELECT `price` FROM `packages` WHERE `id` = ?", [$package_id]);
        if ($package)
            return $package['price'];

        return 0;
    }

    public function getPricePackageByUserId($use_id, $package_id)
    {
        $user_package = $this->fetch("SELECT `price` FROM `user_packages` WHERE `user_id` = ? AND `package_id` = ?", [$use_id, $package_id]);
        if ($user_package)
            return $user_package['price'];

        return $this->getOrginalPricePackage($package_id);
    }

    public function addUserPackage($user_id, $package_id, $price)
    {
        return $this->query("REPLACE INTO `user_packages`(`user_id`, `package_id`, `price`) VALUES (?,?,?)", [$user_id, $package_id, $price]);
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

trait keyboardGenerator
{
    public static function keyboardGenerator($keyboard)
    {
        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }
}


class MentContext extends MentTextContext
{
    use keyboardGenerator;
    public static function start()
    {
        return static::keyboardGenerator([[parent::get('seller'), parent::get('buyer')]]);
    }

    public static function home()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('renew_vpn'), MentTextContext::get('buy_vpn')],
            [MentTextContext::get('prices_list'), MentTextContext::get('change_price')],
            [MentTextContext::get('mylink'), MentTextContext::get('buyers')]
        ]);
    }

    public static function cancel()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('cancel')]
        ]);
    }

    public static function change_price_package()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('change_price')],
            [MentTextContext::get('cancel')]
        ]);
    }

    public static function buy_package()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('buy_vpn')],
            [MentTextContext::get('cancel')]
        ]);
    }
}


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
        $this->json = $json;
        $this->telegram_id = $this->json->message->chat->id;
        $this->firstname = $this->json->message->chat->first_name;
        $this->lastname = $this->json->message->chat->last_name ?? '';
        $this->username = $this->json->message->chat->username ?? '';

        $this::log($this->json);
    }


    public function start()
    {
        $keyboard = MentContext::home();
        $this->sendMessage($this->telegram_id, "سلام $this->firstname به ربات خرید و فروش VPN خوش آمدید 🌺🤩", $keyboard);
    }

    public function backToStart()
    {
        $keyboard = MentContext::start();
        $this->sendMessage($this->telegram_id, "بازگشت به منوی اصلی", $keyboard);
    }

    public function seller_input_login_user()
    {
        $keyboard = MentContext::cancel();
        $this->sendMessage($this->telegram_id, "لطفا نام کاربری خود را وارد کنید", $keyboard);
    }

    public function seller_input_login_pass()
    {
        $keyboard = MentContext::cancel();
        $this->sendMessage($this->telegram_id, "لطفا رمز عبور خود را وارد کنید", $keyboard);
    }

    public function seller_input_login_pass_wrong()
    {
        $this->sendMessage($this->telegram_id, "نام کاربری و رمز عبور اشتباه است");
        $this->seller_input_login_pass();
    }

    public function welcome_seller()
    {
        $keyboard = MentContext::home();

        $this->sendMessage($this->telegram_id, "فروشنده محترم خوش آمدید", $keyboard);
    }

    public function backToHome()
    {
        $keyboard = MentContext::home();
        $this->sendMessage($this->telegram_id, "بازگشت به منوی اصلی", $keyboard);
    }

    public function send_my_link($link)
    {
        $keyboard = MentContext::home();
        $this->sendMessage($this->telegram_id, "این لینک معرفی شماست؛ هر فردی که از طریق این لینک وارد شود و VPN خریداری کند، موجودی حساب شما افزایش می‌یابد.\n $link", $keyboard);
    }

    public function send_my_buyers(array $buyers)
    {
        $keyboard = MentContext::home();
        if (count($buyers) > 0) {
        } else {
            $this->sendMessage($this->telegram_id, "شما فعلا هیچ خریداری ندارید", $keyboard);
        }
    }

    public function choose_one(array $packages)
    {
        $packages = array_map(function ($item) {
            return [$item['title']];
        }, $packages);

        $packages[] = [MentTextContext::get('cancel')];
        $keyboard = self::keyboardGenerator($packages);
        $this->sendMessage($this->telegram_id, "یکی از موارد زیر را انتخاب کنید", $keyboard);
    }

    public function price_package($title, $price, $orginal_price)
    {
        $keyboard = MentContext::change_price_package();
        $orginal_price_text = $price == $orginal_price ? '' : "(قیمت اصلی : $orginal_price)";
        $this->sendMessage($this->telegram_id, "ایتم <b>$title</b> به قیمت <b>$price</b> می باشد. $orginal_price_text\nآیا میخواهید تغییر قیمت لحاظ کنید ؟", $keyboard, 'HTML');
    }

    public function buy_package($title, $price)
    {
        $keyboard = MentContext::buy_package();
        $this->sendMessage($this->telegram_id, "آیا <b>$title</b> با قیمت <b>$price</b> تومان خریداری می‌کنید ؟", $keyboard, 'HTML');
    }

    public function send_new_price_package()
    {
        $this->sendMessage($this->telegram_id, "لطفا قیمت جدید این ایتم رو وارد کنید (فقط عدد)");
    }

    public function low_price_package_wrong()
    {
        $this->sendMessage($this->telegram_id, "قیمت اعلام شده از قیمت اصلی پکیج کمتر میباشد . لطفا مبلغ بیشتری را وارد کنید");
    }

    public function changed_new_price_package()
    {
        $this->sendMessage($this->telegram_id, "قیمت با موفقیت ثبت شد");
    }

    public function send_file($docfilePath, $caption = null)
    {
        $this->sendDocument($this->telegram_id, $docfilePath, $caption);
    }
}

class Story
{
    use log;
    public $user_text;
    public $user_id;
    public $user_reference = 0;
    public $TelegramDb;
    public $TelegramContext;
    public $dataStory;
    public function __construct($user_id, $TelegramDb, $TelegramContext)
    {
        $this->user_id = $user_id;
        $this->TelegramDb = $TelegramDb;
        $this->TelegramContext = $TelegramContext;
        $this->user_text = $this->TelegramContext->json->message->text;

        $story = $this->TelegramDb->getStory($this->user_id) ?: ['title' => 'welcomeSeller', 'data' => null];

        $this->dataStory = $story['data'];
        $this->{$story['title']}();
    }

    public function iDontKnow($keyboard = null)
    {
        $this->TelegramContext->sendMessage($this->TelegramContext->telegram_id, "متوجه درخواست شما نمی شوم", $keyboard);
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
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->TelegramContext->seller_input_login_pass_wrong();
        }
    }

    public function welcomeSeller()
    {
        if ($this->user_text == MentTextContext::get('mylink')) {
            $link = $this->TelegramContext::$bot_link . "?start=" . $this->TelegramContext->telegram_id;
            $this->TelegramContext->send_my_link($link);
        } elseif ($this->user_text == MentTextContext::get('buy_vpn')) {
            $parent_id = 0;
            $packages = $this->TelegramDb->getPackagesByParentId($parent_id);
            $this->TelegramContext->choose_one($packages);
            $this->TelegramDb->setStory($this->user_id, 'buyVPNLoop', $parent_id);
        } elseif ($this->user_text == MentTextContext::get('buyers')) {
            $this->TelegramContext->send_my_buyers([]);
        } elseif ($this->user_text == MentTextContext::get('change_price')) {
            $parent_id = 0;
            $packages = $this->TelegramDb->getPackagesByParentId($parent_id);
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
            $generateTable = $PackageTree->generateTable($this->TelegramDb->getAllPackages(), $user_packages);

            createPDF::pricesList($generateTable, $pdf_file_path);
            $this->TelegramContext->send_file($pdf_file_path, 'لیست اخرین قیمت ها VPN');

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
        $selected_package = $this->TelegramDb->getPackageByParentIdAndTitle($parent_id, $this->user_text);

        if ($selected_package) {
            $parent_id = $selected_package['id'];
            $this->TelegramDb->setDataStory($this->user_id, $parent_id);

            if ($selected_package['price'] != null) {

                $titles = $this->TelegramDb->getFullTitlePackage($parent_id);

                $title = join(' ', array_reverse($titles));

                $price = $this->TelegramDb->getPricePackageByUserId($this->user_id, $parent_id);

                $this->TelegramContext->price_package($title, number_format($price), number_format($selected_package['price']));
                $this->TelegramDb->setStory($this->user_id, 'isChangePriceItem', $parent_id);
            } else {
                $packages = $this->TelegramDb->getPackagesByParentId($parent_id);
                $this->TelegramContext->choose_one($packages);
            }
        } else {
            $this->iDontKnow();
        }
    }

    public function isChangePriceItem()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } elseif ($this->user_text == MentTextContext::get('change_price')) {
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
            $this->TelegramDb->delStory($this->user_id);
        } elseif (is_numeric($this->user_text)) {
            $this_packge_price = $this->TelegramDb->getOrginalPricePackage($this->user_id, $this->dataStory);
            if ($this_packge_price >= $this->user_text) {
                $this->TelegramContext->low_price_package_wrong();
                return false;
            }

            $this->TelegramDb->addUserPackage($this->user_id, $this->dataStory, $this->user_text);
            $this->TelegramContext->changed_new_price_package();
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
        } else {
            $this->iDontKnow();
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
        $selected_package = $this->TelegramDb->getPackageByParentIdAndTitle($parent_id, $this->user_text);

        if ($selected_package) {
            $parent_id = $selected_package['id'];
            $this->TelegramDb->setDataStory($this->user_id, $parent_id);

            if ($selected_package['price'] != null) {

                $titles = $this->TelegramDb->getFullTitlePackage($parent_id);

                $title = join(' ', array_reverse($titles));

                // $price = $this->TelegramDb->getPricePackageByUserId($this->user_id, $parent_id);

                $this->TelegramContext->buy_package($title, number_format($selected_package['price']));
                $this->TelegramDb->setStory($this->user_id, 'buyVPN', $parent_id);
            } else {
                $packages = $this->TelegramDb->getPackagesByParentId($parent_id);
                $this->TelegramContext->choose_one($packages);
            }
        } else {
            $this->iDontKnow();
        }
    }

    public function buyVPN()
    {
        if ($this->user_text == MentTextContext::get('cancel')) {
            $this->TelegramContext->backToHome();
            $this->TelegramDb->delStory($this->user_id);
            return false;
        }


        $this->iDontKnow();
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

if (strlen($user_text) > 6 && substr($user_text, 0, 6) == '/start') {
    $TelegramContext->sendMessage($telegram_id, substr($user_text, 6));
    exit;
}

if (!$TelegramDb->checkUser($telegram_id)) {

    $TelegramDb->insertUser($telegram_id, $firstname, $lastname, $username, 0);
    $TelegramContext->start();
    exit;
} else {
    $user_id = $TelegramDb->getUserId($telegram_id);
}
new Story($user_id, $TelegramDb, $TelegramContext);