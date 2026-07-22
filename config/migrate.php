<?php
// migrate.php - Uruchamianie migracji bazy danych na żądanie

// Hasło/Token zabezpieczający przed nieautoryzowanym uruchomieniem
$secret_token = 'migracja2026';

if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['token']) || $_GET['token'] !== $secret_token) {
        header('HTTP/1.0 403 Forbidden');
        die("Dostęp zabroniony. Podaj właściwy token w URL (np. ?token=migracja2026).");
    }
}

require_once __DIR__ . '/db.php';

try {
    $db = new PDO('mysql:host='.$mysql_server.';dbname='.$mysql_db, $mysql_user, $mysql_pass, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die ("Error connecting to database: " . $e->getMessage());
}

echo "Rozpoczynanie migracji bazy danych...<br>";

// Autocreate alerts table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."alerts` LIMIT 1");
    echo "Tabela alerts już istnieje.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."alerts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `email` varchar(128) NOT NULL,
          `category_id` int(11) NOT NULL,
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Utworzono tabelę alerts.<br>";
    } catch (Throwable $ex) {
        echo "Błąd podczas tworzenia tabeli alerts: " . $ex->getMessage() . "<br>";
    }
}

// Autocreate black_list_ip table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."black_list_ip` LIMIT 1");
    echo "Tabela black_list_ip już istnieje.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."black_list_ip` (
          `ip` varchar(45) NOT NULL,
          PRIMARY KEY (`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Utworzono tabelę black_list_ip.<br>";
    } catch (Throwable $ex) {
        echo "Błąd podczas tworzenia tabeli black_list_ip: " . $ex->getMessage() . "<br>";
    }
}

