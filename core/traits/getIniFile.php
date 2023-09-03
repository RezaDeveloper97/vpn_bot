<?php
trait getIniFile
{
    public static function getIniFile(string $name, string $section, string $filename = 'config.ini'): string
    {
        $file = parse_ini_file($filename, true);
        return $file[$section][$name];
    }
}