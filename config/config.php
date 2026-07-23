<?php
require_once __DIR__ . "/../vendor/autoload.php";
// Set the demonstration mode Admin Panel true/false
define('_ADMIN_TEST_MODE_', false);

// Debug Mode (show all error) true/false
define('_DEBUG_MODE_', true);

date_default_timezone_set('Europe/Warsaw');

require_once __DIR__ . '/db.php';

try{
  $db = new PDO('mysql:host='.$mysql_server.';dbname='.$mysql_db, $mysql_user, $mysql_pass, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]);
}
catch (PDOException $e){
  die ("Error connecting to database!");
}

// Ensure missing user table columns exist
try {
  $cols = $db->query("SHOW COLUMNS FROM `"._DB_PREFIX_."user`")->fetchAll(PDO::FETCH_COLUMN);
  $needed_cols = [
    'url_website' => "VARCHAR(255) DEFAULT ''",
    'url_facebook' => "VARCHAR(255) DEFAULT ''",
    'url_linkedin' => "VARCHAR(255) DEFAULT ''",
    'url_youtube' => "VARCHAR(255) DEFAULT ''",
    'company_name' => "VARCHAR(255) DEFAULT ''",
    'company_nip' => "VARCHAR(32) DEFAULT ''",
    'verified_email' => "TINYINT(1) DEFAULT 0",
    'verified_phone' => "TINYINT(1) DEFAULT 0",
    'verified_company' => "TINYINT(1) DEFAULT 0"
  ];
  foreach ($needed_cols as $colName => $colDef) {
    if (!in_array($colName, $cols)) {
      $db->exec("ALTER TABLE `"._DB_PREFIX_."user` ADD `$colName` $colDef");
    }
  }
} catch (Throwable $e) {}

function getSettings(){
  global $db, $settings;
  $sth = $db->query('SELECT * FROM '._DB_PREFIX_.'settings');
  if(!$sth){die('Error! Incorrect database');}
  $settings = [];
  foreach($sth as $row){
    $settings[$row['name']] = $row['value'];
  }
  if (class_exists('\App\Core\App')) {
    \App\Core\App::setSettings($settings);
  }
}
getSettings();

if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $settings['base_url'] = $protocol . "://" . $_SERVER['HTTP_HOST'];
} else {
    // CLI fallback or default
    $settings['base_url'] = 'https://antyki.pl';
}

\App\Core\App::setDb($db);
\App\Core\App::setSettings($settings);

// Dynamic assets version to automatically bust cache when css/js files are modified
$viewsDir = __DIR__ . '/../views/'.($settings['template'] ?? 'default');
$maxMtime = 1719700000; // Baseline timestamp
foreach (['/js/engine.js', '/js/add.js', '/css/style.css'] as $file) {
    if (file_exists($viewsDir . $file)) {
        $maxMtime = max($maxMtime, filemtime($viewsDir . $file));
    }
}
$settings['assets_version'] = $maxMtime;

$today = date('Y-m-d');

function normalizeBrandingText(string $value): string{
  $legacyProject = 'NOTICE' . '2';
  $legacyAuthor = implode(' ', ['Zbyszek', 'S']);
  return str_replace(
    [$legacyProject, 'Giełda Budowlana', 'Giełda Budowlana 2.0', 'Giełda Budowlana 2.0', 'Zbyszek .S', 'Projekt 2026 by Zbyszek .S', 'The script of website with announcements '.$legacyProject, 'Strona pokazowa skryptu ogłoszeń '.$legacyProject, 'Gie?da Budowlana'],
    ['Giełda Antyków i Militariów', 'Giełda Antyków i Militariów', 'Giełda Antyków i Militariów', 'Giełda Antyków i Militariów', 'Zbyszek .S', 'Projekt 2026 by Zbyszek .S', 'Giełda Antyków i Militariów', 'Giełda Antyków i Militariów', 'Giełda Antyków i Militariów'],
    $value
  );
}

foreach ($settings as $key => $value) {
  if (is_string($value)) {
    $settings[$key] = normalizeBrandingText($value);
  }
}
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
  ini_set("error_log", __DIR__ . '/../tmp/php-error.log');
  error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

define('_OFFERS_PATH_', true); // default is true
define('_PREFIX_CATEGORY_', 'c-'); // default is 'c-'
define('_PREFIX_STATE_', 'region-'); // default is 's-'
define('_PREFIX_TYPE_', 't-'); // default is 't-'

define('_FOLDER_PHOTOS_', __DIR__ . '/../upload/photos/');
define('_FOLDER_AVATARS_', __DIR__ . '/../upload/avatars/');
