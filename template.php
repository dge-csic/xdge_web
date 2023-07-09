<?php

declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title><?= Route::title() ?></title>
    <link rel="stylesheet" href="theme/xdge_article.css" />
    <link rel="stylesheet" href="theme/xdge_layout.css" />
    <script type="text/javascript" src="<?= Route::home_href() ?>theme/dge.js">
        //
    </script>
    <script type="text/javascript" src="<?= Route::home_href() ?>theme/xdge.js">
        //
    </script>
</head>

<body>
    <div id="win">
        <header id="header">
            <a href="http://dge.cchs.csic.es/" id="DGE"><img src="<?= Route::home_href() ?>theme/dge_64.png" /></a>
            <div class="grad">
                <div class="tabs">
                    <a href="http://dge.cchs.csic.es/lst/lst-int.htm" target="_new" title="Listas de ediciones de referencia y de abreviaturas empleadas en el DGE">Listas</a>
                    <a target="article" href="doc/creditos.html" title="Créditos y agradecimientos">Créditos</a>
                </div>
                <a href="http://dge.cchs.csic.es/">Diccionario<br />Griego–Español</a>
            </div>
        </header>
        <article id="article">
            <?= Route::main() ?>
        </article>
        <aside id="aside">
            <div id="home">
                <a target="_top" href="."><i>DGE</i> en línea</a>
            </div>
            <div id="form">
                <div class="tabs">
                    <?php
                    echo '<a title="Lista alfabética de los lemas" id="indicar" ';
                    echo ' href="', $home_href, 'indicar/', $lemma, '" target="suggest" onclick="return tab(this);"';
                    if ($tab == "indicar") echo ' class="active"';
                    echo '>Lemas</a>';

                    echo '<a title="Lista de los lemas ordenados por su terminación" id="inverso" ';
                    echo ' href="', $home_href, 'inverso/', $lemma, '" target="suggest" onclick="return tab(this);"';
                    if ($tab == "inverso") echo ' class="active"';
                    echo '>Inverso</a>';

                    ?>
                </div>
                <?php
                // the input field
                echo '<input name="q" id="q" accesskey="Q" autocomplete="off" placeholder="palabra a buscar" 
  title="Para llegar a un artículo, escribir aquí el lema en Beta Code o Unicode. La lista se posiciona en el punto indicado."
  onkeyup="if (ret=qKey(this, event)) { win=window.frames[\'suggest\']; win.location.replace(win.location.href.replace(/(indicar|inverso)\/.*$/, \'$1/\'+this.value));} "  ';
                // what to display in the field ?
                if (file_exists("doc/" . $lemma . ".html")); // documentation, display nothing
                else if ($tab == 'inverso') { // if inverso, put reverse lemma
                    echo ' class="inverso"';
                }
                echo  ' value="', $lemma, '"/>';
                echo '<script type="text/javascript">var toFoc=document.getElementById("q"); if(toFoc && !toFoc.autofocus) toFoc.focus();</script>';

                ?>
            </div>
            <div id="nav">
                <?php
                echo '<iframe width="100%" height="100%" frameborder="0" id="suggest" name="suggest" src="', $home_href;
                if ($tab == "inverso") echo 'inverso/';
                else echo 'indicar/';
                echo $lemma, '"> </iframe>';

                /*
Sabine, attention sur le lien licence.
Ici nous sommes dans un contexte de frame, il faut faire comme pour creditos, un @target="article"
*/

                ?>
            </div>
        </aside>
        <footer id="footer">
            <a href="#" onmouseover="this.href='ma'+'ilto'+'\x3A'+'dge'+'\x40'+'cchs.csic.es'">Proyecto DGE (contacto)</a> - <a target="article" href="doc/licencia.html">Licencia</a> - <a target="_blank" href="http://www.csic.es/">CSIC</a>

            <a href="https://github.com/dge-csic/xdge" title="&lt;TEI&gt xml source" target="_new"><img alt="&lt;TEI&gt" src="<?= Route::home_href() ?>theme/img/tei.png" /></a>
        </footer>

    </div>
</body>

</html>