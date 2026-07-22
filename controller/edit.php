<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

$controller = 'add';
include_once('controller/'.$controller.'.php');
	
if(!empty($_GET['code'])){$code = $_GET['code'];}else{$code = '';}

if(isset($_GET['id']) and $_GET['id']>0 and \App\Offer::checkPermissions($_GET['id'],$code)){
	$render_variables['offer'] = \App\Offer::loadOffer($_GET['id'], 'edit');
	$settings['seo_title'] = lang('Edit offer').' - '.$settings['title'];
	$settings['seo_description'] = lang('Edit offer').' - '.$settings['description'];
}else{
	header("Location: ".path('add'));
	die('redirect');
}
