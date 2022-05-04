<?php // encoding="UTF-8"
/**
<h1>Hilite, hilite words in HTML, with regular expressions but witout breaking tags</h1>

© 2010, <a href="http://www.enc.sorbonne.fr/">École nationale des chartes</a>, <a href="http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html">licence CeCILL-C</a> (LGPL compatible droit français)

<ul>
  <li>2009–2010 [FG] <a onclick="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'enc.sorbonne.fr?subject=[Hiliote.php] '">Frédéric Glorieux</a></li>
</ul>

<p>
Hilite words in HTML
 Surligneur dans du HTML en expressions régulières
 marche sur le texte sans casser les balises, et sur des valeurs en attribut
</p>

*/
class Hilite extends php_user_filter {
  /** tableau de recherche/remplace dans le texte */
  public $re;

  /**
   * Build the regexp with an array of words
   * $a : tableau de termes à chercher
   */
  function __construct ($a) {
    if (!is_array($a)) $a=array($a);
    // transformer une requête en expression régulière
    $re_q=array (
      '/[+\-<>~$^\[\]{},\.\"\\|\'\n\t\r]/u'=>' ', // échapper les caractères regexp un peu spéciaux
      '/([\(\)])/' => '\\\\$1', // protéger les parenthèses
      '/^ +/'=>'', // trim
      '/ +$/'=>'', // trim
      '/ +/'=>' ', // normaliser les espaces
      '/^\*+/'=>"", // supprimer les jokers en début de mot
      '/ /'=>'[\-\s\(\)\'’,_]+', // savoir passer un peu de ponctuation entre les mots d'un terme
      '/\*/'=>'[^ \.\)\],<" ”»=]*',  //  joker '*' = caractère qui n'est pas un séparateur ou une balise
      '/\?/'=>'[^ \.\)\],<" ”»=]',   // en classe unicode \pL, [^ \.\)\],<" ”»=]
      '/a/' => '[aáàÁ]',
      '/e/' => '[eéèêë]',
      '/i/' => '[iíïî]',
      '/o/' => '[oóôö]',
      '/u/' => '[uúûü]',
      '/n/' => '[nñ]',
    );
    $a=preg_replace(array_keys($re_q), array_values($re_q), $a);
    // supprimer les valeurs vides
    $a=array_diff($a, array(""));
    if (count($a) < 1) return;
    // re pour surligner dans du texte
    $keys=preg_replace('/^(.*)$/', '/(?<=[\s >\.,\*\(\[\'’\-])($1)(?=[\s \., <\*\)\- \]:])/iu', $a);
    $this->re=array_combine($keys, array_fill  ( 0 , count($keys) , '<mark>$1</mark>' ));
    // re pour surligner des attributs title="mon mot" > title="mon mot" class="hi"
    $keys=preg_replace('/^(.*)$/', '/<([^\/> ]+)[^>]*(title="$1")[^>]*>/iu', $a);
    $this->re=array_merge ($this->re, array_combine($keys, array_fill  ( 0 , count($keys) , '<$1 $2 class="mark">' )) );
  }
  /** Surligner avec la requête donnée */
  function hi($html) {
    if (count($this->re) < 1) return $html;
    return preg_replace(array_keys($this->re), array_values($this->re), $html);
  }
}

?>
