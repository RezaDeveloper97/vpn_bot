<?php
class MentTextContext
{
    use getIniFile;
    public static function get(string $name): string
    {
        return self::getIniFile($name, 'menu');
    }
}