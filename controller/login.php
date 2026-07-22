<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(!empty($path_parts[1])){
	throw new noFoundException();
}

$get_new_session_code = true;
$tab_active = 'login';

// Obsługa parametru tab z URL (dla linku "Reset password")
if(isset($_GET['tab']) and in_array($_GET['tab'], ['login','register','reset_password'])){
    $tab_active = $_GET['tab'];
}

if(isset($_POST['action']) and $_POST['action'] == 'login'){
    try {
        \App\Core\Validator::validate($_POST, [
            'session_code' => ['required'],
            'username' => ['required'],
            'password' => ['required']
        ]);
		$user->login($_POST);
    } catch (\App\Core\ValidationException $e) {
        $render_variables['alert_danger'][] = getSafeExceptionMessage($e);
	} catch(Exception $e) {
		$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
	}

}elseif(isset($_POST['action']) and $_POST['action'] == 'request_magic_link'){

	if(!checkToken('request_magic_link')){
		$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
		$render_variables['input'] = $_POST;
	}else{
		try{
            \App\Core\Validator::validate($_POST, [
                'email' => ['required', 'email'],
                'captcha' => ['required']
            ]);
			$user->requestMagicLink($_POST);
			$render_variables['alert_success'][] = lang('A login link has been sent to your email address.');
        } catch (\App\Core\ValidationException $e) {
            $render_variables['alert_danger'][] = getSafeExceptionMessage($e);
            $render_variables['input'] = $_POST;
		}catch(Exception $e) {
			$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
			$render_variables['input'] = $_POST;
		}
	}
	$tab_active = 'magic_link';

}elseif(!empty($_GET['magic_token'])){

	try{
		$user->loginWithMagicToken($_GET['magic_token']);
	}catch(Exception $e) {
		$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
	}

}elseif(!empty($_GET['activation_code'])){

	if(\App\User::checkCodeAndActivate($_GET['activation_code'])){
		$render_variables['alert_success'][] = lang('Account has been activated, you can now log in.');
	}else{
		$render_variables['alert_danger'][] = lang('Incorrect activation code or the account has already been activated');
	}

}elseif(!empty($_GET['complete_data'])){

	$complete_data = $user->checkCompleteData($_GET['complete_data']);
	if($complete_data){
		$render_variables['input']['username'] = preg_replace('/@.*/', '', $complete_data['email']);
		if(isset($_POST['action']) and $_POST['action']=='complete_data'){
			if(!checkToken('complete_data')){
				$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
				$render_variables['input'] = $_POST;
			}else{
				try{
					$user->completeData($_GET['complete_data'], $_POST);
					$_SESSION['flash'] = 'profile_completed';
					$redirectUrl = getSafeRedirectUrl($_GET['redirect'] ?? '', $settings['base_url']);
					header("Location: " . $redirectUrl);
					die('redirect');
				}catch(Exception $e) {
					$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
					$render_variables['input'] = $_POST;
				}
			}
		}

		$render_variables['form_complete_data'] = $complete_data;
		$render_variables['states'] = getAllStates();
		$get_new_session_code = false;
	}

}elseif(isset($_POST['action']) and $_POST['action']=='register'){

	if(!checkToken('register')){
		$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
		$render_variables['input'] = $_POST;
	}else{
        try {
            \App\Core\Validator::validate($_POST, [
                'email' => ['required', 'email'],
                'username' => ['required', 'min:3'],
                'password' => ['required', 'min:5'],
                'password_repeat' => ['required'],
                'captcha' => ['required']
            ]);
            $result = $user->register($_POST);
            if($result['status']){
                $_SESSION['flash'] = 'new_account';
                header("Location: ".path('login'));
                die('redirect');
            }elseif(!empty($result['error'])){
                $render_variables['error'] = $result['error'];
                $render_variables['input'] = $_POST;
            }
        } catch (\App\Core\ValidationException $e) {
            $render_variables['error'] = getSafeExceptionMessage($e);
            $render_variables['input'] = $_POST;
        }
	}
  	$tab_active = 'register';

}elseif(isset($_POST['action']) and $_POST['action'] == 'reset_password'){

	if(!checkToken('reset_password')){
		$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
		$render_variables['input'] = $_POST;
	}else{
		try{
            \App\Core\Validator::validate($_POST, [
                'username' => ['required'],
                'captcha' => ['required']
            ]);
			$user->resetPassword($_POST);
			$render_variables['alert_success'][] = lang('Link to change your password has been sent to your email address.');
        } catch (\App\Core\ValidationException $e) {
            $render_variables['alert_danger'][] = getSafeExceptionMessage($e);
            $render_variables['input'] = $_POST;
		}catch(Exception $e) {
			$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
			$render_variables['input'] = $_POST;
		}
	}
	$tab_active = 'reset_password';

}elseif(!empty($_GET['new_password'])){

  $tab_active = 'reset_password';

	$user_id = $user->resetPasswordNew($_GET['new_password'])['user_id'];
	if($user_id){
		if(isset($_POST['action']) and $_POST['action'] == 'new_password' and isset($_POST['password']) and isset($_POST['password_repeat'])){
			if(!checkToken('new_password')){
				$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
			}else{
				try{
					$user->resetPasswordNewCheck($user_id,$_POST,$_GET['new_password']);
					$render_variables['alert_success'][] = lang('The password has been changed successfully. You can now login to the site');
					$tab_active = 'login';
				}catch(Exception $e) {
					$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
				}
			}
		}

		$render_variables['form_new_password'] = \App\User::getData($user_id);
	}else{
		$render_variables['alert_danger'][] = lang('Incorrect or inactive password reset code');
	}
}

if($get_new_session_code){
	if($settings['facebook_login'] and $settings['facebook_api'] and $settings['facebook_secret']){
		if(isset($_GET['facebook_login'])){
			$user->loginFB();
		}
		$render_variables['facebook_redirect_uri'] = 'https://www.facebook.com/v2.2/dialog/oauth?client_id='.$settings['facebook_api'].'&redirect_uri='.urlencode(path('login').'?facebook_login').'&sdk=php-sdk-4.0.12&scope=email';
	}
	if($settings['google_login'] and $settings['google_id'] and $settings['google_secret']){
		$user->loginGoogle();
		$render_variables['google_redirect_uri'] = 'https://accounts.google.com/o/oauth2/v2/auth?scope=' . urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email') . '&redirect_uri='.urlencode(path('login')).'&response_type=code&client_id=' .$settings['google_id'].'&access_type=online';
	}
	$render_variables['session_code'] = \App\User::newSessionCode();
}

$render_variables['tab_active'] = $tab_active;

$settings['seo_title'] = lang('Log in').' - '.$settings['title'];
$settings['seo_description'] = lang('Log in on the website').' - '.$settings['description'];
