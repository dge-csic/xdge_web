<?php declare(strict_types=1);

include_once(__DIR__ . '/Xdge.php');

use Oeuvres\Kit\{Http, Select};

header( 'Content-type: text/html; charset=utf-8' );
// said to be useful for flush
header('Content-Encoding: none');

ob_implicit_flush();
while (ob_get_level()) ob_end_clean();

$main = function() {
    results();
};

function results()
{
    $q = Http::par('q', '');
    if (!$q) {
        // echo "<h1>0 resultados</h1>";
        return;
    }
    echo "<article class=\"results\">\n";
    $where = " WHERE search MATCH ? LIMIT 1000";

    $fields = Http::pars('f');
    if (count($fields)) {
        $fts = '(type: "' . implode('" OR "', $fields) . '") AND (text: ' . Xdge::monoton($q) . ')';
    }
    else {
        $fts = $q;
    }
    // counting results
    $stmt = Xdge::$pdo->prepare("SELECT COUNT(*) AS count FROM search " . $where);
    $stmt->execute([$fts]);
    $max  = $stmt->fetchColumn();
    if (!$max) {
        echo "<h1>No se ha encontrado</h1>";
        return;
    }
    echo "<h1>$max resultados</h1>";
    $stmt->execute([$fts]);


    $stmt = Xdge::$pdo->prepare(
        "SELECT *, highlight(search, 0, '<mark>', '</mark>') as hi FROM search " . $where);
     
    $stmt->execute([$fts]);
    $n = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $n++;
        $article = '';
        // $article .= "<section class=\"hit\">\n";
        $article .= "<a class=\"hit\" href=\"{$row['entryname']}?q=$q#{$row['name']}\">\n";
        $article .= "    <small class=\"n\">$n.</small>\n";
        $article .= "    " . $row['branch'] . "\n";
        // hilite the html conmponent
        $html = "<div class=\"found\">" 
            . Xdge::hilite($row['html'], $row['hi']) 
            . "</div>\n";
        $context = preg_replace('/({\$html})\s*\pP/u', '$1', $row['context']);
        $article .= "    " . str_replace('{$html}', $html, $context) . "\n";

        $article .= "</a>\n\n";
        // $article .= "</section>\n";
        print($article);
        flush();
    }
    echo "</article>\n";

}

?>