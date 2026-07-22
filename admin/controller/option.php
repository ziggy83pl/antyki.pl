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
		if($_POST['action']=='add_option' and !empty($_POST['name']) and isset($_POST['kind']) and checkToken('admin_add_option')){
			\App\Option::add($_POST);
			header('Location: ?controller=options');
		}elseif($_POST['action']=='edit_option' and isset($_POST['id']) and $_POST['id']>0 and isset($_POST['kind']) and (!empty($_POST['name']) or $_POST['kind']=='pernament') and checkToken('admin_edit_option')){
			\App\Option::edit($_POST['id'],$_POST);
			header('Location: ?controller=options');
		}
	}

	if(isset($_GET['id']) and $_GET['id']>0){

		$render_variables['option'] = \App\Option::getOption($_GET['id']);

	}

	$render_variables['option_kinds'] = \App\Option::getKinds();

	$render_variables['categories'] = \App\Category::getAllCategories();

	$title = lang('Option').' - '.$title_default;

}
