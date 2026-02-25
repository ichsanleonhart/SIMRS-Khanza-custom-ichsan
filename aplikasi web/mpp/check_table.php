<?php
require_once 'config/database.php';
try {
    $s = $pdo->query('DESCRIBE penilaian_medis_ranap');
    $r = $s->fetchAll(PDO::FETCH_ASSOC);
    foreach ($r as $c) {
        echo $c['Field'] . '|' . $c['Type'] . '|' . $c['Key'] . PHP_EOL;
    }
}
catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    // Try to list tables
    try {
        $s2 = $pdo->query('SHOW TABLES LIKE "%penilaian%"');
        $tables = $s2->fetchAll(PDO::FETCH_COLUMN);
        echo 'Matching tables: ' . implode(', ', $tables) . PHP_EOL;
    }
    catch (Exception $e2) {
        echo 'Also ERROR: ' . $e2->getMessage() . PHP_EOL;
    }
}
