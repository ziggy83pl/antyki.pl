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
		if($_POST['action']=='add_article' and !empty($_POST['name']) and checkToken('admin_add_article')){
      $id = \App\Article::add($_POST);
			header('Location: ?controller=article&id='.$id);
			die('redirect');
		}elseif($_POST['action']=='edit_article' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_article')){
			\App\Article::edit((int)$_POST['id'],$_POST);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}
	}

	if(isset($_GET['id']) and $_GET['id']>0){
		$article = \App\Article::getArticle((int)$_GET['id']);
		if($article!=''){
			$title = $article['name'].' - '.lang('Article');
			$render_variables['article'] = $article;
		}
	}

}
