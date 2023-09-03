<?php
class TextContext
{
    use getIniFile;
    use wordsReplace;

    public static function get(string $name, $replacements = []): string
    {
        $text = self::getIniFile($name, 'text');
        return str_replace('\n', "\n", wordsReplace::wordsReplace($text, $replacements));
    }
}