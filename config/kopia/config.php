<?php
require_once(realpath(dirname(__FILE__))."/../vendor/autoload.php");
// Set the demonstration mode Admin Panel true/false
define('_ADMIN_TEST_MODE_', false);

// Debug Mode (show all error) true/false
define('_DEBUG_MODE_', true);

date_default_timezone_set('Europe/Warsaw');

require_once(realpath(dirname(__FILE__)).'/db.php');

try{
  $db = new PDO('mysql:host='.$mysql_server.';dbname='.$mysql_db, $mysql_user, $mysql_pass, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]);
}
catch (PDOException $e){
  die ("Error connecting to database!");
}

function getSettings(){
  global $db, $settings;
  $sth = $db->query('SELECT * FROM '._DB_PREFIX_.'settings');
  if(!$sth){die('Error! Incorrect database');}
  foreach($sth as $row){
    $settings[$row['name']] = $row['value'];
  }
}
getSettings();

global $is_local;
if (!$is_local && php_sapi_name() !== 'cli') {
    // Wymuś nowy adres na produkcji, by zapobiec generowaniu linków do starej domeny (freehosting.dev)
    $settings['base_url'] = 'https://gieldabudowlana.xo.je';
} elseif ($is_local && isset($_SERVER['HTTP_HOST'])) {
    // W środowisku lokalnym używaj aktualnego hosta
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $settings['base_url'] = $protocol . "://" . $_SERVER['HTTP_HOST'];
}

\App\Core\App::setDb($db);
\App\Core\App::setSettings($settings);

// Dynamic assets version to automatically bust cache when css/js files are modified
$viewsDir = realpath(dirname(__FILE__)).'/../views/'.($settings['template'] ?? 'default');
$maxMtime = 1719700000; // Baseline timestamp
foreach (['/js/engine.js', '/js/add.js', '/css/style.css'] as $file) {
    if (file_exists($viewsDir . $file)) {
        $maxMtime = max($maxMtime, filemtime($viewsDir . $file));
    }
}
$settings['assets_version'] = $maxMtime;

// Temporarily set PDO error mode to exception to guarantee migrations run on all environments
$original_error_mode = $db->getAttribute(PDO::ATTR_ERRMODE);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Autocreate alerts table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."alerts` LIMIT 1");
} catch (Throwable $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."alerts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `email` varchar(128) NOT NULL,
          `category_id` int(11) NOT NULL,
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (Throwable $ex) {}
}

// Autocreate abuse_reports table if not exists
try {
    $db->query("SELECT 1 FROM `"._DB_PREFIX_."abuse_reports` LIMIT 1");
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
    } catch (Throwable $ex) {}
}

// Autocreate verification columns in user table if not exist
try {
    $db->query("SELECT `verified_email` FROM `"._DB_PREFIX_."user` LIMIT 1");
} catch (Throwable $e) {
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `verified_email` tinyint(1) NOT NULL DEFAULT 0");
    } catch (Throwable $ex) {
        if (defined('_DEBUG_MODE_') && _DEBUG_MODE_) {
            error_log("Migration error (verified_email): " . $ex->getMessage());
        }
    }
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `verified_phone` tinyint(1) NOT NULL DEFAULT 0");
    } catch (Throwable $ex) {
        if (defined('_DEBUG_MODE_') && _DEBUG_MODE_) {
            error_log("Migration error (verified_phone): " . $ex->getMessage());
        }
    }
    try {
        $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD COLUMN `verified_company` tinyint(1) NOT NULL DEFAULT 0");
    } catch (Throwable $ex) {
        if (defined('_DEBUG_MODE_') && _DEBUG_MODE_) {
            error_log("Migration error (verified_company): " . $ex->getMessage());
        }
    }
    try {
        $db->exec("UPDATE `"._DB_PREFIX_."user` SET `verified_email` = 1 WHERE `active` = 1");
    } catch (Throwable $ex) {
        if (defined('_DEBUG_MODE_') && _DEBUG_MODE_) {
            error_log("Migration error (verified_email update): " . $ex->getMessage());
        }
    }
}

