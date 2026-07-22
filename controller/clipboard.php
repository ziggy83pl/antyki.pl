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

if(!empty($path_parts[1])){
	throw new noFoundException();
}

if($user->logged_in){

	if(isset($_POST['action']) and $_POST['action']=='clipboard_remove' and isset($_POST['id']) and $_POST['id']>0 and checkToken('clipboard_remove')){
		\App\Offer::clipboardRemove($_POST['id']);
	}

	$render_variables ['offers'] = \App\Offer::loadOffers($settings['limit_page'],'clipboard');

	$settings['seo_title'] = lang('Clipboard').' - '.$settings['title'];
	$settings['seo_description'] = lang('Clipboard').' - '.$settings['description'];
	
}else{
	$render_variables ['offers'] = [];
	$settings['seo_title'] = lang('Clipboard').' - '.$settings['title'];
	$settings['seo_description'] = lang('Clipboard').' - '.$settings['description'];
}
