<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if (isset($_GET['id'])) {
    $_GET['id'] = (int)$_GET['id'];
}
if (isset($_POST['id'])) {
    $_POST['id'] = (int)$_POST['id'];
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='remove_option' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_option')){
			\App\Option::remove($_POST['id']);
			$render_variables['alert_danger'][] = lang('Successfully deleted');
		}
	}

	$render_variables['options'] = \App\Option::getOptions();
	
	$title = lang('Options').' - '.$title_default;
}

