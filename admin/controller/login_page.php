<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='save_login_page' and isset($_POST['login_page']) and checkToken('admin_save_login_page')){
			global $purifier;
			if ($purifier) {
				$_POST['login_page'] = $purifier->purify($_POST['login_page']);
			}
			\App\Settings::save('login_page');
			$render_variables['alert_success'][] = lang('Changes have been saved');
			getSettings();
		}
	}

	$title = lang('Login page').' - '.$title_default;

}
