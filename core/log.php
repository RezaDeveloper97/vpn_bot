<?php
trait log
{
    public function log($inp, $file = 'log.html', $file_type = 0)
    {
        ob_start();
        var_dump($inp);
        $result = ob_get_clean();
        file_put_contents($file, "<pre>$result</pre>", $file_type);
    }
}