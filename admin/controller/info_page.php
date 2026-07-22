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
		if($_POST['action']=='add_info' and !empty($_POST['name']) and checkToken('admin_add_info')){
		  \App\Info::add($_POST);
			header('Location: ?controller=info');
			die('redirect');
		}elseif($_POST['action']=='edit_info' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_info')){
      \App\Info::edit($_POST['id'],$_POST);
			header('Location: ?controller=info');
			die('redirect');
		}
	}

	if(isset($_GET['id']) and $_GET['id']>0){
		$info_page = \App\Info::getInfoById($_GET['id']);
		if($info_page!=''){
			$title = $info_page['name'].' - '.lang('Info');
			$render_variables['info_page'] = $info_page;
		}
	}

	$title = lang('Info').' - '.$title_default;
}
