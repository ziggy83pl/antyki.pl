<?php
/************************************************************************

 * 
 * *********************************************************************
 * THIS SOFTWARE IS LICENSED - YOU CAN MODIFY THESE FILES
 * BUT YOU CAN NOT REMOVE OF ORIGINAL COMMENTS!
 * ACCORDING TO THE LICENSE YOU CAN USE THE SCRIPT ON ONE DOMAIN. DETECTION
 * COPY SCRIPT WILL RESULT IN A HIGH FINANSIAL PENALTY AND WITHDRAWAL
 * LICENSE THE SCRIPT
 * *********************************************************************/

/* Modified: Secure session and cookie options */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', '1');
}

session_start(); 

/* Modified: Session timeout (30 minutes) */
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

require_once('../../config/config.php');

$admin = new \App\Admin\Admin($db);

if(!_ADMIN_TEST_MODE_ and $admin->is_logged() and isset($_POST['data'])){
	$post = $_POST['data'];
	if (is_string($post)) {
		$post = json_decode($post, true);
	}
	if($post['action']=='activate_user' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_activate_user', $post['token'])){
		\App\User::activate((int)$post['id']);
	}elseif($post['action']=='activate_offer' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_activate_offer', $post['token'])){
		\App\Offer::activate((int)$post['id']);
	}elseif($post['action']=='deactivate_offer' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_deactivate_offer', $post['token'])){
		\App\Offer::deactivate((int)$post['id']);
	}elseif($post['action']=='enable_promote_offer' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_enable_promote_offer', $post['token'])){
		\App\Offer::enablePromote((int)$post['id']);
	}elseif($post['action']=='disable_promote_offer' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_disable_promote_offer', $post['token'])){
		\App\Offer::disablePromote((int)$post['id']);
	}elseif($post['action']=='set_moderator' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_set_moderator', $post['token'])){
		\App\User::setModerator((int)$post['id']);
	}elseif($post['action']=='unset_moderator' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_unset_moderator', $post['token'])){
		\App\User::unSetModerator((int)$post['id']);
	}elseif($post['action']=='toggle_verified_email' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_toggle_verified_email', $post['token'])){
		\App\User::toggleVerifiedEmail((int)$post['id']);
	}elseif($post['action']=='toggle_verified_phone' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_toggle_verified_phone', $post['token'])){
		\App\User::toggleVerifiedPhone((int)$post['id']);
	}elseif($post['action']=='toggle_verified_company' and isset($post['id']) and (int)$post['id']>0 and checkToken('admin_toggle_verified_company', $post['token'])){
		\App\User::toggleVerifiedCompany((int)$post['id']);
	}elseif($post['action']=='position_options' and isset($post['id']) and isset($post['position']) and isset($post['plusminus']) and checkToken('admin_position_options', $post['token'])){
		setPosition('option',(int)$post['id'],(int)$post['position'],$post['plusminus']);
	}elseif($post['action']=='position_categories' and isset($post['id']) and isset($post['position']) and isset($post['plusminus']) and isset($post['category']) and (int)$post['category']>=0 and checkToken('admin_position_categories', $post['token'])){
		setPosition('category',(int)$post['id'],(int)$post['position'],$post['plusminus'], 'category_id='.(int)$post['category']);
	}elseif($post['action']=='arrange_categories_alphabetically' and isset($post['category']) and (int)$post['category']>=0 and checkToken('admin_arrange_categories_alphabetically', $post['token'])){
		arrangeAlphabetically('category', 'category_id='.(int)$post['category']);
	}elseif($post['action']=='position_states' and isset($post['id']) and isset($post['position']) and isset($post['plusminus']) and isset($post['state_id']) and checkToken('admin_position_states', $post['token'])){
		setPosition('state',(int)$post['id'],(int)$post['position'],$post['plusminus'], 'state_id='.(int)$post['state_id']);
	}elseif($post['action']=='arrange_staties_alphabetically' and isset($post['state_id']) and checkToken('admin_arrange_staties_alphabetically', $post['token'])){
		arrangeAlphabetically('state', 'state_id='.(int)$post['state_id']);
	}elseif($post['action']=='position_info' and isset($post['id']) and isset($post['position']) and isset($post['plusminus']) and checkToken('admin_position_info', $post['token'])){
		setPosition('info',(int)$post['id'],(int)$post['position'],$post['plusminus']);
	}elseif($post['action']=='arrange_info_alphabetically' and checkToken('admin_arrange_info_alphabetically', $post['token'])){
		arrangeAlphabetically('info');
	}
}else{
	die('Access denied!');
}