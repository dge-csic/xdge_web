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
// allow caching on date of the database ?
// Http::notModified(Xdge::$p['xdge_db']);

$inverso = Http::par('inverso', null);
$home_href = Route::home_href();

$before = 10;
$after = 90;

$id_start = 0;
$id_end = $before + $after;
$id_form = 0;
$form = Http::par('form', null);
if ($form) {
    if ($inverso) {
        $id_form = Xdge::inversoRowid($form);
    }
    else {
        $id_form = Xdge::rowid($form);
    }
    // no rowid found for the form
    if ($id_form == -1) {
        echo "<p>No hay ningún lema que empiece por <b>\"", $form, "\"</b>. La sección de diccionario cubierta por DGE en línea es α - ἐπισκήπτω.</p>";
        return;
    }
    $id_start = max(0, $id_form - $before);
    $id_end = $id_form + $after;
}
else {
    $id_start = Http::int('id_start', null);
    $id_end = Http::int('id_end', null);
    if ($id_start !== null && $id_start >= 0) {
        $id_form = null;
        $id_end = $id_start + $before + $after;
    }
    else if ($id_end !== null && $id_end >= 0) {
        $id_form = null;
        $id_start = $id_end - $before - $after;
    }
}

// rowid should be the start index
if ($inverso) $q = Xdge::$pdo->prepare("SELECT name, label, rowid FROM inverso WHERE rowid >= ? AND rowid < ?");
else $q = Xdge::$pdo->prepare("SELECT name, label, rowid FROM entry WHERE rowid >= ? AND rowid < ?");
// if ($start > 1) echo '<a target="_self" href="',($rowid - $after + $before),'">…</a>',"\n";

$q->execute(array($id_start, $id_end));
$i = 0;
// δῆλος 1, δῆλος 2.  homograph hack
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $class = '';
    // if form was requested, hilite it
    if ($id_form !== null && $id_form == $row['rowid']) {
        $class = " active";
    }
    $i++;
    // @xml:id is unique (verified by schema in index in database)
    // but lemma may be not (homograph name="word-flexion-flexion")
    // test Safari ? encoding pb ? rowid param is given to article page to keep
    echo '<a data-rowid="' . $row['rowid'] . '" class="lemma' . $class . '" id="_' . $row['name'] . '" href="' . $row['name'] . '">' . $row['label'] . '</a>' . "\n";
}
// test if there is a lemma left
// echo '<a target="_self" href="',($last - $before +1),'">…</a>';


echo "<!-- " . ($time_start - microtime(true)) . "ms. -->\n";
