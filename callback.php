<?php require_once('./loader.php');

if (isset($_POST['telegram_id'], $_POST['package_id'])) {

    $TelegramDb = new TelegramDb();
    $user = $TelegramDb->getUserByTelegramId($_POST['telegram_id']);

    if ($user) {
        $json = json_encode([
            'message' => [
                'chat' => [
                    'id' => $_POST['telegram_id'],
                    'first_name' => $user['firstname'],
                    'last_name' => $user['lastname'],
                    'username' => $user['username'],
                ]
            ]
        ]);

        $TelegramContext = new TelegramContext(json_decode($json));

        $Jdf = new Jdf();
        $vpn_server = 'web.uxyz.top';
        $vpn_username = generateRandomString(6);
        $vpn_password = rand(0, 9);
        $register_date = $Jdf->jdate('Y/m/d ساعت H:i:s', strtotime(date('Y-m-d H:i:s')));

        $price = $TelegramDb->getOrginalPricePackage($user['user_reference'], $_POST['package_id']);
        $TelegramDb->addCustomer($user['id'], $_POST['package_id'], $vpn_server, $vpn_username, $vpn_password, $register_date, $price);

        $title = $TelegramDb->getFullTitlePackage($user['user_reference'], $_POST['package_id']);

        $TelegramContext->send_customer_informations($title, $vpn_server, $vpn_username, $vpn_password, $register_date, "-", false);

        die('<h2>با تشکر از شما</h2><script>window.location.href = "' . ConfigContext::get('bot_link') . '";</script>');
    }
}

function generateRandomString($length = 10)
{
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $randomString = '';

    for ($i = 0; $i < $length; $i++)
        $randomString .= $characters[rand(0, strlen($characters) - 1)];

    return $randomString;
}
?>
<form action="./callback.php" method="POST">
    <input type="hidden" name="telegram_id" value="<?= $_GET['tid'] ?>" />
    <input type="hidden" name="package_id" value="<?= $_GET['pid'] ?>" />
    <button>پرداخت</button>
</form>