<?php
final class TelegramDb extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkUser($telegram_id)
    {
        return $this->fetch("SELECT * FROM users WHERE telegram_id = ?", [$telegram_id]);
    }

    public function insertUser($telegram_id, $firstname, $lastname, $username, $user_reference)
    {
        $this->query("INSERT INTO `users`(`telegram_id`, `firstname`, `lastname`, `username`, `user_reference`) VALUES (?,?,?,?,?)", [$telegram_id, $firstname, $lastname, $username, $user_reference]);
        return $this->lastInsertId();
    }

    public function getUserByTelegramId($telegram_id)
    {
        $user = $this->fetch("SELECT * FROM `users` WHERE `telegram_id` = ?", [$telegram_id]);
        return $user;
    }

    public function getUserId($telegram_id)
    {
        $user = $this->getUserByTelegramId($telegram_id);
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

    public function getAllPackages($user_reference)
    {
        return $this->fetchAll("SELECT t.id, t.title, t.parent_id, IF(tt.price > 0, tt.price, t.price) as `price` FROM `packages` t LEFT JOIN `user_packages` tt ON tt.package_id = t.id AND tt.user_id = ?", [$user_reference]);
    }

    public function getAllUserPackages($user_id)
    {
        return $this->fetchAll("SELECT * FROM `user_packages` WHERE `user_id` = ?", [$user_id]);
    }

    public function getPackagesByParentId($user_reference, $parent_id)
    {
        return $this->fetchAll("SELECT t.id, t.title, t.parent_id, IF(tt.price > 0, tt.price, t.price) as `price` FROM `packages` t LEFT JOIN `user_packages` tt ON tt.package_id = t.id AND tt.user_id = ? WHERE t.`parent_id` = ?", [$user_reference, $parent_id]);
    }

    public function getPackageByParentIdAndTitle($user_reference, $parent_id, $title)
    {
        return $this->fetch("SELECT t.id, t.title, t.parent_id, IF(tt.price > 0, tt.price, t.price) as `price` FROM `packages` t LEFT JOIN `user_packages` tt ON tt.package_id = t.id AND tt.user_id = ? WHERE t.`parent_id` = ? AND t.`title` = ?", [$user_reference, $parent_id, $title]);
    }

    public function getTitlePackage($user_reference, $id)
    {
        $result = [];
        $this_package = $this->fetch("SELECT t.id, t.title, t.parent_id, IF(tt.price > 0, tt.price, t.price) as `price` FROM `packages` t LEFT JOIN `user_packages` tt ON tt.package_id = t.id AND tt.user_id = ? WHERE t.`id` = ?", [$user_reference, $id]);
        $result[] = $this_package['title'];

        if ($this_package['parent_id'] != 0) {
            $parentTitles = $this->getTitlePackage($user_reference, $this_package['parent_id']);
            $result = array_merge($result, $parentTitles);
        }

        return $result;
    }

    public function getFullTitlePackage($user_reference, $id)
    {
        $titles = $this->getTitlePackage($user_reference, $id);
        return join(' ', array_reverse($titles));
    }

    public function getOrginalPricePackage($user_reference, $package_id)
    {
        $package = $this->fetch("SELECT IF(tt.price > 0, tt.price, t.price) as `price` FROM `packages` t LEFT JOIN `user_packages` tt ON tt.package_id = t.id AND tt.user_id = ? WHERE t.`id` = ?", [$user_reference, $package_id]);
        if ($package) {
            return $package['price'];
        }
        return 0;
    }

    public function getPricePackageByUserId($user_reference, $use_id, $package_id)
    {
        $user_package = $this->fetch("SELECT `price` FROM `user_packages` WHERE `user_id` = ? AND `package_id` = ?", [$use_id, $package_id]);
        if ($user_package)
            return $user_package['price'];

        return $this->getOrginalPricePackage($user_reference, $package_id);
    }

    public function addUserPackage($user_id, $package_id, $price)
    {
        return $this->query("REPLACE INTO `user_packages`(`user_id`, `package_id`, `price`) VALUES (?,?,?)", [$user_id, $package_id, $price]);
    }

    public function addUserPackageByPercentage($user_id, $percentage, $user_reference, $package_id)
    {
        return $this->query("REPLACE INTO `user_packages`(`user_id`, `package_id`, `price`) 
        SELECT ?, t.id, IF(tt.price > 0, tt.price * 1.$percentage, t.price * 1.$percentage) as `price` 
        FROM `packages` t 
        LEFT JOIN `user_packages` tt ON tt.package_id = t.id AND tt.user_id = ? 
        WHERE t.price > 0 AND t.`id` != ?;
        ", [$user_id, $user_reference, $package_id]);
    }
}