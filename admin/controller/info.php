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

	if(isset($_POST['action'])){
		if(!_ADMIN_TEST_MODE_ and $_POST['action']=='remove_info' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_info')){
		  \App\Info::remove($_POST['id']);
			$render_variables['alert_danger'][] = lang('Successfully deleted');
		}
	}

  $render_variables['info'] = \App\Info::getInfos();

}
