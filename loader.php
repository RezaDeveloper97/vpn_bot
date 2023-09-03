<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", TRUE);
ini_set('error_log', "./my-errors.log");

spl_autoload_register(function ($className) {
    $paths = [
        '/core/',
        '/core/traits/',
        '/core/context/',
    ];

    foreach ($paths as $path) {
        $classPath = __DIR__ . $path . $className . '.php';
        if (file_exists($classPath))
            return require_once $classPath;
    }

    $classMap = [
        'TCPDF' => '/core/vendor/tcpdf/tcpdf.php',
    ];

    $classPath = __DIR__ . $classMap[$className];
    if (file_exists($classPath))
        return require_once $classPath;
});