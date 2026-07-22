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
if (isset($_GET['state_id'])) {
    $_GET['state_id'] = (int)$_GET['state_id'];
}

if($admin->is_logged()){
	
	if(isset($_GET['state_id']) and $_GET['state_id']>0 and is_numeric($_GET['state_id'])){
		$state = getStateById($_GET['state_id']);
		$render_variables['state'] = $state;
		$state_id = $state['id'];
	}else{
		$state_id = 0;
	}
	
	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='add_state' and !empty($_POST['name']) and checkToken('admin_add_state')){
			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'state`(`state_id`, `position`, `slug`, `name`) VALUES (:state_id,:position,:slug,:name)');
			$sth->bindValue(':state_id', $state_id, PDO::PARAM_INT);
			$sth->bindValue(':position', getPosition('state', ' state_id='.$state_id.' '), PDO::PARAM_INT);
			$sth->bindValue(':slug', slug($_POST['name']), PDO::PARAM_STR);
			$sth->bindValue(':name', trim($_POST['name']), PDO::PARAM_STR);
			$sth->execute();
			$render_variables['alert_success'][] = lang('Successfully added new state').' '.strip_tags($_POST['name']);
		}elseif($_POST['action']=='edit_state' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_state')){
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'state` SET slug=:slug, name=:name WHERE id=:id limit 1');
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->bindValue(':slug', slug($_POST['name']), PDO::PARAM_STR);
			$sth->bindValue(':name', trim($_POST['name']), PDO::PARAM_STR);
			$sth->execute();
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='remove_state' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_state')){
			$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'state` WHERE state_id=:id');
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->execute();
			$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'state` WHERE id=:id limit 1');
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->execute();
			$render_variables['alert_danger'][] = lang('Successfully deleted');
		}
	}

	$render_variables['states'] = getStates($state_id);
	
	$title = lang('States').' - '.$title_default;
	
}
