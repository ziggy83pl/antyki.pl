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
if (isset($_GET['category_id'])) {
    $_GET['category_id'] = (int)$_GET['category_id'];
}

if($admin->is_logged()){

	$category_id = 0;
	if(isset($_GET['category_id']) and $_GET['category_id']>0){
		$category = \App\Category::getCategory((int)$_GET['category_id'], true);
		if($category){
			$render_variables['category'] = $category;
			$category_id = $category['id'];
		}
	}
	
	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='add_category' and !empty($_POST['name']) and checkToken('admin_add_category')){

			\App\Category::add($_POST, (int)$category_id);
			$render_variables['alert_success'][] = lang('Successfully added new category').' '.strip_tags($_POST['name']);

		}elseif($_POST['action']=='edit_category' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_category')){
			
			\App\Category::edit($_POST, (int)$_POST['id']);
			$render_variables['alert_success'][] = lang('Changes have been saved');
			
		}elseif($_POST['action']=='remove_category' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_category')){
			
			\App\Category::remove((int)$_POST['id']);
			$render_variables['alert_danger'][] = lang('Successfully deleted');
			
		}elseif($_POST['action']=='reload_category' and isset($_POST['category']) and $_POST['category']>=0 and checkToken('admin_reload_category')){
			
			\App\Category::refreshAllSubcategories((int)$_POST['category']);
			$render_variables['alert_success'][] = lang('Categories have been reloaded');
			
		}
	}

	$render_variables['categories'] = \App\Category::getCategories((int)$category_id);
	
	$title = lang('Categories').' - '.$title_default;
}
