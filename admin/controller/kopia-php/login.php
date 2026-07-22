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

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

$failed_count_sth = $db->prepare('SELECT COUNT(1) FROM `'._DB_PREFIX_.'admin_logs` WHERE logged=0 AND date > DATE_ADD(NOW(), INTERVAL -15 MINUTE) AND ip=:ip');
$failed_count_sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
$failed_count_sth->execute();
$failed_attempts = $failed_count_sth->fetchColumn();

$render_variables['failed_attempts'] = $failed_attempts;

if($failed_attempts >= 3){
	if(!isset($_SESSION['admin_captcha_challenge']) or empty($_SESSION['admin_captcha_challenge'])){
		$num1 = rand(1, 10);
		$num2 = rand(1, 10);
		$_SESSION['admin_captcha_challenge'] = "$num1 + $num2 = ?";
		$_SESSION['admin_captcha_answer'] = $num1 + $num2;
	}
	$render_variables['captcha_challenge'] = $_SESSION['admin_captcha_challenge'];
}else{
	unset($_SESSION['admin_captcha_challenge']);
	unset($_SESSION['admin_captcha_answer']);
}

if(isset($_POST['action']) and $_POST['action'] == 'login' and !empty($_POST['session_code']) and !empty($_POST['username']) and !empty($_POST['password'])){

	try{
		if($failed_attempts >= 3){
			if(!isset($_POST['captcha']) or trim($_POST['captcha']) === '' or intval($_POST['captcha']) !== intval($_SESSION['admin_captcha_answer'])){
				// Generate new challenge for next try
				$num1 = rand(1, 10);
				$num2 = rand(1, 10);
				$_SESSION['admin_captcha_challenge'] = "$num1 + $num2 = ?";
				$_SESSION['admin_captcha_answer'] = $num1 + $num2;
				$render_variables['captcha_challenge'] = $_SESSION['admin_captcha_challenge'];
				throw new Exception(lang('Invalid captcha code.'));
			}
		}

		$admin->login($_POST);
		
		// Clear captcha on success
		unset($_SESSION['admin_captcha_challenge']);
		unset($_SESSION['admin_captcha_answer']);
		
		header('Location: '.$_SERVER['REQUEST_URI']);
		die('redirect');
	}catch(Exception $e) {
		$render_variables['alert_danger'][] = $e->getMessage();
		
		// Recalculate failed attempts to immediately show CAPTCHA field
		$failed_count_sth->execute();
		$failed_attempts = $failed_count_sth->fetchColumn();
		$render_variables['failed_attempts'] = $failed_attempts;
		if($failed_attempts >= 3 && (!isset($_SESSION['admin_captcha_challenge']) or empty($_SESSION['admin_captcha_challenge']))){
			$num1 = rand(1, 10);
			$num2 = rand(1, 10);
			$_SESSION['admin_captcha_challenge'] = "$num1 + $num2 = ?";
			$_SESSION['admin_captcha_answer'] = $num1 + $num2;
			$render_variables['captcha_challenge'] = $_SESSION['admin_captcha_challenge'];
		}
	}
}

if(!$admin->is_logged()){
	$render_variables['session_code'] = $admin->newSessionCode();
}