// Autocreate black_list_email table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."black_list_email` LIMIT 1");
    echo "Tabela black_list_email już istnieje.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."black_list_email` (
          `email` varchar(128) NOT NULL,
          PRIMARY KEY (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Utworzono tabelę black_list_email.<br>";
    } catch (Throwable $ex) {
        echo "Błąd podczas tworzenia tabeli black_list_email: " . $ex->getMessage() . "<br>";
    }
}

// Autocreate abuse_reports table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."abuse_reports` LIMIT 1");
    echo "Tabela abuse_reports już istnieje.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."abuse_reports` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `offer_id` int(11) NOT NULL,
          `reason` varchar(50) NOT NULL,
          `description` text NULL,
          `email` varchar(128) NOT NULL,
          `created_at` datetime NOT NULL,
          `ip` varchar(45) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Utworzono tabelę abuse_reports.<br>";
    } catch (Throwable $ex) {
        echo "Błąd podczas tworzenia tabeli abuse_reports: " . $ex->getMessage() . "<br>";
    }
}

// Autocreate rate_limit table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."rate_limit` LIMIT 1");
    echo "Tabela rate_limit już istnieje.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."rate_limit` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ip` varchar(45) NOT NULL,
          `action` varchar(50) NOT NULL,
          `created_at` int(11) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_ip_action_created` (`ip`, `action`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Utworzono tabelę rate_limit.<br>";
    } catch (Throwable $ex) {
        echo "Błąd podczas tworzenia tabeli rate_limit: " . $ex->getMessage() . "<br>";
    }
}

// Autocreate verification columns in user table if not exist
try {
    $db->query("SELECT `verified_email` FROM `"._DB_PREFIX_."user` LIMIT 1");
    echo "Kolumny weryfikacji w tabeli user już istnieją.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `verified_email` tinyint(1) NOT NULL DEFAULT 0");
        echo "Dodano kolumnę verified_email.<br>";
    } catch (Throwable $ex) {
        echo "Błąd (verified_email): " . $ex->getMessage() . "<br>";
    }
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `verified_phone` tinyint(1) NOT NULL DEFAULT 0");
        echo "Dodano kolumnę verified_phone.<br>";
    } catch (Throwable $ex) {
        echo "Błąd (verified_phone): " . $ex->getMessage() . "<br>";
    }
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `verified_company` tinyint(1) NOT NULL DEFAULT 0");
        echo "Dodano kolumnę verified_company.<br>";
    } catch (Throwable $ex) {
        echo "Błąd (verified_company): " . $ex->getMessage() . "<br>";
    }
    try {
        $db->exec("UPDATE `"._DB_PREFIX_."user` SET `verified_email` = 1 WHERE `active` = 1");
        echo "Zaktualizowano status zweryfikowanych e-maili.<br>";
    } catch (Throwable $ex) {
        echo "Błąd aktualizacji statusów: " . $ex->getMessage() . "<br>";
    }
}

// Autocreate magic link columns in user table if not exist
try {
    $db->query("SELECT `magic_link_token` FROM `"._DB_PREFIX_."user` LIMIT 1");
    echo "Kolumny magic link w tabeli user już istnieją.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `magic_link_token` varchar(64) DEFAULT NULL");
        echo "Dodano kolumnę magic_link_token.<br>";
    } catch (Throwable $ex) {
        echo "Błąd (magic_link_token): " . $ex->getMessage() . "<br>";
    }
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `magic_link_expires` datetime DEFAULT NULL");
        echo "Dodano kolumnę magic_link_expires.<br>";
    } catch (Throwable $ex) {
        echo "Błąd (magic_link_expires): " . $ex->getMessage() . "<br>";
    }
}

// Ensure settings for verification badges exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'enable_verification_badges'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('enable_verification_badges', '1')");
        echo "Dodano ustawienie enable_verification_badges.<br>";
    } else {
        echo "Ustawienie enable_verification_badges już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (enable_verification_badges): " . $e->getMessage() . "<br>";
}

// Ensure settings for admin_phone exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'admin_phone'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('admin_phone', '+48 500 600 700')");
        echo "Dodano ustawienie admin_phone.<br>";
    } else {
        echo "Ustawienie admin_phone już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (admin_phone): " . $e->getMessage() . "<br>";
}

// Ensure settings for scraper_enabled exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_enabled', '1')");
        echo "Dodano ustawienie scraper_enabled.<br>";
    } else {
        echo "Ustawienie scraper_enabled już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_enabled): " . $e->getMessage() . "<br>";
}

// Ensure settings for scraper_display_days exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_display_days'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_display_days', '7')");
        echo "Dodano ustawienie scraper_display_days.<br>";
    } else {
        echo "Ustawienie scraper_display_days już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_display_days): " . $e->getMessage() . "<br>";
}

// Ensure settings for scraper_max_imports exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_max_imports'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_max_imports', '30')");
        echo "Dodano ustawienie scraper_max_imports.<br>";
    } else {
        echo "Ustawienie scraper_max_imports już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_max_imports): " . $e->getMessage() . "<br>";
}

// Ensure settings for mylomza scraper exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mylomza_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mylomza_enabled', '0')");
        echo "Dodano ustawienie scraper_mylomza_enabled.<br>";
    } else {
        echo "Ustawienie scraper_mylomza_enabled już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_mylomza_enabled): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mylomza_display_days'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mylomza_display_days', '7')");
        echo "Dodano ustawienie scraper_mylomza_display_days.<br>";
    } else {
        echo "Ustawienie scraper_mylomza_display_days już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_mylomza_display_days): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mylomza_max_imports'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mylomza_max_imports', '10')");
        echo "Dodano ustawienie scraper_mylomza_max_imports.<br>";
    } else {
        echo "Ustawienie scraper_mylomza_max_imports już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_mylomza_max_imports): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_eostroleka_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_eostroleka_enabled', '0')");
        echo "Dodano ustawienie scraper_eostroleka_enabled.<br>";
    } else {
        echo "Ustawienie scraper_eostroleka_enabled już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_eostroleka_enabled): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_eostroleka_display_days'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_eostroleka_display_days', '7')");
        echo "Dodano ustawienie scraper_eostroleka_display_days.<br>";
    } else {
        echo "Ustawienie scraper_eostroleka_display_days już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_eostroleka_display_days): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_eostroleka_max_imports'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_eostroleka_max_imports', '10')");
        echo "Dodano ustawienie scraper_eostroleka_max_imports.<br>";
    } else {
        echo "Ustawienie scraper_eostroleka_max_imports już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_eostroleka_max_imports): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mojaostroleka_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mojaostroleka_enabled', '0')");
        echo "Dodano ustawienie scraper_mojaostroleka_enabled.<br>";
    } else {
        echo "Ustawienie scraper_mojaostroleka_enabled już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_mojaostroleka_enabled): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mojaostroleka_display_days'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mojaostroleka_display_days', '7')");
        echo "Dodano ustawienie scraper_mojaostroleka_display_days.<br>";
    } else {
        echo "Ustawienie scraper_mojaostroleka_display_days już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_mojaostroleka_display_days): " . $e->getMessage() . "<br>";
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mojaostroleka_max_imports'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mojaostroleka_max_imports', '10')");
        echo "Dodano ustawienie scraper_mojaostroleka_max_imports.<br>";
    } else {
        echo "Ustawienie scraper_mojaostroleka_max_imports już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (scraper_mojaostroleka_max_imports): " . $e->getMessage() . "<br>";
}

// Ensure settings for cron timestamps exist
$cron_keys = ['cron_last_10min', 'cron_last_daily', 'cron_last_scraper', 'cron_last_scraper_mylomza', 'cron_last_scraper_eostroleka', 'cron_last_scraper_mojaostroleka'];
foreach ($cron_keys as $cron_key) {
    try {
        $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = :name");
        $sth->bindValue(':name', $cron_key, PDO::PARAM_STR);
        $sth->execute();
        if ($sth->fetchColumn() == 0) {
            $sth_ins = $db->prepare("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES (:name, '0')");
            $sth_ins->bindValue(':name', $cron_key, PDO::PARAM_STR);
            $sth_ins->execute();
            echo "Dodano ustawienie cron dla {$cron_key}.<br>";
        } else {
            echo "Ustawienie cron dla {$cron_key} już istnieje.<br>";
        }
    } catch (Throwable $e) {
        echo "Błąd (cron_key {$cron_key}): " . $e->getMessage() . "<br>";
    }
}

// Ensure settings for exclude_ip_views exists in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'exclude_ip_views'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('exclude_ip_views', '')");
        echo "Dodano ustawienie exclude_ip_views.<br>";
    } else {
        echo "Ustawienie exclude_ip_views już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (exclude_ip_views): " . $e->getMessage() . "<br>";
}

// Ensure setting for security_2fa_enabled exists in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'security_2fa_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('security_2fa_enabled', '0')");
        echo "Dodano ustawienie security_2fa_enabled.<br>";
    } else {
        echo "Ustawienie security_2fa_enabled już istnieje.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd (security_2fa_enabled): " . $e->getMessage() . "<br>";
}

// Add twofa_secret column to admin table if it doesn't exist
try {
    $db->query("SELECT `twofa_secret` FROM `"._DB_PREFIX_."admin` LIMIT 1");
    echo "Kolumna twofa_secret już istnieje w tabeli admin.<br>";
} catch (Throwable $e) {
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."admin` ADD COLUMN `twofa_secret` varchar(32) DEFAULT NULL");
        echo "Dodano kolumnę twofa_secret do tabeli admin.<br>";
    } catch (Throwable $ex) {
        echo "Błąd podczas dodawania kolumny twofa_secret: " . $ex->getMessage() . "<br>";
    }
}