// Ensure settings for verification badges exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'enable_verification_badges'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('enable_verification_badges', '1')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Ensure settings for admin_phone exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'admin_phone'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('admin_phone', '+48 500 600 700')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Ensure settings for scraper_enabled exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_enabled', '1')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Ensure settings for scraper_display_days exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_display_days'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_display_days', '7')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Ensure settings for scraper_max_imports exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_max_imports'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_max_imports', '30')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Ensure settings for mylomza scraper exist in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mylomza_enabled'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mylomza_enabled', '0')");
    }
} catch (Throwable $e) {
    // Fail silently
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mylomza_display_days'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mylomza_display_days', '7')");
    }
} catch (Throwable $e) {
    // Fail silently
}

try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'scraper_mylomza_max_imports'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('scraper_mylomza_max_imports', '10')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Ensure settings for cron timestamps exist
$cron_keys = ['cron_last_10min', 'cron_last_daily', 'cron_last_scraper', 'cron_last_scraper_mylomza'];
foreach ($cron_keys as $cron_key) {
    try {
        $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = :name");
        $sth->bindValue(':name', $cron_key, PDO::PARAM_STR);
        $sth->execute();
        if ($sth->fetchColumn() == 0) {
            $sth_ins = $db->prepare("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES (:name, '0')");
            $sth_ins->bindValue(':name', $cron_key, PDO::PARAM_STR);
            $sth_ins->execute();
        }
    } catch (Throwable $e) {
        // Fail silently
    }
}

// Ensure setting for exclude_ip_views exists in settings table
try {
    $sth = $db->prepare("SELECT COUNT(1) FROM `"._DB_PREFIX_."settings` WHERE name = 'exclude_ip_views'");
    $sth->execute();
    if ($sth->fetchColumn() == 0) {
        $db->query("INSERT INTO `"._DB_PREFIX_."settings` (name, value) VALUES ('exclude_ip_views', '')");
    }
} catch (Throwable $e) {
    // Fail silently
}

// Restore original PDO error mode
$db->setAttribute(PDO::ATTR_ERRMODE, $original_error_mode);

$today = date('Y-m-d');

function normalizeBrandingText(string $value): string{
  $legacyProject = 'NOTICE' . '2';
  $legacyAuthor = implode(' ', ['Zbyszek', 'S']);
  return str_replace(
    [$legacyProject, 'Giełda Budowlana', 'Giełda Budowlana 2.0', 'Giełda Budowlana 2.0', 'Zbyszek .S', 'Projekt 2026 by Zbyszek .S', 'The script of website with announcements '.$legacyProject, 'Strona pokazowa skryptu ogłoszeń '.$legacyProject],
    ['Giełda Budowlana', 'Giełda Budowlana', 'Giełda Budowlana 2.0', 'Giełda Budowlana 2.0', 'Zbyszek .S', 'Projekt 2026 by Zbyszek .S', 'Giełda Budowlana', 'Giełda Budowlana'],
    $value
  );
}

foreach ($settings as $key => $value) {
  if (is_string($value)) {
    $settings[$key] = normalizeBrandingText($value);
  }
}

$settings['assets_version'] = 67;

require_once(realpath(dirname(__FILE__)).'/../vendor/autoload.php');
require_once(realpath(dirname(__FILE__)).'/htmlpurifier.php');

langLoad($settings['lang']);

