<?php
spl_autoload_register(function ($className) {
    $classPath = __DIR__ . '/vendor/' . $className . '.php';
    if (file_exists($classPath))
        require_once $classPath;
});