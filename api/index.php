<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($uri, '/');
if ($path === '' || $path === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}
$target = __DIR__ . '/../' . $path;
if (is_file($target)) {
    if (substr($target, -4) === '.php') {
        require $target;
        exit;
    }
    return false;
}
$clean = strtok($path, '?');
if (is_file(__DIR__ . '/../' . $clean)) {
    require __DIR__ . '/../' . $clean;
    exit;
}
require __DIR__ . '/../index.php';
