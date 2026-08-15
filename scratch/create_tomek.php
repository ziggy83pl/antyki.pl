<?php
define('_ADMIN_TEST_MODE_', false);
define('_DEBUG_MODE_', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../functions.php';

try {
    $db = \App\Core\App::db();
    
    // Check if tomek user already exists
    $sth = $db->prepare("SELECT id FROM "._DB_PREFIX_."admin WHERE username = 'tomek'");
    $sth->execute();
    if ($sth->fetchColumn()) {
        echo "Użytkownik tomek już istnieje w bazie!\n";
    } else {
        $passwordHash = createPasswordHash('tomek123');
        $sthIns = $db->prepare("INSERT INTO "._DB_PREFIX_."admin (username, password, role) VALUES ('tomek', :pass, 'admin')");
        $sthIns->bindValue(':pass', $passwordHash, PDO::PARAM_STR);
        $sthIns->execute();
        echo "Dodano moderatora 'tomek' z hasłem 'tomek123'!\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
