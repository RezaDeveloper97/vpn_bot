<?php
trait numberConvertor
{
    public static $persianToEnglishMap = [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9'
    ];

    public static function persianNumberToEnglish($text)
    {
        return str_replace(array_keys(numberConvertor::$persianToEnglishMap), array_values(numberConvertor::$persianToEnglishMap), $text);
    }

    public static function englishNumberTopersian($text)
    {
        return str_replace(array_values(numberConvertor::$persianToEnglishMap), array_keys(numberConvertor::$persianToEnglishMap), $text);
    }
}