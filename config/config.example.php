<?php

// Set the demonstration mode Admin Panel true/false
define('_ADMIN_TEST_MODE_', false);

// Debug Mode (show all error) true/false
define('_DEBUG_MODE_', false);

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

$settings['assets_version'] = 32;

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
define('_PREFIX_STATE_', 's-'); // default is 's-'
define('_PREFIX_TYPE_', 't-'); // default is 't-'

define('_FOLDER_PHOTOS_', realpath(dirname(__FILE__)).'/../upload/photos/');
define('_FOLDER_AVATARS_', realpath(dirname(__FILE__)).'/../upload/avatars/');
