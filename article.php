<?php declare(strict_types=1);

include_once(__DIR__ . '/Xdge.php');

use Oeuvres\Kit\{Http};

/**
 * Test if form requested is available, 
 * populate object of data 
 */
class Article {
    /** requested form */
    public static $form;
    /** article id recalled */
    public static $lemma;
    /** article html */
    public static $html;
    /** init static paras */
    public static function init() {
        self::$form = Http::par('form');
        self::$html = Xdge::article(self::$form);
    }
}
Article::init();

$title = function() {
    // return $lenma . ", " . 'DGE (Diccionario Griego-Español)'; 
};

$main = function() {
    // return $lenma . ", " . 'DGE (Diccionario Griego-Español)';
    /* hilite ? 
    if (isset($_REQUEST['mark'])) {
        $hi = new Hilite($_REQUEST['mark']);
        $html = $hi->hi($html);
    }
    */
    return Article::$html;
};

// for debug, direct call
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__)) {
    echo $main();
}
