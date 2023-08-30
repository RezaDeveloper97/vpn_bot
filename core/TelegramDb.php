<?php

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
        // $this->log("INSERT INTO `users`(`telegram_id`, `firstname`, `lastname`, `username`) VALUES ('$telegram_id', '$firstname', '$lastname', '$username')");
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