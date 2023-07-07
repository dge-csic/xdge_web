<!doctype html><?php
$time_start = microtime(true);
/**
List lemmas

1) suggest/[0-9]+ a rowid of a lemma is requested 
2) suggest/.+ a lemma is requested 

 */
include __DIR__ . "/Xdge.php";

use Oeuvres\Kit\{Web};

// caching on date of the database
Web::notModified(Xdge::$p['xdge_db']);

$lib = ""; // where to find resources
if (!isset($inverso)) $inverso=false;


// persistent number of lemmas in column, before and after the selected index
$before = 0 + Web::par('before', 1);
if ($before<1) $before=1;
$after=70-$before;
if($after < 1) $after=1;


$pathinfo=Web::pathinfo();
// if number -> rowid
if (is_numeric($pathinfo)) $rowid=$pathinfo;
else if (!$pathinfo) $rowid=0;
else if($inverso) $rowid = Xdge::inversoRowid($pathinfo);
// if letters -> letter index
else $rowid = Xdge::rowid($pathinfo);

// if a word is requested, allow cache by client, to avoid too much hits on keypress
/*
if ($form) {
  $expires = 60*60*24;
  header("Pragma: public");
  header("Cache-Control: maxage=".$expires);
  header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
}
*/



?>
<html>
  <head>
    <title>Indicar, DGE Diccionario Griego-Español</title>
    <link rel="stylesheet" href="<?= $lib ?>theme/dge.css"/>
    <base target="article"/>
    <script type="text/javascript" src="<?= $lib ?>theme/dge.js">//</script>
  </head>
  <body onclick="navGo(null, event)" class="lemmas" <?php
if ($inverso) echo ' style="text-align:right;"';
echo '>';
// echo '<form style="position:fixed; width:100%"><input style="width:100%" name="log"/></form>';

// 
if ($rowid===false) {
    echo "<p>No hay ningún lema que empiece por <b>\"",$pathinfo,"\"</b>. La sección de diccionario cubierta por DGE en línea es α - ἔξαυος. </p>";
}
else {
    $start = $rowid-$before;
    if ($start < 1) $start = 1;
    $end = $rowid + $after;
    if ($inverso) $q = Xdge::$pdo->prepare("SELECT id, label, rowid FROM inverso WHERE rowid >= ? AND rowid<= ?");
    else $q = Xdge::$pdo->prepare("SELECT id, label, rowid FROM entry WHERE rowid >= ? AND rowid<= ?");
    // if ($start > 1) echo '<a target="_self" href="',($rowid - $after + $before),'">…</a>',"\n";

    $q->execute(array($rowid - $before, $rowid + $after));
    $active = $rowid;
    $i = 0;
    $prevId="";
    // δῆλος 1, δῆλος 2.  homograph hack
    while($row = $q->fetch(PDO::FETCH_ASSOC) ) {
        $last = $row['rowid'];
        if($active && $last == $active) $class = ' id="active" class="active" ';
        else $class = "";
        $i++;
        // homograph, do not open a second link here
        if ($row['id'] == $prevId);
        // test Safari ? encoding pb ? rowid param is given to article page to keep
        else echo '<a',$class,' id="',$row['id'],'" href="../article/',$row['id'],'">' ,$row['label'],'</a>',"\n";
        $prevId=$row['id'];
    }
  // test if there is a lemma left
  // echo '<a target="_self" href="',($last - $before +1),'">…</a>';
}


echo "<!-- ",$time_start - microtime(true),"ms. -->\n";
    ?>
  </body>
</html>