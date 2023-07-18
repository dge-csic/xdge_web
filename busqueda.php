<?php declare(strict_types=1);

include_once(__DIR__ . '/Xdge.php');

use Oeuvres\Kit\{Http};

// supposed required to output logging line by line
header( 'Content-type: text/html; charset=utf-8' );
// said to be useful for flush
header('Content-Encoding: none');

ob_implicit_flush();
while (ob_get_level()) ob_end_clean(); 

$main = function() {
    echo "<article class=\"results\">\n";
    $q = Http::par('q');
    $sql = "SELECT *, highlight(search, 0, '<mark>', '</mark>') as hi FROM search WHERE text MATCH ? LIMIT 1000";
    $stmt = Xdge::$pdo->prepare($sql);
    $stmt->execute([$q]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $article = "<section class=\"hit\">\n";
        $article .= "    <a class=\"hit\" href=\"{$row['entryname']}#{$row['name']}\">\n";
        $article .= $row['branch'];

        // hilite the html conmponent
        $html_src = $row['html'];
        $html_pos = 0;
        $html = "";
        $hi = $row['hi'];
        $hi_pos = 0;
        while (true) {
            $pos = mb_strpos($hi, "<mark>", $hi_pos);
            if ($pos === false) break;
            $html .= mb_substr($html_src, $html_pos, $pos - $hi_pos);
            $html_pos = $html_pos + $pos - $hi_pos;
            $html .= "<mark>";
            $hi_pos = $pos + 6;

            $pos = mb_strpos($hi, "</mark>", $hi_pos);
            if ($pos === false) break;
            $html .= mb_substr($html_src, $html_pos, $pos - $hi_pos);
            $html_pos = $html_pos + $pos - $hi_pos;
            $html .= "</mark>";
            $hi_pos = $pos + 7;
        }
        $html .=  mb_substr($html_src, $html_pos);
        $html = "<div class=\"found\">$html</div>";
        $article .= str_replace('{$html}', $html, $row['context']) . "\n";

        $article .= "</a>";
        $article .= "</section>\n";
        print($article);
        flush();
    }
    echo "</article>\n";
};
