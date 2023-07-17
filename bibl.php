<?php declare(strict_types=1);

include_once(__DIR__ . '/Xdge.php');

use Oeuvres\Kit\{Http};

$main = function() {
    echo "author\ttitle\tentry\tscope\n";
    $q = Xdge::$pdo->prepare("SELECT * FROM bibl ORDER BY author, title, entry");
    $q->execute([]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        echo $row['author'] . "\t" . $row['title'] . "\t" . $row['entryname'] . "\t" . $row['scope'], "\n";
    }

};
$main();