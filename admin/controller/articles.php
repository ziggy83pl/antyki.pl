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
		if($_POST['action']=='remove_article' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_article')){
		  \App\Article::remove((int)$_POST['id']);
			$render_variables['alert_danger'][] = lang('Successfully deleted');
		}
		if($_POST['action']=='bulk_delete' and !empty($_POST['ids']) and is_array($_POST['ids']) and checkToken('admin_bulk_delete_articles')){
			$ids = array_map('intval', $_POST['ids']);
			if(!empty($ids)){
				$in = str_repeat('?,', count($ids) - 1) . '?';
				$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'article WHERE id IN ('.$in.')');
				$sth->execute($ids);
				$render_variables['alert_danger'][] = lang('Successfully deleted');
			}
		}
	}

	$render_variables['articles'] = \App\Article::getArticles(100);

	$title = lang('Articles').' - '.$title_default;

}