$ad_prefixes = ['ads_1', 'ads_2', 'ads_3', 'ads_4', 'ads_side_1', 'ads_side_2'];
foreach ($ad_prefixes as $prefix) {
    $settings[$prefix] = ''; 
    $isActive = !empty($settings[$prefix.'_active']);
    $hasImage = !empty($settings[$prefix.'_image']);
    $start = $settings[$prefix.'_start'] ?? '';
    $end = $settings[$prefix.'_end'] ?? '';
    $isCurrentDate = (($start == '' || $start <= $today) && ($end == '' || $end >= $today));

    if ($isActive) {
        if ($hasImage && $isCurrentDate) {
            $url = $settings[$prefix.'_url'] ?? '#';
            $img = rtrim($settings['base_url'], '/') . '/upload/ads/' . $settings[$prefix.'_image'];
            $settings[$prefix] = '<a href="'.htmlspecialchars($url).'" target="_blank" rel="noopener"><img src="'.htmlspecialchars($img).'" alt="Reklama" class="img-fluid"></a>';
        } else {
            // Render CSS placeholder banner!
            $settingsPage = path('settings') . '?order_ad=1&slot=' . $prefix;
            $colorClasses = [
                'ads_1' => 'ad-placeholder-blue',
                'ads_2' => 'ad-placeholder-purple',
                'ads_3' => 'ad-placeholder-orange',
                'ads_4' => 'ad-placeholder-green',
                'ads_side_1' => 'ad-placeholder-teal',
                'ads_side_2' => 'ad-placeholder-blue',
            ];
            $colorClass = $colorClasses[$prefix] ?? 'ad-placeholder-blue';

            if (in_array($prefix, ['ads_1', 'ads_2', 'ads_3', 'ads_4'])) {
                // Leaderboard
                $settings[$prefix] = '
                <a href="' . htmlspecialchars($settingsPage) . '" class="ad-banner-placeholder ad-leaderboard ' . $colorClass . '">
                    <div class="ad-placeholder-text">
                        <span class="ad-badge">' . lang('Ad space') . '</span>
                        <div class="ad-text-group">
                            <h3 class="ad-title">' . lang('Place for your advertisement') . '</h3>
                            <p class="ad-subtitle">' . lang('Increase the visibility of your business! Order a banner here.') . '</p>
                        </div>
                    </div>
                </a>';
            } elseif ($prefix === 'ads_side_1') {
                // Rectangle
                $settings[$prefix] = '
                <a href="' . htmlspecialchars($settingsPage) . '" class="ad-banner-placeholder ad-rectangle ' . $colorClass . '">
                    <span class="ad-badge">' . lang('Ad space') . '</span>
                    <h3 class="ad-title">' . lang('Place for your advertisement') . '</h3>
                    <p class="ad-subtitle">' . lang('Increase the visibility of your business! Order a banner here.') . '</p>
                </a>';
            } else {
                // Skyscraper for ads_side_2
                $settings[$prefix] = '
                <a href="' . htmlspecialchars($settingsPage) . '" class="ad-banner-placeholder ad-skyscraper ' . $colorClass . '">
                    <span class="ad-badge">' . lang('Ad space') . '</span>
                    <h3 class="ad-title">' . lang('Your ad here') . '</h3>
                    <p class="ad-subtitle">' . lang('Increase the visibility of your business! Order a banner here.') . '</p>
                </a>';
            }
        }
    }

    // Wrap active and placeholder ads with floating identifier badges (A-F)
    if (!empty($settings[$prefix])) {
        $letterMap = [
            'ads_1' => 'A',
            'ads_2' => 'B',
            'ads_3' => 'C',
            'ads_4' => 'D',
            'ads_side_1' => 'E',
            'ads_side_2' => 'F'
        ];
        $letter = $letterMap[$prefix] ?? '';
        $settings[$prefix] = '<div class="ad-wrapper-identifiable position-relative d-inline-block w-100" data-ad-letter="' . $letter . '">' . $settings[$prefix] . '<span class="ad-floating-badge badge-color-' . $prefix . '">' . $letter . '</span></div>';
    }
}


if(_DEBUG_MODE_){
  ini_set("display_errors", "1");
  error_reporting(E_ALL);
}else{
  /* Modified: Secure error logging in production */
  ini_set("display_errors", "0");
  ini_set("log_errors", "1");
  ini_set("error_log", realpath(dirname(__FILE__)).'/../tmp/php-error.log');
  error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

define('_OFFERS_PATH_', true); // default is true
define('_PREFIX_CATEGORY_', 'c-'); // default is 'c-'
define('_PREFIX_STATE_', 'region-'); // default is 's-'
define('_PREFIX_TYPE_', 't-'); // default is 't-'

define('_FOLDER_PHOTOS_', realpath(dirname(__FILE__)).'/../upload/photos/');
define('_FOLDER_AVATARS_', realpath(dirname(__FILE__)).'/../upload/avatars/');
