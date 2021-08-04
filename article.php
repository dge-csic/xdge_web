<?php
// shared libraries
include 'xdge.php';
// requested word
$lemma=Web::pathinfo();
// crawler policy
// No index of this page,  
$canonical=str_replace('/article/', '/', $_SERVER['REQUEST_URI']);
?>
<!doctype html>
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8"/>
    <meta name="robots" content="noindex"/>
    <link rel="canonical" href="<?php echo $canonical ?>"/>
    <title><?php if($lemma) echo $lemma,', '; ?>DGE (Diccionario Griego-Español)</title>
    <!--[if lt IE 9]>
      <script src="<?php echo xdge::$libHref; ?>html5shiv.js">//</script>
    <![endif]-->
    <link rel="stylesheet" href="<?php echo xdge::$libHref; ?>dge.css"/>
    <script type="text/javascript">
if (self==top); // do something if no nav ?
else {
  window.parent.document.title=document.title;
  if (window.parent.history.replaceState) window.parent.history.replaceState("article", document.title, '<?php echo $canonical ?>');
  // click coming from internal link, sync sidebar 
  if( (win=window.parent.frames['suggest']) && document.referrer && document.referrer.search('article')>-1) {
    lemma='<?php echo $lemma ?>';
    if (a=win.document.getElementById(lemma)) win.navGo(a);
    else win.location.replace(win.location.href.replace(/(indicar|inverso)\/.*$/, '$1/'+lemma+'?before=15'));
  }
}
    </script>
  </head>
  <body class="article">
  <?php
// if a lemma should be displayed (busqueda should have reset $lemma)
if($lemma ) { // $lemma==1 when nothing found on some servers, strange
  $html=xdge::article($lemma);
  if (isset($_REQUEST['mark'])) {
    $hi=new Hilite($_REQUEST['mark']);
    $html=$hi->hi($html);
  }
  echo $html;
}
?>
  <script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-180455-14']);
    _gaq.push(['_trackPageview']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
  </script>
  </body>
</html>
