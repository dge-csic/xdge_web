<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

/** Routage */

use Psr\Log\{LogLevel};
use Oeuvres\Kit\{Route, I18n, Http, Log, LoggerWeb};

// no template
Route::get(
    '/article/(.*)',
    __DIR__ . '/article.php',
    array(
        'form' => '$1',
    )
);

// register the default template in which include content
Route::template(__DIR__ . '/template.php');
// welcome page or error page
Route::get('/', __DIR__ . '/pages/presentacion.html');
// try if a local html page is available
Route::get('/(.*)', __DIR__ . '/pages/$1.html');

// try to have an article
Route::get(
    '/(.*)',
    __DIR__ . '/article.php',
    array(
        'form' => '$1',
    )
);

// No Route has worked
echo "Bad routage, 404.";

