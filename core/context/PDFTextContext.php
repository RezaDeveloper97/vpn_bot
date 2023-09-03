<?php
class PDFTextContext
{
    use getIniFile;
    public static function get(string $name): string
    {
        return self::getIniFile($name, 'PDF');
    }
}