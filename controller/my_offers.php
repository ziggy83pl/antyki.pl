<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(!empty($path_parts[1])){
	throw new noFoundException();
}

if($user->logged_in){

	if(isset($_POST['action'])){
		if($_POST['action']=='finish_offer' and isset($_POST['id']) and $_POST['id']>=0 and checkToken('finish_offer')){
			if(\App\Offer::checkPermissions($_POST['id'])){
				\App\Offer::deactivate($_POST['id']);
			}
		}elseif($_POST['action']=='remove_offer' and isset($_POST['id']) and $_POST['id']>0 and checkToken('remove_offer')){
			if(\App\Offer::checkPermissions($_POST['id'])){
				\App\Offer::remove($_POST['id']);
			}
		}elseif($_POST['action']=='refresh_offer' and isset($_POST['id']) and $_POST['id']>=0 and $settings['allow_refresh_offer'] and checkToken('refresh_offer')){
			if(\App\Offer::checkPermissions($_POST['id']) and \App\Offer::countCost($_POST['id'])['total']==0){
				\App\Offer::refresh($_POST['id']);
			}
		}
	}

	$render_variables['offers'] = \App\Offer::loadOffers($settings['limit_page'],'my_offers');

	$settings['seo_title'] = lang('My offers').' - '.$settings['title'];
	$settings['seo_description'] = lang('My offers').' - '.$settings['description'];
	
}else{
	header("Location: ".path('login')."?redirect=".path('my_offers'));
	die('redirect');
}
