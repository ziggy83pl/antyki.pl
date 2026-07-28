<?php
header('Content-Type: text/html; charset=utf-8');
header('X-XSS-Protection: 0');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once('../config/config.php');
require_once('../php/bootstrap.php');
startSecureSession();

$twig = buildTwigEnvironment(__DIR__ . '/views', true);

$admin = new \App\Admin\Admin($db);

$title = $title_default = 'Admin Panel';

$controller = 'index';
if ($admin->is_logged()) {
    $requestedController = $_GET['controller'] ?? '';
    if ($requestedController && isSlug($requestedController) && file_exists('controller/'.$requestedController.'.php')) {
        // Map controller names to permission keys
        $controllerPermMap = [
            'categories' => 'categories',
            'offers' => 'offers',
            'articles' => 'articles',
            'index_page' => 'articles',
            'login_page' => 'articles',
            'mails' => 'articles',
            'info' => 'articles',
            'logs_payments' => 'payments',
            'users' => 'users',
            'mailing' => 'communication',
            'suggestions' => 'communication',
            'opinions' => 'communication',
            'chat' => 'communication',
            'states' => 'additional_data',
            'types' => 'additional_data',
            'options' => 'additional_data',
            'logs_offers' => 'logs_and_security',
            'logs_users' => 'logs_and_security',
            'logs_mails' => 'logs_and_security',
            'logs_security' => 'logs_and_security',
            'reset_password' => 'logs_and_security',
            'settings_black_list' => 'settings',
            'settings_days' => 'settings',
            'settings_appearance' => 'settings',
            'settings_social_media' => 'settings',
            'settings_ads' => 'settings',
            'settings_payments' => 'settings',
            'settings_security' => 'settings',
            'settings' => 'settings'
        ];

        $requiredPerm = $controllerPermMap[$requestedController] ?? null;
        if ($requiredPerm && !$admin->hasPermission($requiredPerm)) {
            $controller = '404';
            $title = 'Brak uprawnień - '.$title_default;
        } else {
            $controller = $requestedController;
            $title = ucfirst($controller).' - '.$title_default;
        }
    } else {
        $title = $title_default;
    }
} else {
    $controller = 'login';
    $title = 'Logowanie - '.$title_default;
}

$render_variables = [];

class noFoundException extends Exception {}

try{
	include_once('controller/'.$controller.'.php');
}catch(noFoundException $e){
	include_once('controller/404.php');
}

echo $twig->render($controller.'.html', array_merge($render_variables, [
	'title' => $title,
	'settings' => $settings,
	'admin' => $admin->user_data,
	'admin_obj' => $admin,
	'is_superadmin' => $admin->isSuperAdmin(),
	'_ADMIN_TEST_MODE_' => _ADMIN_TEST_MODE_,
	'get' => $_GET,
	'controller' => $controller,
	'folder_admin' => basename(dirname($_SERVER['REQUEST_URI'])),
	'csrf_token' => \App\Core\Csrf::getToken()
]));
