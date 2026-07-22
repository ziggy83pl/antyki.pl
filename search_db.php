<?php
require 'config/db.php';
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$found = false;
foreach($tables as $table) {
    $stmt = $db->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $text_cols = [];
    foreach($columns as $col) {
        if (strpos($col['Type'], 'char') !== false || strpos($col['Type'], 'text') !== false) {
            $text_cols[] = $col['Field'];
        }
    }
    if(empty($text_cols)) continue;
    $where = [];
    foreach($text_cols as $col) {
        $where[] = "`$col` LIKE '%freehosting%'";
    }
    $query = "SELECT * FROM `$table` WHERE " . implode(' OR ', $where);
    $stmt = $db->query($query);
    if($stmt->rowCount() > 0) {
        echo "Found in table: $table\n";
        $found = true;
    }
}
if(!$found) echo "Not found in DB\n";
