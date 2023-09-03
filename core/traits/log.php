<?php
trait log
{
    public static function log($inp, $file = 'log.html', $file_type = 0)
    {
        ob_start();
        var_dump($inp);
        $result = ob_get_clean();
        file_put_contents($file, "<meta charset='utf-8'><pre>$result</pre>", $file_type);
    }
}