// Fix existing imported eOstrołęka advertisements to correct region (Mazowieckie/Ostrołęka)
try {
    $sthState = $db->prepare("SELECT id FROM `"._DB_PREFIX_."state` WHERE name = 'Mazowieckie' LIMIT 1");
    $sthState->execute();
    $prod_state_id = $sthState->fetchColumn();

    $sthState2 = $db->prepare("SELECT id FROM `"._DB_PREFIX_."state` WHERE name = 'Ostrołęka' LIMIT 1");
    $sthState2->execute();
    $prod_state2_id = $sthState2->fetchColumn();

    if ($prod_state_id && $prod_state2_id) {
        $sthUpd = $db->prepare("UPDATE `"._DB_PREFIX_."offer` SET state_id = :state_id, state2_id = :state2_id WHERE code LIKE 'imported_eo_%'");
        $sthUpd->bindValue(':state_id', $prod_state_id, PDO::PARAM_INT);
        $sthUpd->bindValue(':state2_id', $prod_state2_id, PDO::PARAM_INT);
        $sthUpd->execute();
        echo "Zaktualizowano region dla dotychczasowych ogłoszeń eOstrołęka (Województwo ID: {$prod_state_id}, Miasto ID: {$prod_state2_id}).<br>";
    } else {
        echo "Nie znaleziono w bazie danych regionów 'Mazowieckie' lub 'Ostrołęka'.<br>";
    }
} catch (Throwable $e) {
    echo "Błąd podczas aktualizacji regionów ogłoszeń eOstrołęka: " . $e->getMessage() . "<br>";
}

echo "Migracja bazy danych zakończona sukcesem!<br>";
