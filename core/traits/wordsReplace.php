<?php
trait wordsReplace
{
    public static function wordsReplace($str, $replacements)
    {
        $search = '$';
        foreach ($replacements as $replacement) {
            $str = preg_replace('/' . preg_quote($search, '/') . '/', $replacement, $str, 1);
        }
        return $str;
    }
}