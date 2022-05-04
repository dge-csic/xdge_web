<?php // encoding="UTF-8"

include_once(__DIR__ . '/php/autoload.php');

use Oeuvres\Kit\{Web};

Xdge::init();
class Xdge
{
    /** pars */
    static $p;
    /** SQL connexion */
    static $pdo;
    /** Request type for searching */
    static $qtype;
    /** active messages (defines by __construct) */
    static $say;
    /** spanish messages (to translate from french) */
    static $es;
    /** french messages */
    static $fr = array(
        'notfound' => 'Aucun mot n’a été trouvé pour les caractères “%s”.',
    );
    /** a translitteration table for latin chars */
    static $lat_tr;
    /** a translitteration table for greek chars */
    static $grc_tr;
    /** a translitteration table for betacode */
    static $lat_grc_tr;
    /** a translitteration table from simple greek to latin chars */
    static $grc_lat_tr;
    /** a translitteration table to clean punctuation */
    static $orth_tr;
    /** a transliterration table to convert modern accentued greek in ancient  */
    static $el_grc_tr;

    /** constructor */
    static function init()
    {
        $pars_file = __DIR__ . "/pars.php";
        if (!file_exists($pars_file)) {
            throw new Exception("Parameters file not found, expected in:<br/> 
".$pars_file);
        }
        self::$p = include($pars_file);
        // load transliteration tables
        $dir = __DIR__ . '/json/';
        self::$grc_tr = json_decode(file_get_contents($dir . 'grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_tr = json_decode(file_get_contents($dir . 'lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$grc_lat_tr = json_decode(file_get_contents($dir . 'grc_lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_grc_tr = json_decode(file_get_contents($dir . 'lat_grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$orth_tr =json_decode(file_get_contents($dir . 'orth.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$el_grc_tr = json_decode(file_get_contents($dir . 'el_grc.json'), true, 512, JSON_THROW_ON_ERROR);

        if (!isset(self::$p['xdge_db']) ) {
            throw new Exception("Installation problem.<br/>
Set the property for your xdge database<br/>
ex: 'xdge_db' =>  => __DIR__ . '/xdge.db',<br/>
in your parameter file<br/>
" . $pars_file );
        }
        if (!file_exists(self::$p['xdge_db'])) {
            throw new Exception("Installation problem.<br/>
Xdge database not found. Check property<br/>
'xdge_db' =>  => " . self::$p['xdge_db'] .",<br/>
in your parameter file<br/>
" . $pars_file );
        }
        self::$pdo = self::connect(self::$p['xdge_db']);
    }
    /** Conexion */
    static function connect($sqlite_file)
    {
        // persistent
        $pdo = new PDO(
            "sqlite:" . $sqlite_file,
            NULL,
            NULL,
            array(PDO::ATTR_PERSISTENT => TRUE),
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        return $pdo;
    }

    /** For “Busqueda”, build a SQL WHERE clause from params */
    static function sqlFrom()
    {
        $sql = " FROM search WHERE text MATCH ? ";
        $i = 0;
        if (count(self::$qtype) == 0); // no type requested
        else { // add where close
            $sql .= " AND ( ";
            foreach (self::$qtype as $k => $v) {
                if ($i) $sql .= " OR ";
                $sql .= " type = '$k' ";
                $i++;
            }
            $sql .= ")";
        }
        return $sql;
    }
    /** Get rowid of a form */
    function rowid($form)
    {
        // convert modern greek accentued letter to old greek
        $form = strtr($form, self::$el_grc_tr);
        $q = self::$pdo->prepare('select rowid FROM entry WHERE lemma = ?');
        $q->execute(array($form));
        if ($rowid = $q->fetchColumn(0)) {
            // no echo before DOCTYPE
            echo "<!-- exact $form $rowid -->\n";
            return $rowid;
        }

        // strip punctuation
        $form = strtr($form, self::$orth_tr);
        if (($monoton = strtr($form, self::$lat_grc_tr)) != $form) {
            // no echo before DOCTYPE
            echo "<!-- latin  $form > $monoton -->\n";
            $q = self::$pdo->prepare("SELECT rowid FROM entry WHERE latin >= ? LIMIT 10");
        } else {
            $form = strtr($form, self::$grc_tr);
            // no echo before DOCTYPE
            echo "<!-- monoton $form -->\n";
            $q = self::$pdo->prepare("SELECT rowid FROM entry WHERE monoton >= ? LIMIT 6;");
            /* Bug Arma
"24147","αρμα","Ἅρμα"
"24150","αρμα","Ἄρμα"
"24145","αρμα1","1 ἅρμα"
"24148","αρμα1","1 ἄρμα"
"24146","αρμα2","2 ἅρμα"
"24149","αρμα2","2 ἄρμα"
      */
        }
        $q->execute(array($form));
        $rowid = false;
        // much more efficient that an ORDER BY rowid
        while ($value = $q->fetchColumn(0)) {
            if (!$rowid || $value < $rowid) $rowid = $value;
        }
        return $rowid;
    }

    function inversoRowid($form)
    {
        // convert modern greek accentued letter to old greek
        $form = strtr($form, self::$el_grc_tr);
        // monoton
        $form = trim(strtr(strtr($form, self::$orth_tr), self::$grc_tr));
        // reverse form before searching
        $form = implode(array_reverse(preg_split('//u', $form, -1, PREG_SPLIT_NO_EMPTY)));
        $q = self::$pdo->prepare("SELECT rowid FROM inverso WHERE inverso >= ?  LIMIT 1");
        $q->execute(array($form));
        if ($rowid = $q->fetchColumn(0)) {
            // no echo before DOCTYPE
            // echo "<!-- inverso exact $form $rowid -->\n";
            return $rowid;
        }
        return false;
    }
    /**
     * Get an html article to display
     */
    static function article($form)
    {
        // convert modern Greek diacritics to old greek
        $form = strtr($form, self::$el_grc_tr);
        // id ?
        if ($html = self::artquery("id = ?", $form)) return $html;
        // lemma ?
        if ($html = self::artquery("lemma = ?", $form)) return $html;
        // without special signs ?
        $form = strtr($form, self::$orth_tr);
        if ($html = self::artquery("form = ?", $form)) return $html;
        // with no diacritics ?
        $form = strtr($form, self::$grc_tr);
        if ($html = self::artquery("monoton = ?", $form)) return $html;
        // latin ?
        if ($html = self::artquery("latin = ?", $form)) return $html;
        return "<h1>$form?</h1>";
    }
    /**
     * display article 
     */
    static function artquery($where, $form)
    {
        $query = self::$pdo->prepare('SELECT count(*) FROM entry WHERE ' . $where);
        $query->execute(array($form));
        $count = $query->fetchColumn(0);
        if ($count >= 1) {
            $query = self::$pdo->prepare('SELECT html, id, label FROM entry WHERE ' . $where);
            $query->execute(array($form));
            $html = "";
            $menu = "";
            $count = 0;
            while ($row = $query->fetch()) {
                $count++;
                if ($count > 1) {
                    $menu .= ', ';
                    $html .= "\n\n<hr class=\"entry\"/>\n\n";
                }
                $menu .= '<a href="#' . $row[1] . '">' . $row[2] . '</a>';
                $html .= '<a name="' . $row[1] . '"></a>' . $row[0] . "\n\n";
            }
            if ($count > 1) $menu = '<p class="menu">' . $menu . ".</p>\n\n";
            else $menu = "";
            return "!-- $where $form : $count -->\n" . $menu . $html;
        }
    }
}
