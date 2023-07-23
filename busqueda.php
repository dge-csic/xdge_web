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
    $sql = "SELECT *, highlight(search, 0, '<mark>', '</mark>') as hi FROM search WHERE search MATCH ? LIMIT 1000";
    $stmt = Xdge::$pdo->prepare($sql);

    $fields = Http::pars('f');
    if (count($fields)) {
        $fts = '(type: "' . implode('" OR "', $fields) . '") AND (text: ' . Xdge::monoton($q) . ')';
        // $q = implode('" OR "', $fields) ;
    }
    else {
        $fts = $q;
    }

    $stmt->execute([$fts]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $article = "<section class=\"hit\">\n";
        $article .= "    <a class=\"hit\" href=\"{$row['entryname']}?q=$q#{$row['name']}\">\n";
        $article .= $row['branch'];
        // hilite the html conmponent
        $html = "<div class=\"found\">" 
            . Xdge::hilite($row['html'], $row['hi']) 
            . "</div>";
        $article .= str_replace('{$html}', $html, $row['context']) . "\n";

        $article .= "</a>";
        $article .= "</section>\n";
        print($article);
        flush();
    }
    echo "</article>\n";

}

?>