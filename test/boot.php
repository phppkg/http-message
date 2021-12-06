<?php
/**
 * phpunit --bootstrap test/boot.php test
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$inhereDir = dirname(__DIR__, 2);
$map = [
    'PhpPkg\Http\MessageTest\\' => __DIR__,
    'PhpPkg\Http\Message\\' => dirname(__DIR__) . '/src',
];

spl_autoload_register(function ($class) use ($map) {
    foreach ($map as $np => $dir) {
        if (0 === strpos($class, $np)) {
            $path = str_replace('\\', '/', substr($class, strlen($np)));
            $file = $dir . "/{$path}.php";

            if (is_file($file)) {
                my_include_file($file);
            }
        }
    }
});

if (file_exists($file = dirname(__DIR__, 3) . '/autoload.php')) {
    require $file;
} elseif (file_exists($file = dirname(__DIR__) . '/vendor/autoload.php')) {
    require $file;
}

function my_include_file($file) {
    include $file;
}
