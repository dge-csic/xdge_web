<?php declare(strict_types=1);

include_once(__DIR__ . '/Xdge.php');

use Oeuvres\Kit\{Http};

$main = function() {
    ?>
    <h1>Búsqueda</h1>

    <form name="busqueda" action="resultados">
        <input name="q" value="<?= Http::par('q', '') ?>"/>
        <label>
            <input name="filter" value="quotespa" type="checkbox"/>traducción de cita
        </label>
    </form>

    <?php
}
?>