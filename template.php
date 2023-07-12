<?php

declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

$form = Http::par('form');
$tab = Http::par("tab", "indicar", null, "tab");
$home_href = Route::home_href();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title><?= Route::title() ?></title>
    <link rel="stylesheet" href="theme/xdge_article.css" />
    <link rel="stylesheet" href="theme/xdge_layout.css" />
</head>

<body>
    <div id="win">
        <header id="header">
            <div class="left">
                <a target="_top" href="."><i>DGE</i> en línea</a>
            </div>
            <div class="tabs" id="tabs-left">
            <?php
echo '<a title="Lista alfabética de los lemas" id="indicar" ';
echo ' href="', $home_href ,'indicar/',$form,'" target="suggest" onclick="return tab(this);"';
if ($tab=="indicar") echo ' class="active"';
echo '>Lemas</a>';

echo '<a title="Lista de los lemas ordenados por su terminación" id="inverso" ';
echo ' href="', $home_href ,'inverso/',$form,'" target="suggest" onclick="return tab(this);"';
if ($tab=="inverso") echo ' class="active"';
echo'>Inverso</a>';

?>
            </div>
            <div class="tabs" id="tabs-right">
                <a href="http://dge.cchs.csic.es/lst/lst4.htm" target="_new" title="Abreviaturas empleadas en el DGE">Abreviaturas</a>
                <a href="http://dge.cchs.csic.es/lst/lst-int.htm" target="_new" title="Listas de ediciones de referencia y de abreviaturas empleadas en el DGE">Listas</a>
                <a target="article" href="doc/creditos.html" title="Créditos y agradecimientos">Créditos</a>

            </div>
            <div class="right">
                <a href="http://dge.cchs.csic.es/">Diccionario<br />Griego–Español</a>
                <a href="http://dge.cchs.csic.es/"><img src="<?= Route::home_href() ?>theme/dge_64.png" /></a>
            </div>
        </header>
        <div id="middle">
            <div id="left">
                <form name="lemmas" action="lemmas.php">
                    <input name="form" id="form"/>
                    <input type="hidden" name="inverso"/>
                </form>
                <div id="lemmas" data-url="lemmas.php">
                    <!-- -->
                </div>
            </div>
            <div id="right">
                <article id="article">
                    <?= Route::main() ?>
                </article>
            </div>
        </div>
        <footer id="footer">
            <a id="tei" href="https://github.com/dge-csic/xdge_xml" title="&lt;TEI&gt xml source" target="_new"><img alt="&lt;TEI&gt" src="<?= Route::home_href() ?>theme/tei.png" /></a>
            <div>
            <a href="#" onmouseover="this.href='ma'+'ilto'+'\x3A'+'dge'+'\x40'+'cchs.csic.es'">Proyecto DGE (contacto)</a> – <a target="article" href="doc/licencia.html">Licencia</a> – <a target="_blank" href="http://www.csic.es/">CSIC</a>
            <div>
        </footer>
    </div>
    <script src="<?= Route::home_href() ?>theme/xdge.js">//</script>
    <script>
const form = document.forms['lemmas'];
form.form.value = '<?= $form ?>';
form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
    </script>
</body>

</html>