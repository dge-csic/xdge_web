<?php // encoding="UTF-8"

/**
 * Global pilot of xdge app
 */
XdgeBuild::build(
    dirname(__DIR__) . '/xdge.db',
    dirname(dirname(__DIR__)) . '/xdge_xml/xdge*.xml'
);

class XdgeBuild
{
    /** SQL connexion */
    static $pdo;
    /** Count */
    static $idEntry = 1;
    /** SQL prepared queries */
    static $insEntry;
    static $insSearch;
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

    /** create new database */
    static function build($sqlite_file, $xml_glob)
    {
        $dir = dirname($sqlite_file);
        if (is_dir($dir));
        else if (!mkdir($dir, 0775, true)) {
            throw new Exception("Directory not created: " . $dir);
        }
        if (file_exists($sqlite_file)) unlink($sqlite_file);
        self::$pdo = self::connect($sqlite_file);
        $sql = file_get_contents(__DIR__ . "/xdge.sql");
        self::$pdo->exec($sql);
        // load transliteration tables
        $dir = dirname(__DIR__) . '/json/';
        self::$grc_tr = json_decode(file_get_contents($dir . 'grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_tr = json_decode(file_get_contents($dir . 'lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$grc_lat_tr = json_decode(file_get_contents($dir . 'grc_lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_grc_tr = json_decode(file_get_contents($dir . 'lat_grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$orth_tr = json_decode(file_get_contents($dir . 'orth.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$el_grc_tr = json_decode(file_get_contents($dir . 'el_grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::load($xml_glob);
    }
    /** Connexion */
    static function connect($file)
    {
        $dsn = "sqlite:" . $file;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        return $pdo;
    }
    /** Create table */
    static function load($glob)
    {
        // insert statements
        self::$insEntry = self::$pdo->prepare("
        INSERT INTO entry
        (
            xmlid,
            lemma,
            label,
            html,
            form,
            monoton,
            latin,
            inverso
        )
        VALUES (?,?,?,?, ?,?,?,?);");
        // self::$insSearch = self::$pdo->prepare("INSERT INTO search VALUES (?,?,?,?,?,?,?,?);");

        $proc = new XSLTProcessor();
        $proc->registerPHPFunctions();
        $dom = new DOMDocument();
        $dom->load(__DIR__ . '/xdge_sql.xsl');
        $proc->importStyleSheet($dom);
        self::$pdo->beginTransaction();
        // loop on xge xml files
        echo "Loop on xdge files: " . $glob . "\n";
        foreach (glob($glob) as $file) {
            echo $file, "\n";
            $dom->load($file);
            $proc->transformToXML($dom);
        }
        self::$pdo->commit();
        // load inverso table
        self::$pdo->exec("INSERT INTO inverso (xmlid, label, inverso) SELECT xmlid, label, inverso FROM entry ORDER BY inverso, rowid;");
        // optimize
        self::$pdo->exec("INSERT INTO  search(search) VALUES ('optimize'); -- optimize fulltext index");
    }

    /**
     * Normalize a greek form to lower with no accents
     */
    static public function monoton($form)
    {
        $form = Normalizer::normalize($form, Normalizer::FORM_D);
        $form = preg_replace('@\pM@u', "", $form);
        $form = mb_strtolower($form);
        return $form;
    }

    /** Insert an entry, method is called by xsl transformation */
    static function entry($xmlid, $lemma, $label, $html, $text)
    {
        $text = self::xml($text, true);
        // precaution, convert modern greek accentued letter to old greek
        $lemma = strtr(trim($lemma), self::$el_grc_tr);
        $form = strtr($lemma, self::$orth_tr);
        // normalize lemma for access, punctuation and diacritics
        // $monoton = strtr($form, self::$grc_tr);
        $monoton = self::monoton($form);
        $latin = strtr($monoton, self::$grc_lat_tr);
        // strrev() or str_split() are not UTF-8 OK
        $rev = implode(array_reverse(preg_split('//u', $monoton, -1, PREG_SPLIT_NO_EMPTY)));
        // put back an homograph number for monoton ?
        /*
    preg_match('/\s*([0-9]+)/', $lemma, $matches);
    if (isset($matches[1])) $monoton.=$matches[1];
    */
        $html = self::xml($html);
        self::$insEntry->execute(array(
            $xmlid,
            $lemma,
            self::xml($label, true),
            $html,
            $form,
            $monoton,
            $latin,
            $rev,
        ));
        // echo $html;
        $rowid = self::$pdo->lastInsertId();
        /*
        self::$insSearch->execute(array(
            $rowid,
            $id,
            $lemma,
            self::xml($label, true),
            "",
            "entry",
            $text,
            trim(strtr($text, self::$grc_tr)),
        ));
        */
    }
    /**
     * get XML from a dom sent by xsl
     */
    static function xml($nodeset, $inner = false)
    {
        $xml = '';
        if (!is_array($nodeset)) $nodeset = array($nodeset);
        foreach ($nodeset as $doc) {
            $doc->formatOutput = true;
            $doc->substituteEntities = true;
            $doc->encoding = "UTF-8";
            $doc->normalize();
            $xml .= $doc->saveXML($doc->documentElement);
        }
        // cut the root element
        if ($inner) {
            $xml = substr($xml, strpos($xml, '>') + 1);
            $xml = substr($xml, 0, strrpos($xml, '<'));
        }
        return $xml;
    }
}
