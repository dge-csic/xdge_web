<?php
// shared libraries
include 'xdge.php';
$q=Web::param("q");
?>
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8"/>
    <title>Busqueda, DGE Diccionario Griego-Español</title>
    <link rel="stylesheet" href="<?php echo xdge::$libHref; ?>dge.css"/>
    <script type="text/javascript" src="<?php echo xdge::$libHref; ?>dge.js">//</script>
  </head>
  <body onload="docLoad()">
    <div id="header">
      <a href="http://dge.cchs.csic.es/" id="DGE"><img src="<?php echo xdge::$libHref; ?>img/dge_64.png"/></a>
      <div class="grad"><a href="http://dge.cchs.csic.es/">Diccionario<br/>Griego–Español</a></div>
    </div>
    <!-- sidebar -->
    <div id="aside">
    </div>
    <!-- body -->
    <div id="article">
      <form name="busqueda">
        <input name="q" size="50" value="<?php echo str_replace('"', '&quot;', $q); ?>"/>
      </form>
      <table class="snip">
      <?php
$query=xdge::$pdo->prepare('SELECT id, label, text, offsets(search) AS offsets FROM search WHERE text MATCH ? LIMIT 1000;');
$query->execute(array($q));
$start=1;
while ($row=$query->fetch()) {
  echo '<tr><td><small>',$start++,'.</small> <a class="lemma" href="',$row['id'],'?mark=',$q,'">',$row['label'],'</a>',"</td></tr>\n";
  $offsets=explode(' ',$row['offsets']);
  $count=count($offsets);
  // echo '<tr><td colspan="3"><pre style="white-space:pre-wrap; font-family:serif; ">',$row['text'],'</pre></td></tr>';
  for ($i=0; $i<$count;$i=$i+4) {
    echo snip($row['text'], $offsets[$i+2], $offsets[$i+3]),"\n";
  }
}
      ?>
    </div>
    <div id="footer">
      <a href="#" onmouseover="this.href='ma'+'ilto'+'\x3A'+'dge'+'\x40'+'cchs.csic.es'">Proyecto DGE (contacto)</a>  - <a target="article" href="doc/licencia.html">Licencia</a> - <a target="_blank" href="http://www.csic.es/">CSIC</a>
      <a href="http://algone.net/" title="Made by Algone"  target="_new"><img align="right" alt="Algone" src="<?php echo xdge::$libHref; ?>img/algone.png"/></a>
      <a href="xml/" title="&lt;TEI&gt xml source"  target="_new"><img align="right" alt="&lt;TEI&gt" src="<?php echo xdge::$libHref; ?>img/tei.png"/></a>
    </div>
  </body>
</html>
<?php 
function snip ($text, $offset, $size) {
  $width=70;
  $snip=array();
  $snip[]='<tr class="snip"><td align="right">';
  $start=$offset-$width;
  $length=$width;
  if($start < 0) {
    $start=0;
    $length=$offset-1;
  }
  // it is byte index and not unicode char
  if ($length) {
    $left=substr($text, $start, $length);
    $pos=strrpos($left, "\n");
    if ( $pos !== false) {
      $left=substr($left, $pos+1);
    }
    else {
      $pos=strpos($left, ' ');
      if ( $pos !== false) $left=substr($left, $pos);
    }
    // espace insécables
    $left=str_replace(' ', ' ', ltrim($left));
    $snip[]=$left;
  }
  $snip[]="</td><td><mark>";
  $snip[]=substr($text, $offset, $size);
  $snip[]="</mark>";
  $start=$offset+$size;
  $length=$width;
  $len=strlen($text);
  if ($start + $length - 1 > $len) $length=$len-$start;
  if($length) {
    $right=substr($text, $start, $length);
    $pos=strpos($right, "\n");
    if ( $pos !== false) {
      $right=substr($right,0, $pos);
    }
    else {
      $pos=strrpos($right, ' ');
      if ($pos) $right=substr($right, 0, $pos);
    }
    $snip[]=rtrim($right);
  }
  $snip[]='</td></tr>';
  return implode('', $snip);
}
?>
