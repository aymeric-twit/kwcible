<?php

// KWCible module boot: load Composer autoloader (php-stemmer)
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Propagation clé API Sistrix
if (!empty($_ENV['SISTRIX_API_KEY'])) {
    putenv("SISTRIX_API_KEY={$_ENV['SISTRIX_API_KEY']}");
}
