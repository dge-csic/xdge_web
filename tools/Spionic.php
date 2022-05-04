<?php
/**

TACHE: convertir en Unicode les mots grecs du site du DGE, qui sont pour l'heure dans la police SPIonic, espèce de Betacode.

1) Le "matériel":

	- DGE-samples.zip:
	Il s'agit de faire les remplacements dans tous les fichiers "*.htm" de plusieurs dossiers et sous-dossiers. Le dossier 'DGE-samples.zip' contient un échantillon de ceux-ci.
Toutes les lignes à rechercher et à procéder commencent par <FONT FACE="SPIonic"[^>]*> et se terminent par </FONT>. Et c'est la chaîne contenue dans cette balise <FONT.+></FONT> qu'il faut substituer.

	- equivalences.txt:
	Le fichier 'equivalences.txt' contient la liste des remplacements à effectuer, mis dans un ordre adéquat (je l'espère) pour le traitement. Le séparateur entre la chaîne de départ et son caractère unicode équivalent est une tabulation.

ATTENTION:
Il y a plusieurs combinaisons en SPIonic comportant des caractères qui peuvent être interprétés comme des métacaractères (crochets, parenthèses, pipes, etc.; un exemple: 'a)\'). Il se peut qu'il faille protéger ces caractères.
Les caractères qui ne se trouvent pas dans 'equivalences.txt' doivent être rendus dans les fichiers de sortie tels qu'ils sont au départ (espace, virgule, point, tiret, etc.). De même, es balises devraient être laissées telles quelles car une grande partie d'entre elles comportent un ou des attribut(s) de style qu'il faudrait conserver pour le moment.

2) Exemples des substitutions attendues:

	- un ligne avec plusieurs mots:
<FONT FACE="SPIonic">ei)/ pe/r ga/r te xo/lon ... katape/yh|, a)lla/ te kai\ meto/pisqen e)/xei ko/ton</FONT>
>> résultat attendu:
<FONT FACE="SPIonic">εἴ πέρ γάρ τε χόλον ... καταπέυῃ, ἀλλά τε καὶ μετόπισθεν ἔχει κότον</FONT>

	- ou encore:
<FONT FACE="SPIonic" COLOR="#CC0000">ai(mo/rrooj, -ouj</FONT>
>> résultat attendu:
<FONT FACE="SPIonic" COLOR="#CC0000">αἱμόρροος, -ους</FONT>

	- une ligne avec un mot à convertir:
<FONT FACE="SPIonic">new=n</FONT>
>> résultat attendu:
<FONT FACE="SPIonic">νεῶν</FONT>

*/
new Spionic();
class Spionic {
  /** translitération table */
  static $tr;
  /** initialisation */
  function __construct() {
    include_once (dirname(__FILE__).'/I18n.php');
    self::$tr=I18n::json(dirname(__FILE__).'/spi_utf8.json');
  }


  /**
   * Scan récursif de dossiers pour ramasser tous les fichiers
   */
  public static function scan ($dir, $include='/.*\.htm.?/') {
    if (!is_readable($dir)) return false;
    $scan=array();
    $ls=scandir($dir); // readdir will not sort entries
    foreach( $ls as $fileName) {
      if($fileName=="." || $fileName=="..") continue;
      $file="$dir/$fileName";
      if (is_dir($file)) {
        $scan=array_merge($scan, self::scan($file, $include)); // dirs may be excluded but not included
        continue;
      }
      if ($include && !preg_match($include, $fileName)) continue;
      $file=realpath($file);
      $scan[]=$file;
      self::parse($file);
    }
    return $scan;
  }
  /**
   * Parse an html file
   */
  public static function parse($file) {
    echo "\n == ",$file,"\n";
    $html=file_get_contents($file);
    $html=mb_convert_encoding($html, "UTF-8");
    $html=preg_replace('@CONTENT="text/html[^"]*"@i', 'content="text/html; charset=utf-8"', $html);
    $html=html_entity_decode($html, null, "UTF-8");
    $html=preg_replace_callback( '@{{[\r\n]+<FONT FACE="SPIonic"([^>]*)>([^<]+)</FONT>[\r\n]+}}@i', "Spionic::tr", $html);
    file_put_contents($file, $html);
  }
  /**
   * Transliteration callback
   */
  public static function tr($matches) {
    $ret= '<font class="grc"'.$matches[1].'>'.strtr($matches[2], self::$tr).'</font>';
    return $ret;
  }
}

// included file, do nothing
if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) != basename(__FILE__));
else if (isset($_SERVER['ORIG_SCRIPT_FILENAME']) && realpath($_SERVER['ORIG_SCRIPT_FILENAME']) != realpath(__FILE__));
// direct command line call, work
else if (php_sapi_name() == "cli") {
	array_shift($_SERVER['argv']); // shift first arg, the script filepath
	if (!count($_SERVER['argv'])) exit('
	usage		: php -f Spionic.php dir
	dir	    : a directory to scan where html file will be parsed
');
  // print_r(get_html_translation_table());
  Spionic::scan($_SERVER['argv'][0]);

}


?>
