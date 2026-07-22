<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if (isset($_GET['id'])) {
    $_GET['id'] = (int)$_GET['id'];
}
if (isset($_POST['id'])) {
    $_POST['id'] = (int)$_POST['id'];
}

if($settings['enable_articles'] and isset($_GET['id']) and $_GET['id']>0 and isset($_GET['slug']) and $_GET['slug']!=''){

  $article = \App\Article::getArticle((int)$_GET['id']);
	if($article){
		if($_GET['slug']!=$article['slug']){
			header("Location: ".path('article', $article['id'], $article['slug']));
			die('redirect');
		}else{
			$render_variables['article'] = $article;
			$settings['seo_title'] = $article['name'].' - '.$settings['title'];
			if($article['description']){
				$settings['seo_description'] = $article['description'];
			}else{
				$settings['seo_description'] = $article['name'].' - '.$settings['description'];
			}
			if($article['keywords']){
				$settings['seo_keywords'] = $article['keywords'];
			}
			if($article['thumb']){$settings['logo_facebook'] = $article['thumb'];}
		}
	}else{
		throw new noFoundException();
	}

}else{
	throw new noFoundException();
}
