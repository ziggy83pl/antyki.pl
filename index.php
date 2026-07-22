<?php
/**
 * Ogłoszenia Nova classifieds CMS.
 * Modernization in progress.
 */

// Zabezpieczenie wyświetlania i logowania błędów krytycznych
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (defined('_DEBUG_MODE_') && _DEBUG_MODE_) {
            echo "<div style='background:#ffdddd; color:red; padding:20px; border:2px solid red; z-index:9999; position:relative;'>";
            echo "<strong>KRYTYCZNY BŁĄD PHP:</strong><br>Plik: {$error['file']} (Linia: {$error['line']})<br>Komunikat: {$error['message']}</div>";
        } else {
            error_log("KRYTYCZNY BŁĄD PHP: Plik: {$error['file']} (Linia: {$error['line']}) | Komunikat: {$error['message']}");
            echo "<div style='background:#ffdddd; color:red; padding:20px; border:2px solid red; z-index:9999; position:relative;'>";
            echo "Wystąpił błąd serwera. Spróbuj później.</div>";
        }
    }
});

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once('config/config.php');
require_once('php/bootstrap.php');
startSecureSession();

$twig = buildTwigEnvironment('views/'.$settings['template']);
$render_variables = [];
$user = new \App\User();
/* Modified: Normalize $_GET['path'] by trimming slashes to fix Nginx/Apache differences */
if (isset($_GET['path'])) {
    $_GET['path'] = trim($_GET['path'], '/');
}
$path = normalizeRequestPath($_GET['path'] ?? '');
$pathParts = getPathParts($path);
$path_parts = $pathParts;

$controller = 'index';

class NotFoundException extends Exception {}
class_alias(NotFoundException::class, 'noFoundException');

try {
    $controller = resolveController($links, $pathParts);
    
    // Zabezpieczenie LFI (Local File Inclusion) - Biała lista dozwolonych kontrolerów
    $allowed_controllers = [
        'index', 'offers', 'offer', 'add', 'edit', 'login', 'profile', 
        'settings', 'contact', 'info', 'articles', 'article', 'chat', 
        'clipboard', 'feed', 'captcha', 'suggestions', 'my_offers', '404'
    ];
    
    if (!in_array($controller, $allowed_controllers, true)) {
        throw new NotFoundException();
    }
    
    include_once('controller/'.$controller.'.php');
} catch (NotFoundException $e) {
    $controller = '404';
    include_once('controller/404.php');
}

// --- Dynamic Geographical Ad Filtering ---
$current_state_id = 0;
$current_state2_id = 0;

if ($controller === 'offers') {
    if (!empty($render_variables['state']['id'])) {
        $current_state_id = (int)$render_variables['state']['id'];
    }
    if (!empty($render_variables['state2']['id'])) {
        $current_state2_id = (int)$render_variables['state2']['id'];
    }
} elseif ($controller === 'offer' && !empty($render_variables['offer'])) {
    if (!empty($render_variables['offer']['state_id'])) {
        $current_state_id = (int)$render_variables['offer']['state_id'];
    }
    if (!empty($render_variables['offer']['state2_id'])) {
        $current_state2_id = (int)$render_variables['offer']['state2_id'];
    }
}

// Fallback search parameters
if (!$current_state_id && !empty($_GET['state_id'])) {
    $current_state_id = (int)$_GET['state_id'];
}
if (!$current_state2_id && !empty($_GET['state2_id'])) {
    $current_state2_id = (int)$_GET['state2_id'];
}

$ad_prefixes = ['ads_1', 'ads_2', 'ads_3', 'ads_4', 'ads_side_1', 'ads_side_2'];
foreach ($ad_prefixes as $prefix) {
    if (!empty($settings[$prefix.'_active'])) {
        $location_type = $settings[$prefix.'_location_type'] ?? 'all';
        if ($location_type === 'state') {
            $target_state = (int)($settings[$prefix.'_state_id'] ?? 0);
            if ($current_state_id !== $target_state) {
                $settings[$prefix] = '';
            }
        } elseif ($location_type === 'city') {
            $target_state2 = (int)($settings[$prefix.'_state2_id'] ?? 0);
            if ($current_state2_id !== $target_state2) {
                $settings[$prefix] = '';
            }
        }
    }
}

$settings['logo_facebook'] = getFullUrl($settings['logo_facebook']);

checkInfo();



// Pseudo-cron scheduler for hostings with disabled CRON (like InfinityFree)
$now = time();
$trigger_cron_10min = false;
$trigger_cron_daily = false;
$trigger_cron_scraper = false;
$trigger_cron_scraper_mylomza = false;

if (empty($settings['cron_last_10min']) || ($now - (int)$settings['cron_last_10min']) > 600) {
    $trigger_cron_10min = true;
}
if (empty($settings['cron_last_daily']) || ($now - (int)$settings['cron_last_daily']) > 86400) {
    $trigger_cron_daily = true;
}
if (!empty($settings['scraper_enabled'])) {
    // 2 days = 172800 seconds
    if (empty($settings['cron_last_scraper']) || ($now - (int)$settings['cron_last_scraper']) > 172800) {
        $trigger_cron_scraper = true;
    }
}
if (!empty($settings['scraper_mylomza_enabled'])) {
    // 1.5 days = 129600 seconds
    if (empty($settings['cron_last_scraper_mylomza']) || ($now - (int)$settings['cron_last_scraper_mylomza']) > 129600) {
        $trigger_cron_scraper_mylomza = true;
    }
}

echo $twig->render($controller.'.html', array_merge($render_variables, [
    'settings' => $settings,
    'user' => $user->user_data,
    'controller' => $controller,
    'get' => $_GET,
    'csrf_token' => \App\Core\Csrf::getToken(),
    'canonical_url' => rtrim($settings['base_url'], '/') . '/' . $path,
    'unread_chat_count' => \App\Chat::getUnreadCount($user->logged_in ? (int)$user->id : 0),
    'trigger_cron_10min' => $trigger_cron_10min,
    'trigger_cron_daily' => $trigger_cron_daily,
    'trigger_cron_scraper' => $trigger_cron_scraper,
    'trigger_cron_scraper_mylomza' => $trigger_cron_scraper_mylomza
]));
