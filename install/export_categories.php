<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../php/bootstrap.php';

$db = \App\Core\App::db();
$cats = $db->query('SELECT * FROM category ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$sql = "-- Synchronizacja kategorii i podkategorii antyków\n";
$sql .= "INSERT INTO `category` (`id`, `category_id`, `position`, `slug`, `name`, `cost`, `thumb`, `path`, `content`, `h1`, `title`, `keywords`, `description`) VALUES\n";

$rows = [];
foreach ($cats as $c) {
    $id = (int)$c['id'];
    $category_id = (int)$c['category_id'];
    $position = (int)$c['position'];
    $slug = $db->quote($c['slug']);
    $name = $db->quote($c['name']);
    $cost = $c['cost'] !== null ? $db->quote($c['cost']) : 'NULL';
    $thumb = $c['thumb'] !== null ? $db->quote($c['thumb']) : 'NULL';
    $path = $c['path'] !== null ? $db->quote($c['path']) : 'NULL';
    $content = $c['content'] !== null ? $db->quote($c['content']) : 'NULL';
    $h1 = $c['h1'] !== null ? $db->quote($c['h1']) : 'NULL';
    $title = $c['title'] !== null ? $db->quote($c['title']) : 'NULL';
    $keywords = $c['keywords'] !== null ? $db->quote($c['keywords']) : 'NULL';
    $description = $c['description'] !== null ? $db->quote($c['description']) : 'NULL';

    $rows[] = "($id, $category_id, $position, $slug, $name, $cost, $thumb, $path, $content, $h1, $title, $keywords, $description)";
}

$sql .= implode(",\n", $rows) . "\nON DUPLICATE KEY UPDATE `category_id`=VALUES(`category_id`), `position`=VALUES(`position`), `slug`=VALUES(`slug`), `name`=VALUES(`name`), `cost`=VALUES(`cost`), `thumb`=VALUES(`thumb`), `path`=VALUES(`path`), `content`=VALUES(`content`), `h1`=VALUES(`h1`), `title`=VALUES(`title`), `keywords`=VALUES(`keywords`), `description`=VALUES(`description`);\n";

file_put_contents(__DIR__ . '/import_antyki_categories.sql', $sql);
echo "Generated install/import_antyki_categories.sql with " . count($cats) . " categories.\n";
