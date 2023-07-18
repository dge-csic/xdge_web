<?php // encoding="UTF-8"

include_once(__DIR__ . '/vendor/autoload.php');


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
    // static $el_grc_tr;
    /** a transliterration table to convert ancient accentued greek in modern  */
    static $grc_el_tr;

    /** constructor */
    static public function init()
    {
        mb_internal_encoding("UTF-8");
        $config_file = __DIR__ . "/config.php";
        if (!file_exists($config_file)) {
            throw new Exception("Configuration file not found, expected in:<br/> 
".$config_file);
        }
        self::$p = include($config_file);
        // load transliteration tables
        $dir = __DIR__ . '/json/';
        self::$grc_tr = json_decode(file_get_contents($dir . 'grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_tr = json_decode(file_get_contents($dir . 'lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$grc_lat_tr = json_decode(file_get_contents($dir . 'grc_lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_grc_tr = json_decode(file_get_contents($dir . 'lat_grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$orth_tr =json_decode(file_get_contents($dir . 'orth.json'), true, 512, JSON_THROW_ON_ERROR);
        // self::$el_grc_tr = json_decode(file_get_contents($dir . 'el_grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$grc_el_tr = json_decode(file_get_contents($dir . 'grc_el.json'), true, 512, JSON_THROW_ON_ERROR);

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

    /** Connexion */
    static public function connect($sqlite_file)
    {
        // persistent ? no perf 
        $pdo = new PDO(
            "sqlite:" . $sqlite_file,
            NULL,
            NULL,
            array(PDO::ATTR_PERSISTENT => FALSE),
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        return $pdo;
    }

    /**
     * Normalize a greek form to lower with no accents
     */
    static public function monoton($form)
    {
        $form = Normalizer::normalize($form, Normalizer::FORM_D);
        $form = preg_replace( '@\pM@u', "", $form);
        $form = mb_strtolower($form);
        return $form;
    }

    /** For “Busqueda”, build a SQL WHERE clause from params */
    static public function sqlFrom()
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

    /** Get rowid from inverso */
    static public function inversoRowid($form)
    {
        // convert ancient greek accentued letter to modern
        $form = strtr($form, self::$grc_el_tr);
        // strip punctuation
        $form = strtr($form, self::$orth_tr);
        // latin ? transliterate
        $form = strtr($form, self::$lat_grc_tr);
        $form = self::monoton($form);
        // reverse form before searching
        $form = implode(array_reverse(preg_split('//u', $form, -1, PREG_SPLIT_NO_EMPTY)));
        $q = self::$pdo->prepare("SELECT rowid FROM inverso WHERE inverso >= ?  LIMIT 1");
        $q->execute(array($form));
        if ($rowid = $q->fetchColumn(0)) {
            // no echo before DOCTYPE
            // echo "<!-- inverso exact $form $rowid -->\n";
            return $rowid;
        }
        return -1;
    }

    /** Get rowid of a form */
    static public function rowid($form)
    {
        // convert ancient greek accentued letter to modern
        $form = strtr($form, self::$grc_el_tr);
        // exact id ?
        $q = self::$pdo->prepare('select rowid FROM entry WHERE name = ?');
        $q->execute(array($form));
        if ($rowid = $q->fetchColumn(0)) {
            return $rowid;
        }
        // exact lemma ?
        $q = self::$pdo->prepare('select rowid FROM entry WHERE lemma = ?');
        $q->execute(array($form));
        if ($rowid = $q->fetchColumn(0)) {
            return $rowid;
        }
        // strip punctuation
        $form = strtr($form, self::$orth_tr);
        // latin ? transliterate
        $form = strtr($form, self::$lat_grc_tr);
        $form = self::monoton($form);
        /* select will be ordered by the index (monoton, rowid) 
24143 ἅρμα1
24144 ἅρμα2
24145 Ἅρμα
24146 ἄρμα1
24147 ἄρμα2
24148 Ἄρμα
      */
        $q = self::$pdo->prepare("SELECT rowid, name FROM entry WHERE monoton >= ? LIMIT 1;");
        $q->execute(array($form));
        if ($rowid = $q->fetchColumn(0)) {
            return $rowid;
        }
        return -1;
    }

    /**
     * Get articles
     */
    static public function article($form)
    {
        // convert ancient greek accentued letter to modern
        $form = strtr($form, self::$grc_el_tr);
        // name ?
        if ($res = self::artquery("name = ?", $form)) return $res;
        // lemma ?
        if ($res = self::artquery("lemma = ?", $form)) return $res;
        // without special signs ?
        $form = strtr($form, self::$orth_tr);
        if ($res = self::artquery("form = ?", $form)) return $res;
        // with no diacritics?
        $form = self::monoton($form);
        if ($res = self::artquery("monoton = ?", $form)) return $res;
        // latin?
        if ($res = self::artquery("latin = ?", $form)) return $res;
        return null;
    }
    /**
     * display article 
     */
    static public function artquery($where, $form)
    {
        $query = self::$pdo->prepare('SELECT count(*) FROM entry WHERE ' . $where);
        $query->execute(array($form));
        $count = $query->fetchColumn(0);
        if ($count < 1) {
            return false;
        }
        $query = self::$pdo->prepare('SELECT html, toc, prevnext, name, label FROM entry WHERE ' . $where);
        $query->execute(array($form));
        $res = [];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $res[] = $row;
        }
        return $res;
    }
}
