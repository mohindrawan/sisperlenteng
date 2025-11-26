<?php
require __DIR__ . '/config/database.php';
$pdo = db();
try {
    echo '<h3>Tables</h3><pre>';
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);
    echo '</pre>';

    echo '<h3>Columns: komoditas / komoditas_master / kelompok_komoditas / jenis_tanaman / harga_komoditas</h3><pre>';
    $targets = ['komoditas','komoditas_master','kelompok_komoditas','jenis_tanaman','harga_komoditas'];
    foreach ($targets as $t) {
        echo "-- $t --\n";
        if (in_array($t, $tables, true)) {
            $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            print_r($cols);
        } else {
            echo "Table `$t` NOT FOUND\n";
        }
        echo "\n";
    }
    echo '</pre>';
} catch (Exception $e) {
    echo 'ERR: ' . htmlspecialchars($e->getMessage());
}
?>