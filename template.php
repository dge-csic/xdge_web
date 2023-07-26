<?php

declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route, Select};

$home_href = Route::home_href();
$q = Http::par('q', '');
$form = Http::par('form', '');
$tab = Http::par("tab", "indicar", null, "tab");
if ($q) $tab = 'busqueda';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title><?= Route::title() ?></title>
    <link rel="preload" as="font" href="<?= Route::home_href() ?>theme/fonts/NotoSansDisplay-Italic-VariableFont_wdth,wght.woff2" type="font/woff2" crossorigin="anonymous">
    <link rel="preload" as="font" href="<?= Route::home_href() ?>theme/fonts/NotoSansDisplay-VariableFont_wdth,wght.woff2" type="font/woff2" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="<?= Route::home_href() ?>theme/teinte.tree.css" />
    <link rel="stylesheet" href="theme/xdge_article.css" />
    <link rel="stylesheet" href="theme/xdge_layout.css" />
</head>

<body>
    <div id="win">
        <header id="header">
            <a class="left" target="_top" href="."><i>DGE</i> en línea</a>
            <a class="right" href="http://dge.cchs.csic.es/">
                <span>Diccionario<br />Griego–Español</span>
                <img src="<?= Route::home_href() ?>theme/dge_64.png" />
            </a>
        </header>
        <div id="middle">
            <div id="left">
                <div class="tabs">
                    <a title="Lista alfabética de los lemas" id="tab_indicar" class="<?=($tab != 'indicar')?'':'active'?>">Lemas</a>
                    <a title="Lista de los lemas ordenados por su terminación" id="tab_inverso" class="<?=($tab == 'inverso')?'active':''?>">Inverso</a>
                    <a id="tab_busqueda" class="<?=($tab == 'busqueda')?'active':''?>">Búsqueda</a>
                    <div class="filler"></div>
                </div>
                <form  
                    id="sugerir" 
                    name="sugerir" 
                    style="<?= ($q)?'display:none':'' ?>" 
                    action="lemmas.php" autocomplete="off"
                    title="Para llegar a un artículo, escribir aquí el lema en Beta Code o Unicode. La lista se posiciona en el punto indicado."
                >
                    <div class="input">
                        <input type="text" name="form" id="form" autocomplete="off"
                    placeholder="palabra a buscar" 
                    value="<?= $form ?>"
                    />
                        <button type="submit">▶</button>
                    </div>
                    <input type="hidden" name="inverso"/>
                </form>
                <form autocomplete="off" name="busqueda" action="busqueda" id="busqueda"  style="<?= ($q)?'':'display:none' ?>">
                    <div class="input">
                        <input type="text" name="q" autocomplete="off" 
                        placeholder="palabra a buscar"
                        value="<?= $q ?>"
                        />
                        <button type="submit">▶</button>
                    </div>
                    <div class="checks">
            <?php 
            $checkbox = new Select('f', Select::CHECKBOX);
            echo $checkbox
                ->add(false, "quotegrc", "cita griega")
                ->add(true, "def", "traducción de lema")
                ->add(true, "quotespa", "traducción de cita")
                ->add(true, "usg", "indicación de uso")
            ;
                    ?>
                    </div>
                </form>
                <div id="lemmas" data-url="lemmas.php">
                    <!-- -->
                </div>
            </div>
            <div id="right">
                <div class="tabs">
                    <div class="filler"></div>
                    <a href="http://dge.cchs.csic.es/lst/lst4.htm" target="_new" title="Abreviaturas empleadas en el DGE">Abreviaturas</a>
                    <a href="http://dge.cchs.csic.es/lst/lst-int.htm" target="_new" title="Listas de ediciones de referencia y de abreviaturas empleadas en el DGE">Listas</a>
                    <a href="creditos" title="Créditos y agradecimientos">Créditos</a>
                </div>
                <main id="main">
                    <?= Route::main() ?>
                </main>
            </div>
        </div>
        <footer id="footer">
            <a id="tei" href="https://github.com/dge-csic/xdge_xml" title="&lt;TEI&gt xml source" target="_new"><img alt="&lt;TEI&gt" src="<?= Route::home_href() ?>theme/tei.png" /></a>
            <div>
            <a href="#" onmouseover="this.href='ma'+'ilto'+'\x3A'+'dge'+'\x40'+'cchs.csic.es'">Proyecto DGE (contacto)</a> – <a target="article" href="licencia">Licencia</a> – <a target="_blank" href="http://www.csic.es/">CSIC</a>
            <div>
        </footer>
    </div>

    <script src="<?= Route::home_href() ?>theme/xdge.js">//</script>
    <script type="text/javascript" charset="utf-8" src="<?= Route::home_href() ?>theme/teinte.tree.js">//</script>
</body>

</html>