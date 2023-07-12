<?php

declare(strict_types=1);
include __DIR__ . "/Xdge.php";

$time_start = microtime(true);
/**
 * List lemmas
 *
 * 1) suggest/[0-9]+ a rowid of a lemma is requested 
 * 2) suggest/.+ a lemma is requested 
 */

use Oeuvres\Kit\{Http, Route};
// allow caching on date of the database
// Http::notModified(Xdge::$p['xdge_db']);
$form = Http::par('form');
$inverso = Http::par('inverso', null);
$home_href = Route::home_href();
// if number -> rowid
if (is_numeric($form)) $rowid = $form;
else if (!$form) $rowid = 0;
else if ($inverso) $rowid = Xdge::inversoRowid($form);
// if letters -> letter index
else $rowid = Xdge::rowid($form);




// persistent number of lemmas in column, before and after the selected index
$before = Http::int('before', 1, 1);
if ($before < 1) $before = 1;
$after = 70 - $before;
if ($after < 1) $after = 1;



// if a word is requested, allow cache by client, to avoid too much hits on keypress
/*
if ($form) {
  $expires = 60*60*24;
  header("Pragma: public");
  header("Cache-Control: maxage=".$expires);
  header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
}
*/



if ($rowid === false) {
    echo "<p>No hay ningún lema que empiece por <b>\"", $form, "\"</b>. La sección de diccionario cubierta por DGE en línea es α - ἔξαυος. </p>";
} else {
    $start = $rowid - $before;
    if ($start < 1) $start = 1;
    $end = $rowid + $after;
    if ($inverso) $q = Xdge::$pdo->prepare("SELECT xmlid, label, rowid FROM inverso WHERE rowid >= ? AND rowid <= ?");
    else $q = Xdge::$pdo->prepare("SELECT xmlid, label, rowid FROM entry WHERE rowid >= ? AND rowid<= ?");
    // if ($start > 1) echo '<a target="_self" href="',($rowid - $after + $before),'">…</a>',"\n";

    $q->execute(array($rowid - $before, $rowid + $after));
    $active = $rowid;
    $i = 0;
    $prevId = "";
    // δῆλος 1, δῆλος 2.  homograph hack
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $last = $row['rowid'];
        $class = '';
        // if ($active && $last == $active) $class = " active";
        $i++;
        // homograph, do not open a second link here
        if ($row['xmlid'] == $prevId);
        // test Safari ? encoding pb ? rowid param is given to article page to keep
        else echo '<a class="lemma' . $class . '" id="_' . $row['xmlid'] . '" href="' . $row['xmlid'] . '">' . $row['label'] . '</a>' . "\n";
        $prevId = $row['xmlid'];
    }
    // test if there is a lemma left
    // echo '<a target="_self" href="',($last - $before +1),'">…</a>';
}


echo "<!-- " . ($time_start - microtime(true)) . "ms. -->\n";
