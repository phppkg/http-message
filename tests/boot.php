<?php
/**
 * phpunit6.phar --bootstrap tests/bootstap.php tests
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(function($class)
{
    $file = null;

    if (0 === strpos($class,'Inhere\Http\Tests\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Http\Tests\\')));
        $file =__DIR__ . "/{$path}.php";

    } elseif (0 === strpos($class,'Inhere\Http\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Http\\')));
        $file = dirname(__DIR__) . "/src/{$path}.php";
    }

    if ($file && is_file($file)) {
        include $file;
    }
});
