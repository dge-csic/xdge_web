<?php // encoding="UTF-8"
declare(strict_types=1);

include_once(__DIR__ . '/../vendor/autoload.php');

use \Oeuvres\Kit\{Xt};


class XdgeBuild
{
    /** SQL connexion */
    static $pdo;
    /** Count */
    static $idEntry = 1;
    /** SQL prepared queries */
    static $qEntry;
    static $qEntrySearch;
    static $qBibl;
    static $qSearch;
    /** a translitteration table for latin chars */
    static $lat_tr;
    /** a translitteration table for very special greek chars */
    static $grcfix_tr;
    /** a translitteration table for betacode */
    static $lat_grc_tr;
    /** a translitteration table from simple greek to latin chars */
    static $grc_lat_tr;
    /** a translitteration table to clean punctuation */
    static $orth_tr;
    /** a transliterration table to convert modern accentued greek in ancient  */
    static $el_grc_tr;

    static function init()
    {
        // load transliteration tables
        $dir = dirname(__DIR__) . '/json/';
        self::$grcfix_tr = json_decode(file_get_contents($dir . 'grcfix.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_tr = json_decode(file_get_contents($dir . 'lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$grc_lat_tr = json_decode(file_get_contents($dir . 'grc_lat.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$lat_grc_tr = json_decode(file_get_contents($dir . 'lat_grc.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$orth_tr = json_decode(file_get_contents($dir . 'orth.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$el_grc_tr = json_decode(file_get_contents($dir . 'el_grc.json'), true, 512, JSON_THROW_ON_ERROR);

    }

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
        self::$qEntry = self::$pdo->prepare("
        INSERT INTO entry
        (
            filename,
            name,
            lemma,
            label,
            html,

            toc,
            prevnext,
            form,
            monoton,
            latin,

            inverso
        )
        VALUES (?,?,?,?,?,  ?,?,?,?,?, ?);");
        self::$qEntrySearch = self::$pdo->prepare("
        INSERT INTO entry_search(rowid, text) VALUES (?, ?);
        ");

        self::$qBibl = self::$pdo->prepare("
        INSERT INTO bibl
        (
            name, 
            label, 
            author, 
            title, 
            scope, 
            
            entryname,
            entrylabel
        )
        VALUES (?,?,?,?,?,  ?,?);");

        self::$qSearch = self::$pdo->prepare("
        INSERT INTO search
        (
            type, 
            name, 
            text, 
            html, 
            branch, 
            
            context, 
            entryName,
            entrylabel
        )
        VALUES (?,?,?,?,?,  ?,?,?);");

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
        self::$pdo->exec("INSERT INTO inverso (name, label, inverso) SELECT name, label, inverso FROM entry ORDER BY inverso, rowid;");
        // update bibl with entry.rowid
        self::$pdo->exec("UPDATE bibl SET entry = (SELECT rowid FROM entry WHERE entry.name = bibl.entryname)");
        // optimize
        self::$pdo->exec("INSERT INTO  search(search) VALUES ('optimize'); -- optimize fulltext index");
    }

    /**
     * Normalize a greek form to lower with no accents
     */
    static public function monoton($form)
    {
        // for greek chars unknown from Normalizer
        $form = strtr($form, self::$grcfix_tr);
        $form = Normalizer::normalize($form, Normalizer::FORM_D);
        $form = preg_replace('@\p{Mn}@u', "", $form);
        $form = mb_strtolower($form);
        return $form;
    }

    /** Insert an entry, method is called by xsl transformation */
    static function entry($filename, $name, $lemma, $label, $html, $toc, $prevnext, $txt)
    {
        $txt = self::xml($txt, true);
        // NO modern-old greek translit, XML should be good
        $lemma = trim($lemma);
        $form = strtr($lemma, self::$orth_tr);
        // normalize lemma for access, punctuation and diacritics
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
        $toc = self::xml($toc);
        $prevnext = self::xml($prevnext);
        self::$qEntry->execute(array(
            $filename,
            $name,
            $lemma,
            self::xml($label, true),
            $html,
            $toc,
            $prevnext,
            $form,
            $monoton,
            $latin,
            $rev,
        ));
        $rowid = self::$pdo->lastInsertId();
        $text = Xt::detag($html);
        $text = self::monoton($text);
        self::$qEntrySearch->execute([$rowid, $text]);
    }

    /**
     * Insert a <bibl> from xslt
     */
    static function bibl(
        $name, 
        $label, 
        $author, 
        $title, 
        $scope, 

        $entryname, 
        $entrylabel
    ) {
        self::$qBibl->execute(array(
            $name,
            self::xml($label, true),
            $author,
            $title,
            $scope,
            $entryname,
            self::xml($entrylabel, true),
        ));

    }

    /**
     * From xslt, insert a searchable object
     */
    static function search(
        $type,
        $name, 
        $html, 
        $branch, 
        $context,
        $entryname,
        $entrylabel
    ) {
        $html = self::xml($html);
        $text = Xt::detag($html);
        $text = self::monoton($text);
        self::$qSearch->execute(array(
            $type,
            $name,
            $text,
            $html,
            self::xml($branch),
            self::xml($context),
            $entryname,
            self::xml($entrylabel, true),
        ));
    }

    /**
     * get XML from a dom sent by xsl
     */
    static function xml($nodeset, $inner = false)
    {
        $xml = '';
        if (!is_array($nodeset)) $nodeset = array($nodeset);
        foreach ($nodeset as $doc) {
            if($doc->firstChild === null) {
                continue;
            }
            if (get_class($doc->firstChild) === 'DOMText') {
                $xml .= $doc->textContent;
                continue;
            }
            // if (!$doc->textContent) // not seen after upper
            $doc->formatOutput = true;
            $doc->substituteEntities = true;
            $doc->encoding = "UTF-8";
            $doc->normalize();
            $xml .= $doc->saveXML($doc->documentElement);
        }
        if (!$xml) return null;
        // del root ns
        $xml = preg_replace('@ xmlns="http://www.w3.org/1999/xhtml"@', '', $xml);
        // cut the root element
        if ($inner) {
            $xml = substr($xml, strpos($xml, '>') + 1);
            $xml = substr($xml, 0, strrpos($xml, '<'));
        }
        return $xml;
    }
}
XdgeBuild::init();
/**
 * Global pilot of xdge app
 */
XdgeBuild::build(
    dirname(__DIR__) . '/xdge.db',
    dirname(dirname(__DIR__)) . '/xdge_xml/xdge*.xml'
);
