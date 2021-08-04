<?php
// shared libraries
include 'xdge.php';
// requested word
$lemma=Web::pathinfo();
// Get user-agent, frames for human browsers, full content for others.
$browser=Web::browser();
if (isset($_REQUEST['bot'])) $browser=array();
// persistent parameter for the pannel view, cookie is set onclick by javascript
$tab=Web::param("tab", "indicar", 3600);
// persistent


?><!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8"/>
    <title><?php if($lemma) echo $lemma,', '; ?>DGE Diccionario Griego-Español</title>
<?php
// http://googlewebmastercentral.blogspot.fr/2011/09/pagination-with-relnext-and-relprev.html
// TODO implement prev/next on all words

?>
    <link rel="stylesheet" href="<?php echo xdge::$libHref; ?>dge.css"/>
    <!--[if lt IE 9]>
      <script src="<?php echo xdge::$libHref; ?>html5shiv.js">//</script>
    <![endif]-->
    <script type="text/javascript" src="<?php echo xdge::$libHref; ?>json/lat_grc.json">//</script>
    <script type="text/javascript" src="<?php echo xdge::$libHref; ?>dge.js">//</script>
    <script type="text/javascript" src="<?php echo Web::pathBase(); ?>xdge.js">//</script>
  </head>
  <?php flush(); ?>
  <body class="xdge">
    <header id="header">
      <a href="http://dge.cchs.csic.es/" id="DGE"><img src="<?php echo xdge::$libHref; ?>img/dge_64.png"/></a>
      <div class="grad">
      <div class="tabs">
       <a href="http://dge.cchs.csic.es/lst/lst-int.htm" target="_new" title="Listas de ediciones de referencia y de abreviaturas empleadas en el DGE">Listas</a>
       <a target="article" href="doc/creditos.html" title="Créditos y agradecimientos">Créditos</a>
      </div>
      <a href="http://dge.cchs.csic.es/">Diccionario<br/>Griego–Español</a></div>
    </header>
    <article id="article">
      <?php
// If not a human browser, no frame, hsould we use js instead?
if (count($browser) == 0) echo xdge::article($lemma);
else {
  echo '<iframe name="article" width="100%" height="100%" frameborder="0" src="'; 
  if($lemma ) { // $lemma==1 when nothing found on some servers, strange
    // TODO here 
    echo Web::pathBase(),'article/',$lemma;
    // if (isset($_REQUEST['mark'])) echo '?mark=',$_REQUEST['mark']);
  }
  else if (Web::pathinfo() == '') {
    echo Web::pathBase(),'doc/presentacion';
  }
  else {
    echo Web::pathBase(),'doc/404';
  }
  echo '"> </iframe>';
  // TODO, lien vers DGE…
}
      ?>
    </article>
    <?php flush(); ?>
    <aside id="aside">
      <div id="home">
        <a target="_top" href="."><i>DGE</i> en línea</a>
      </div>
      <div id="form">
        <div class="tabs">
<?php
echo '<a title="Lista alfabética de los lemas" id="indicar" ';
echo ' href="',Web::pathBase(),'indicar/',$lemma,'" target="suggest" onclick="return tab(this);"';
if ($tab=="indicar") echo ' class="active"';
echo '>Lemas</a>';

echo '<a title="Lista de los lemas ordenados por su terminación" id="inverso" ';
echo ' href="',Web::pathBase(),'inverso/',$lemma,'" target="suggest" onclick="return tab(this);"';
if ($tab=="inverso") echo ' class="active"';
echo'>Inverso</a>';

?>
        </div>
<?php
// the input field
echo '<input name="q" id="q" accesskey="Q" autocomplete="off" placeholder="palabra a buscar" 
  title="Para llegar a un artículo, escribir aquí el lema en Beta Code o Unicode. La lista se posiciona en el punto indicado."
  onkeyup="if (ret=qKey(this, event)) { win=window.frames[\'suggest\']; win.location.replace(win.location.href.replace(/(indicar|inverso)\/.*$/, \'$1/\'+this.value));} "  ';
// what to display in the field ?
if (file_exists("doc/".$lemma.".html")); // documentation, display nothing
else if ($tab == 'inverso') { // if inverso, put reverse lemma
  echo ' class="inverso"';
}
echo  ' value="',$lemma,'"/>';
echo '<script type="text/javascript">var toFoc=document.getElementById("q"); if(toFoc && !toFoc.autofocus) toFoc.focus();</script>';

?>
      </div>
      <div id="nav">
<?php
echo '<iframe width="100%" height="100%" frameborder="0" id="suggest" name="suggest" src="',Web::pathBase();
if ($tab=="inverso") echo 'inverso/';
else echo 'indicar/';
echo $lemma,'"> </iframe>';

/*
Sabine, attention sur le lien licence.
Ici nous sommes dans un contexte de frame, il faut faire comme pour creditos, un @target="article"
*/

       ?>
      </div>
    </aside>
    <div id="footer">
      <a href="#" onmouseover="this.href='ma'+'ilto'+'\x3A'+'dge'+'\x40'+'cchs.csic.es'">Proyecto DGE (contacto)</a>  - <a target="article" href="doc/licencia.html">Licencia</a>  - <a target="_blank" href="http://www.csic.es/">CSIC</a>
      <a href="http://algone.net/" title="Made by Algone"  target="_new"><img align="right" alt="Algone" src="<?php echo xdge::$libHref; ?>img/algone.png"/></a>
      <a href="xml/" title="&lt;TEI&gt xml source"  target="_new"><img align="right" alt="&lt;TEI&gt" src="<?php echo xdge::$libHref; ?>img/tei.png"/></a>
    </div>
  </body>
</html>
