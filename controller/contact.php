<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(!empty($path_parts[1])){
	throw new noFoundException();
}

$contact_page = \App\Info::getInfoByName('contact');
$render_variables['contact_page'] = $contact_page;

if(isset($_POST['action']) and $_POST['action']=='send_message'){

	if(!checkToken('send_message')){
		$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
    } else {
        try {
            $rules = [
                'name' => ['required'],
                'message' => ['required'],
                'captcha' => ['required']
            ];
            
            if (!$user->getId()) {
                $rules['email'] = ['required', 'email'];
                $rules['rules'] = ['required'];
            }
            
            \App\Core\Validator::validate($_POST, $rules);
            
            if($_POST['captcha']!=$_SESSION['captcha']){
                throw new \App\Core\ValidationException(lang('Invalid captcha code.'), ['captcha' => lang('Invalid captcha code.')]);
            }

            if($user->getId()){
                $email = $user->email;
            }else{
                $email = $_POST['email'];
            }
            if(sendMail('contact_form',$settings['email'],['name'=>$_POST['name'], 'email'=>$email, 'message'=>strip_tags($_POST['message']), 'user_id'=>$user->getId()])){
                $render_variables['alert_success'][] = lang('The message was correctly sent');
            }else{
                $render_variables['alert_danger'][] = lang('The message was not sent');
            }

        } catch (\App\Core\ValidationException $e) {
            $render_variables['error'] = $e->getErrors();
            $render_variables['alert_danger'][] = lang('The message was not sent');
            $render_variables['input'] = ['name'=>$_POST['name'] ?? '', 'email'=>$_POST['email'] ?? '', 'message'=>$_POST['message'] ?? ''];
        }
    }
}



$settings['seo_title'] = $contact_page['name'].' - '.$settings['title'];
if($contact_page['description']){
	$settings['seo_description'] = $contact_page['description'];
}else{
	$settings['seo_description'] = $contact_page['name'].' - '.$settings['description'];
}
if($contact_page['keywords']){
	$settings['seo_keywords'] = $contact_page['keywords'];
}
