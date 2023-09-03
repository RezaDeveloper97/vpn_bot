<?php
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