<?php // encoding="UTF-8"
header('Content-Type: text/html; charset=utf-8');
/*
Global pilot of xdge app
*/
xdge::$libDir=dirname(dirname(__FILE__)).'/lib/';
include_once xdge::$libDir."php/Web.php";
xdge::$libHref=Web::pathBase().'../lib/';

include_once xdge::$libDir."php/Hilite.php";
include_once xdge::$libDir."php/I18n.php";
new xdge();
class xdge {
  /** path to lib dir */
  static $libDir;
  /** Relative link to lib dir for web */
  static $libHref;
  /** */
  static $glob="xml/xdge?.xml";
  /** Request type for searching */
  static $qtype;
  /** active messages (defines by __construct) */
  static $say;
  /** spanish messages (to translate from french) */
  static $es;
  /** french messages */
  static $fr=array(
    'notfound'=>'Aucun mot n’a été trouvé pour les caractères “%s”.',
  );
  /** Count */
  static $idEntry=1;
  /** SQL prepared queries */
  static $insEntry;
  static $insSearch;
  /** SQL connexion */
  static $pdo;
  /** SQLite file */
  static $sqlite;
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
  function __construct() {
    // load transliteration tables
    self::$grc_tr=I18n::json(dirname(dirname(__FILE__)).'/lib/json/grc.json');
    self::$lat_tr=I18n::json(dirname(dirname(__FILE__)).'/lib/json/lat.json');
    self::$grc_lat_tr=I18n::json(dirname(dirname(__FILE__)).'/lib/json/grc_lat.json');
    self::$lat_grc_tr=I18n::json(dirname(dirname(__FILE__)).'/lib/json/lat_grc.json');
    self::$orth_tr=I18n::json(dirname(dirname(__FILE__)).'/lib/json/orth.json');
    self::$el_grc_tr=I18n::json(dirname(dirname(__FILE__)).'/lib/json/el_grc.json');
    self::$sqlite=dirname(__FILE__).'/var/xdge.sqlite';
    self::connect();
  }
  /** Conexion */
  static function connect() {
    if (!file_exists($dir=dirname(self::$sqlite))) {
      mkdir($dir, 0775, true);
      @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
    }
    self::$pdo=new PDO("sqlite:".self::$sqlite);
    self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
  }
  /** Create table */
  static function create() {
    // close connection before delete base
    self::$pdo=null;
    set_time_limit (3600);
    // TODO, prepare a new version in another tmp file
    if (!file_exists(dirname(self::$sqlite))) mkdir(dirname(self::$sqlite), 0777, true);
    if (!is_writable(dirname(self::$sqlite))) exit("Misconf, unwritable folder : ".self::$sqlite);
    // delete base if exists
    if (file_exists(self::$sqlite)) unlink(self::$sqlite);
    self::connect();
    chmod(self::$sqlite, 0775);
    self::$pdo->exec("
CREATE TABLE entry (
  id      TEXT, -- entry/@xml:id
  lemma   TEXT, -- *Ἀhεριγος
  label   TEXT, -- *Ἀhεριγ<sup>u̯</sup>ος
  html    TEXT, -- displayable entry
  form    TEXT, -- lemma without ponctuation
  monoton TEXT, -- lemma without diacritics
  latin   TEXT, -- latin script version of the lemma
  inverso TEXT  -- reverse form
);
CREATE VIRTUAL TABLE search USING FTS3 (
  -- table of searchable items
  entry     INT,  -- entry rowid
  id        TEXT, -- entry/@xml:id
  lemma     TEXT, -- *Ἀhεριγος
  label     TEXT, -- *Ἀhεριγ<sup>u̯</sup>ος
  anchor    TEXT, -- relative anchor in entry
  type      TEXT, -- content type
  text      TEXT, -- exact text
  monoton   TEXT, -- desaccentuated text
);
");
    // insert statements
    self::$insEntry=self::$pdo->prepare("INSERT INTO entry   VALUES (?,?,?,?,?,?,?,?);");
    self::$insSearch=self::$pdo->prepare("INSERT INTO search VALUES (?,?,?,?,?,?,?,?);");

    $proc = new XSLTProcessor();
    $proc->registerPHPFunctions();
    $dom=new DOMDocument();
    $dom->load(dirname(__FILE__) .'/transform/xdge_sql.xsl');
    // $cwd=getcwd();
    // chdir(dirname(__FILE__).'/transform/');
    $proc->importStyleSheet($dom);
    // chdir($cwd);
    // loop on xge xml files
    foreach(glob(self::$glob) as $file) {
      echo $file,"\n";
      // start transaction
      self::$pdo->beginTransaction();
      $dom->load($file);
      $proc->transformToXML($dom);
      // commit all pending insert
      self::$pdo->commit();
    }

    // créer l'index
    self::$pdo->exec("
CREATE TABLE inverso (
-- table to suggest lemma from a reverse query, use auto rowid
  inverso, -- reverse form
  id,      -- entry/@xml:id
  label    -- *Ἀhεριγ<sup>u̯</sup>ος
);
INSERT INTO inverso SELECT inverso, id, label FROM entry ORDER BY inverso;
CREATE INDEX entryLemma     ON entry (lemma ASC);
CREATE INDEX entryId        ON entry (id ASC);
CREATE INDEX entryForm      ON entry (form ASC);
CREATE INDEX entryMonoton   ON entry (monoton ASC);
CREATE INDEX entryLatin     ON entry (latin DESC);
CREATE INDEX entryInverso   ON entry (inverso ASC);
CREATE INDEX inversoInverso ON inverso (inverso ASC);
CREATE INDEX inversoId      ON inverso (id ASC);
INSERT INTO  search(search) VALUES('optimize'); -- optimize fulltext index

    ");
    exit;
  }
  /** Insert an entry, method is called by xsl transformation */
  static function entry($id, $lemma, $label, $html, $text) {
    $text=self::xml($text, true);
    // precaution, convert modern greek accentued letter to old greek
    $lemma=strtr(trim($lemma), xdge::$el_grc_tr);
    $form=strtr($lemma, self::$orth_tr);
    // normalize lemma for access, punctuation and diacritics
    $monoton=strtr($form, self::$grc_tr);
    $latin=strtr($monoton, self::$grc_lat_tr);
    // strrev() or str_split() are not UTF-8 OK
    $rev=implode(array_reverse(preg_split('//u', $monoton, -1, PREG_SPLIT_NO_EMPTY)));
    // put back an homograph number for monoton ?
    /*
    preg_match('/\s*([0-9]+)/', $lemma, $matches);
    if (isset($matches[1])) $monoton.=$matches[1];
    */
    self::$insEntry->execute(array(
      $id,
      $lemma,
      self::xml($label, true),
      self::xml($html),
      $form,
      $monoton,
      $latin,
      $rev,
    ));
    $rowid=self::$pdo->lastInsertId();
    //
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

  }
  /**
   * get XML from a dom sent by xsl
   */
  static function xml($nodeset, $inner=false) {
    $xml='';
    if (!is_array($nodeset)) $nodeset=array($nodeset);
    foreach($nodeset as $doc) {
      $doc->formatOutput=true;
      $doc->substituteEntities=true;
      $doc->encoding="UTF-8";
      $doc->normalize();
      $xml.=$doc->saveXML($doc->documentElement);
    }
    // cut the root element
    if ($inner) {
      $xml=substr($xml,strpos($xml, '>')+1);
      $xml=substr($xml,0,strrpos($xml, '<'));
    }
    return $xml;
  }

  /** For “Busqueda”, build a SQL WHERE clause from params */
  static function sqlFrom() {
    $sql=" FROM search WHERE text MATCH ? ";
    $i=0;
    if (count(self::$qtype) == 0); // no type requested
    else { // add where close
      $sql .=" AND ( ";
      foreach(self::$qtype as $k=>$v) {
        if ($i) $sql.=" OR ";
        $sql.=" type = '$k' ";
        $i++;
      }
      $sql .=")";
    }
    return $sql;
  }
  /** Get rowid of a form */
  function rowid($form) {
    // convert modern greek accentued letter to old greek
    $form=strtr($form, xdge::$el_grc_tr);
    $q = xdge::$pdo->prepare('select rowid FROM entry WHERE lemma = ?');
    $q->execute(array($form));
    if ($rowid=$q->fetchColumn(0)) {
      // no echo before DOCTYPE
      echo "<!-- exact $form $rowid -->\n";
      return $rowid;
    }

    // strip punctuation
    $form=strtr($form, xdge::$orth_tr);
    if (($monoton=strtr($form, xdge::$lat_grc_tr)) != $form) {
      // no echo before DOCTYPE
      echo "<!-- latin  $form > $monoton -->\n";
      $q = xdge::$pdo->prepare("SELECT rowid FROM entry WHERE latin >= ? LIMIT 10");
    }
    else {
      $form=strtr($form, xdge::$grc_tr);
      // no echo before DOCTYPE
      echo "<!-- monoton $form -->\n";
      $q = xdge::$pdo->prepare("SELECT rowid FROM entry WHERE monoton >= ? LIMIT 6;");
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
    $rowid=false;
    // much more efficient that an ORDER BY rowid
    while ($value=$q->fetchColumn(0)) {
      if(!$rowid || $value < $rowid) $rowid=$value;
    }
    return $rowid;
  }
  
  function inversoRowid($form) {
    // convert modern greek accentued letter to old greek
    $form=strtr($form, xdge::$el_grc_tr);
    // monoton
    $form=trim(strtr(strtr($form, xdge::$orth_tr), xdge::$grc_tr));
    // reverse form before searching
    $form=implode(array_reverse(preg_split('//u', $form, -1, PREG_SPLIT_NO_EMPTY)));
    $q = xdge::$pdo->prepare("SELECT rowid FROM inverso WHERE inverso >= ?  LIMIT 1");
    $q->execute(array($form));
    if ($rowid=$q->fetchColumn(0)) {
      // no echo before DOCTYPE
      // echo "<!-- inverso exact $form $rowid -->\n";
      return $rowid;
    }
    return false;
  }
  /**
   * Get an html article to display
   */
  static function article($form) {
    // convert modern Greek diacritics to old greek
    $form=strtr($form, xdge::$el_grc_tr);
    // id ?
    if ($html=self::artquery("id = ?", $form)) return $html;
    // lemma ?
    if ($html=self::artquery("lemma = ?", $form)) return $html;
    // without special signs ?
    $form=strtr($form, xdge::$orth_tr);
    if ($html=self::artquery("form = ?", $form)) return $html;
    // with no diacritics ?
    $form=strtr($form, xdge::$grc_tr);
    if ($html=self::artquery("monoton = ?", $form)) return $html;
    // latin ?
    if ($html=self::artquery("latin = ?", $form)) return $html;
    return "<h1>$form?</h1>";
  }
  /**
   * display article 
   */
  static function artquery($where, $form) {
    $query = xdge::$pdo->prepare('SELECT count(*) FROM entry WHERE '.$where);
    $query->execute(array($form));
    $count=$query->fetchColumn(0);
    if ($count >= 1) {
      $query=xdge::$pdo->prepare('SELECT html, id, label FROM entry WHERE '.$where);
      $query->execute(array($form));
      $html="";
      $menu="";
      $count=0;
      while ($row=$query->fetch()) {
        $count++;
        if($count > 1) {
          $menu.=', ';
          $html.="\n\n<hr class=\"entry\"/>\n\n";
        }
        $menu .='<a href="#'.$row[1].'">'.$row[2].'</a>';
        $html .= '<a name="'.$row[1].'"></a>'.$row[0]."\n\n";
      }
      if ($count > 1) $menu='<p class="menu">'.$menu.".</p>\n\n";
      else $menu="";
      return "!-- $where $form : $count -->\n".$menu.$html;
    }
  }

}

// import, do nothing
if (realpath($_SERVER['SCRIPT_FILENAME']) != realpath(__FILE__));
// command line, create base
else if (php_sapi_name() == "cli") {
  xdge::create();
}



?>
