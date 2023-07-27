<?php
include(__DIR__ . "/XdgeBuild.php");

use \Oeuvres\Kit\{Xt};

$html = '<dfn class="def">Bryonia cretica<i> L. subsp. </i>dioica<i> (Jacq.) Tutin</i></dfn>';
echo $html . "\n";
$text = Xt::detag($html);
echo $text . "\n";
$text = XdgeBuild::monoton($text);
echo $text . "\n";
