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

if(isset($_GET['activate']) and !empty($_GET['code'])){
	\App\Offer::activateCode($_GET['code']);
}

if(!empty($_GET['code'])){$code = $_GET['code'];}else{$code = '';}

if(isset($_GET['id']) and $_GET['id']>0 and \App\Offer::checkActiveOffer($_GET['id'],$code)){

	if(isset($_GET['status'])){
		if($_GET['status']=='OK'){
			$render_variables['alert_success'][] = lang('Payment correct');
		}elseif($_GET['status']=='FAIL'){
			$render_variables['alert_danger'][] = lang('Payment error. Please contact with administrator');
		}
	}

	if($settings['show_contact_form_offer'] and isset($_POST['action']) and $_POST['action']=='send_message' and !empty($_POST['name']) and (!empty($_POST['email']) or $user->getId()) and !empty($_POST['message']) and !empty($_POST['captcha']) and (isset($_POST['rules']) or $user->getId())){

		$offer = \App\Offer::loadOffer($_GET['id']);

		if(!checkToken('send_message')){
			$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
		}elseif($_POST['captcha']!=$_SESSION['captcha']){
			$error['captcha'] = lang('Invalid captcha code.');
		}elseif(!$user->getId() and !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$error['email'] = lang('Incorrect e-mail address');
		}

		if(isset($error)){
			$render_variables['error'] = $error;
			$render_variables['alert_danger'][] = lang('The message was not sent');
			$render_variables['input'] = ['name'=>$_POST['name'], 'email'=>$_POST['email'], 'message'=>$_POST['message']];
		}else{
			if($user->getId()){
				$email = $user->email;
			}else{
				$email = $_POST['email'];
			}
			if(sendMail('offer',$offer['email'],['name'=>$_POST['name'], 'email'=>$email, 'message'=>$_POST['message'], 'offer_name'=>$offer['name'], 'offer_url'=>path('offer',$offer['id'],$offer['slug']), 'user_id'=>$user->getId()])){
				$render_variables['alert_success'][] = lang('The message was correctly sent');
			}else{
				$render_variables['error'] = $error;
				$render_variables['alert_danger'][] = lang('The message was not sent');
				$render_variables['input'] = ['name'=>$_POST['name'], 'email'=>$_POST['email'], 'message'=>$_POST['message']];
			}
		}
		$render_variables['showContactTab'] = true;
	}elseif(isset($_POST['action']) and $_POST['action']=='clipboard_add' and checkToken('clipboard_add')){
		if($user->getId()){
			\App\Offer::clipboardAdd($_GET['id']);
			$render_variables['alert_success'][] = lang('Offer added to clipboard');
		}else{
			$render_variables['alert_danger'][] = lang('You must be logged in to post ad to clipboard');
		}
	}elseif(isset($_POST['action']) and $_POST['action']=='clipboard_remove' and checkToken('clipboard_remove')){
		\App\Offer::clipboardRemove($_GET['id']);
		$render_variables['alert_success'][] = lang('Offer remove from clipboard');

	}elseif(isset($_POST['action']) and $_POST['action']=='activate_offer' and \App\Offer::countCost($_GET['id'])['total']==0 and checkToken('activate_offer')){
		\App\Offer::activate($_GET['id']);
		$render_variables['alert_success'][] = lang('The offer has been correctly activated on the site');
	}

	$offer = \App\Offer::loadOffer($_GET['id'], 'offer');

	if($_GET['slug']!=$offer['slug']){
		header("Location: ".path('offer', $offer['id'], $offer['slug']));
		die('redirect');
	}

	if($settings['show_similar_offer']){
		$render_variables['offers'] = \App\Offer::loadSimilarOffers($offer,$settings['limit_similar_offer']);
	}

	$offer['email_part_0'] = '';
	$offer['email_part_1'] = '';
	if (!empty($offer['email']) && strpos($offer['email'], '@') !== false) {
		$email_parts = explode('@', $offer['email']);
		$offer['email_part_0'] = $email_parts[0] ?? '';
		$offer['email_part_1'] = $email_parts[1] ?? '';
	}

	if(!$offer['active']){
		$render_variables['offer_cost'] = \App\Offer::countCost($offer['id']);
	}

	if(isset($offer['photos'])){
		$settings['logo_facebook'] = $settings['base_url'].'/upload/photos/'.$offer['photos'][0]['url'];
	}

	$location_parts = [];
	if (!empty($offer['state_name'])) $location_parts[] = $offer['state_name'];
	if (!empty($offer['state2_name'])) $location_parts[] = $offer['state2_name'];
	$location_suffix = $location_parts ? ' - ' . implode(', ', $location_parts) : '';
	$settings['seo_title'] = $offer['name'] . $location_suffix . " - " . $settings['title'];
	$settings['seo_description'] = substr(trim(preg_replace('/\s\s+/', ' ', strip_tags($offer['description']))),0,200);

	$render_variables['offer'] = $offer;

}else{
	throw new noFoundException();
}
