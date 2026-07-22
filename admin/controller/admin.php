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

		if($_POST['action'] == 'admin_change_user' and !empty($_POST['new_username']) and !empty($_POST['new_password']) and !empty($_POST['repeat_new_password']) and checkToken('admin_change_user')){

			try{
				$admin->changeUser($_POST);
				$render_variables['alert_success'][] = lang('The data have been updated');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
			}

		}elseif($_POST['action'] == 'admin_remove_logs' and checkToken('admin_remove_logs')){

			$admin->removeLogs();
			$render_variables['alert_success'][] = lang('Logs logon to the Admin Panel has been successfully removed');

		}elseif($_POST['action'] == 'admin_add_user' and !empty($_POST['username']) and !empty($_POST['password']) and !empty($_POST['repeat_password']) and checkToken('admin_add_user')){

			try{
				$admin->addUser($_POST);
				$render_variables['alert_success'][] = lang('Added new user');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
			}

		}elseif($_POST['action'] == 'admin_remove_user' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_user')){

			try{
				$admin->removeUser($_POST['id']);
				$render_variables['alert_success'][] = lang('User has been successfully removed');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
			}

		}elseif($_POST['action'] == 'admin_logout_all' and checkToken('admin_logout_all')){

			$admin->logOutAll();

		}

	}

	$render_variables['admin_users'] = $admin->getUsers();
	$render_variables['admin_logs'] = $admin->getLogs();
	
	$title = lang('Admin Panel Settings').' - '.$title_default;

}
