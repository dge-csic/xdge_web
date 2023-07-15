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
    /** articles */
    public static $res;
    /** init static paras */
    public static function init() {
        self::$form = Http::par('form');
        self::$res = Xdge::article(self::$form);
        // if no article found -> 404
        if (!self::$res) return false;
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
    if (!is_array(Article::$res) || !count(Article::$res)) {
        // not found ?
        return false;
    }
    foreach(Article::$res as $row) {
        echo "<div class=\"entrywrap\">\n";
        echo $row['html'];
        echo "  <nav class=\"toc\">\n";
        echo $row['toc'];
        echo "  </nav>\n";
        echo "</div>\n";
    }
};

// for debug, direct call
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__)) {
    echo $main();
}
