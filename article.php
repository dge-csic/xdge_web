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
    /** articles */
    public static $res;
    /** HTML title */
    public static $title = '';
    /** init static paras */
    public static function init() {
        self::$form = Http::par('form');
        self::$res = Xdge::article(self::$form);
        // if no article found -> 404
        if (!self::$res) return false;
        foreach(Article::$res as $row) {
            self::$title .= $row['lemma'] . ', ';
        }
        self::$title .= 'DGE (Diccionario Griego-Español)'; 
    }
}
Article::init();

$title = function() {
    return Article::$title;
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
    $stmt = null;
    $q = Http::par('q', null);
    if ($q) {
        $monoton = Xdge::monoton($q);
        $sql = "SELECT rowid, *, highlight(entry_search, 0, '<mark>', '</mark>') as hi FROM entry_search WHERE rowid = ? AND text MATCH ? LIMIT 1;";
        $stmt = Xdge::$pdo->prepare($sql);
    }
    foreach(Article::$res as $row) {
        echo "<div class=\"entrywrap\">\n";
        // a search
        $reshi = null;
        if ($stmt) {
            $stmt->execute([$row['rowid'], $monoton]);
            $reshi = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if ($reshi) {
            $row['html'] = Xdge::hilite($row['html'], $reshi['hi']);
        }
        echo $row['html'];
        echo "  <nav class=\"toc\">\n";
        echo $row['toc'];
        echo "  </nav>\n";
        echo "</div>\n";
    }
    // <script> not interpreted with innerHTML, a simple hack
    echo "<style onload=\"document.title = '" . Article::$title . "'\"></style>\n";
};

// for debug, direct call
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__)) {
    echo $main();
}
