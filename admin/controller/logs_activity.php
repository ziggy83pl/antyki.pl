<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if ($admin->is_logged()) {

	if (!$admin->hasPermission('logs_and_security')) {
		header('Location: index.php');
		die('Access denied');
	}

	$render_variables['activity_logs'] = $admin->getActivityLogs(100);
	$title = 'Dziennik Akcji Moderatorów - '.$title_default;

}
