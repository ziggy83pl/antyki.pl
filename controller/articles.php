<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(!empty($path_parts[1])){
	throw new noFoundException();
}

if($settings['enable_articles']){
	$render_variables['articles'] = \App\Article::getArticles(10);
	$settings['seo_title'] = lang('Articles').' - '.$settings['title'];
	$settings['seo_description'] = lang('Articles').' - '.$settings['description'];
}else{
	throw new noFoundException();
}
