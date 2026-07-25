<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../php/bootstrap.php';

$sqlFile = __DIR__ . '/import_antyki_categories.sql';

if (!file_exists($sqlFile)) {
    die("Błąd: Plik SQL import_antyki_categories.sql nie istnieje.");
}

try {
    $db = \App\Core\App::db();
    $sql = file_get_contents($sqlFile);
    
    $sql = str_replace('`category`', '`' . _DB_PREFIX_ . 'category`', $sql);
    
    $db->exec($sql);
    
    echo "<div style='font-family: sans-serif; padding: 20px; color: green;'>";
    echo "<h2>SUKCES!</h2>";
    echo "<p>Pomyślnie zaimportowano / zsynchronizowano 62 kategorie i podkategorie z bazy lokalnej do bazy produkcyjnej!</p>";
    echo "<p><a href='../' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Przejdź do strony głównej</a></p>";
    echo "</div>";
} catch (\Throwable $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; color: red;'>";
    echo "<h2>BŁĄD IMPORTU:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
