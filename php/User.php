<?php


namespace App;

use PDO;
use Exception;
#[AllowDynamicProperties]
class User {

	public ?int $id = null;
	/**
     * @var false
     */
    public $logged_in = false;
	public $user_data = [];

	public function __construct () {
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();
		$this->logged_in = false;

		if(isset($_GET['log_out']) and !empty($_GET['token']) and checkToken('logout',$_GET['token'])){
			$this->logOut();
			header("Location: ".$settings['base_url']);
			die('redirect');
		}elseif(!empty($_SESSION['user']['id']) and !empty($_SESSION['user']['session_code'])){
			/* Modified: Session hijacking protection */
			if ((isset($_SESSION['user']['ip']) && $_SESSION['user']['ip'] !== getClientIp()) ||
				(isset($_SESSION['user']['user_agent']) && $_SESSION['user']['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
				$this->logOut();
			} else {
				$this->loginFromSession();
			}
			}elseif(!empty($_COOKIE['user_id']) and !empty($_COOKIE['user_code'])){
			$_SESSION['user']['id'] = $_COOKIE['user_id'];
			$_SESSION['user']['session_code'] = $_COOKIE['user_code'];
			$_SESSION['user']['ip'] = getClientIp();
			$_SESSION['user']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$this->loginFromSession();
		}
	}

	public function __get(string $value): mixed{
		return $this->user_data[$value] ?? false;
	}

	public function __set(string $name, mixed $value){
		$this->user_data[$name] = $value;
	}

	public function loginFromSession(): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'session_user WHERE user_id=:user_id AND code=:code LIMIT 1');
		$sth->bindValue(':user_id', $_SESSION['user']['id'], PDO::PARAM_INT);
		$sth->bindValue(':code', $_SESSION['user']['session_code'], PDO::PARAM_STR);
		$sth->execute();

		if($sth->fetchColumn()){
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $_SESSION['user']['id'], PDO::PARAM_INT);
			$sth->execute();
			$user = $sth->fetch(PDO::FETCH_ASSOC);
			if($user!=''){
				$this->user_data = $user;
				$this->id = (int)$user['id'];
				$this->logged_in = true;
			}
		}else{
			$this->logOut();
		}
	}

	public function login(array $data): void{
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();
		if($settings['check_ip_user']){
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'session_user WHERE code=:code AND ip=:ip LIMIT 1');
			$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		}else{
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'session_user WHERE code=:code LIMIT 1');
		}
		$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
		$sth->execute();
		if($sth->fetchColumn()){

			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE (username=:username OR email=:username) LIMIT 1');
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->execute();
			$user = $sth->fetch(PDO::FETCH_ASSOC);

			if ($user and $this->checkPassword($data['password'], $user['password'])) {
					if($user['active']=='1'){
						if($user['username']==''){
							header("Location: ".path('login')."?complete_data=".$user['activation_code']);
							die('redirect');
						}

						regenerateSessionId();
						$_SESSION['user']['id'] = $user['id'];
						$_SESSION['user']['session_code'] = $data['session_code'];
						$_SESSION['user']['ip'] = getClientIp();
						$_SESSION['user']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

					$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'session_user` SET user_id=:user_id WHERE code=:code');
					$sth->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
					$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
					$sth->execute();

											if(!empty($data['remember_me'])){
						setRememberMeCookie('user_id', (string)$user['id'], time() + (86400 * 30));
						setRememberMeCookie('user_code', $data['session_code'], time() + (86400 * 30));
					}

					static::logUserLoginAndNotify((int)$user['id'], (string)$user['username'], (string)$user['email']);

					$redirectUrl = getSafeRedirectUrl($_GET['redirect'] ?? '', $settings['base_url']);
					header("Location: " . $redirectUrl);
					die('redirect');
				}else{
					static::removeSessionCode($data['session_code']);
					throw new Exception(lang('The account has not been activated yet.'));
				}
			}else{
				static::removeSessionCode($data['session_code']);
				throw new Exception(lang('Username or password are incorrect'));
			}
		}else{
			throw new Exception(lang('Session error'));
		}
	}

	public static function checkCodeAndActivate($activation_code): bool{
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'user WHERE active=0 AND activation_code=:activation_code LIMIT 1');
		$sth->bindValue(':activation_code', $activation_code, PDO::PARAM_STR);
		$sth->execute();
		$id = $sth->fetch(PDO::FETCH_ASSOC)['id'];
		if($id){
			static::activate($id);
			return true;
		}else{
			return false;
		}
	}

	public static function getIdFromEmail($email){
		$db = \App\Core\App::db();
		$id = 0;
		if($email){
			$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'user WHERE email=:email LIMIT 1');
			$sth->bindValue(':email', $email, PDO::PARAM_STR);
			$sth->execute();
			$temp_id = $sth->fetch(PDO::FETCH_ASSOC);
			if($temp_id){$id = $temp_id['id'];}
		}
		return $id;
	}

	public static function activate(int $id): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET active=1, verified_email=1, activation_ip=:activation_ip, activation_date=NOW() WHERE id=:id LIMIT 1');
		$sth->bindValue(':activation_ip', getClientIp(), PDO::PARAM_STR);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function setModerator(int $id): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET moderator=1 WHERE id=:id limit 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function unSetModerator(int $id): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET moderator=0 WHERE id=:id limit 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function toggleVerifiedEmail(int $id): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET verified_email = 1 - COALESCE(verified_email, 0) WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function toggleVerifiedPhone(int $id): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET verified_phone = 1 - COALESCE(verified_phone, 0) WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function toggleVerifiedCompany(int $id): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET verified_company = 1 - COALESCE(verified_company, 0) WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function newSessionCode(): string{
		$db = \App\Core\App::db();
		$session_code = bin2hex(random_bytes(32));
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'session_user`(`code`, `ip`, `date`) VALUES (:code,:ip,NOW())');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();
		return $session_code;
	}

	public static function removeSessionCode($session_code): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'session_user` WHERE code=:code');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->execute();
	}

	public function logOut(): void{
		$db = \App\Core\App::db();
		$this->logged_in = false;
		if(!empty($_SESSION['user']['session_code'])){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'session_user WHERE code=:code');
			$sth->bindValue(':code', $_SESSION['user']['session_code'], PDO::PARAM_STR);
			$sth->execute();
		}
		unset($_SESSION['user']);
		unset($_SESSION['token']);
		clearRememberMeCookie("user_id");
		clearRememberMeCookie("user_code");
	}

	/**
     * @return array<'captcha'|'email'|'info', mixed>[]|bool[]
     */
    public function register(array $data): array{
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();

		if($data['captcha']!=$_SESSION['captcha']){
			$error['captcha'] = lang('Invalid captcha code.');
		}else{
			if(checkEmailBlackList($data['email']) or checkIpBlackList(getClientIp())){
				$error['info'] = lang('The account could not be submitted');
			}else{
				if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL) or strlen((string) $data['email'])>64) {
					$error['email'] = lang('Incorrect e-mail address');
				}else{
					$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'user WHERE email=:email LIMIT 1');
					$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
					$sth->execute();
					if($sth->fetchColumn()){
						$error['email'] = lang('E-mail already exists in the database.');
					}
				}
				$old_username = $data['username'];
				$data['username'] = slugWithUpper(strip_tags((string) $data['username']));
				if(!$data['username'] or strlen($data['username'])>64 or $old_username!=$data['username']){
					$error['username'] = lang('Invalid username');
				}else{
					$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1');
					$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
					$sth->execute();
					if($sth->fetchColumn()){
						$error['username'] = lang('The selected username is already taken');
					}
				}
				if(!$data['password'] or strlen((string) $data['password'])>32){
					$error['password'] = lang('The password is incorrect.');
				}elseif($data['password']!=$data['password_repeat']){
					$error['password'] = lang('Entered passwords are different');
				}
				if(!isset($data['rules'])){
					$error['rules'] = lang('This field is mandatory.');
				}
			}
		}

		if(isset($error)){
			return ['status'=>false,'error'=>$error];
		}else{

			$activation_code = bin2hex(random_bytes(32));

			sendMail('register',$data['email'],['activation_code'=>$activation_code, 'password'=>$data['password'], 'username'=>$data['username'], 'email'=>$data['email']]);

			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'user`(`username`, `email`, `password`, `activation_code`, `register_ip`, `date`) VALUES (:username,:email,:password,:activation_code,:register_ip,NOW())');
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
			$sth->bindValue(':activation_code', $activation_code, PDO::PARAM_STR);
			$sth->bindValue(':register_ip', getClientIp(), PDO::PARAM_STR);
			$sth->execute();

			return ['status'=>true];
		}
	}

	public function resetPassword(array $data): void{
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();

		if($data['captcha']!=$_SESSION['captcha']){
			throw new Exception(lang('Invalid captcha code.'));
		}

		$sth = $db->prepare('SELECT id, email, username FROM '._DB_PREFIX_.'user WHERE (username=:username OR email=:username) LIMIT 1');
		$sth->bindValue(':username', strip_tags((string) $data['username']), PDO::PARAM_STR);
		$sth->execute();
		$user_data = $sth->fetch(PDO::FETCH_ASSOC);
		if($user_data==''){
			throw new Exception(lang('Incorrect user data'));
		}
		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'reset_password WHERE active=1 AND date>(NOW() - INTERVAL 1 DAY) AND user_id=:user_id LIMIT 1');
		$sth->bindValue(':user_id', $user_data['id'], PDO::PARAM_INT);
		$sth->execute();
		if($sth->fetchColumn()){
			throw new Exception(lang('Link to change your password has been sent.'));
		}
		
		$code = bin2hex(random_bytes(32));

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'reset_password`(`user_id`, `active`, `code`, `date`) VALUES (:user_id,1,:code,NOW())');
		$sth->bindValue(':user_id', $user_data['id'], PDO::PARAM_INT);
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();

		sendMail('reset_password',$user_data['email'], ['reset_password_code'=>$code, 'username'=>$user_data['username']]);
	}

	public function resetPasswordNew($code){
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT user_id FROM '._DB_PREFIX_.'reset_password WHERE active=1 AND date>(NOW() - INTERVAL 1 DAY) AND code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		$user_id = $sth->fetch(PDO::FETCH_ASSOC);
		if($user_id){
			return $user_id;
		}
		return false;
	}

	public function resetPasswordNewCheck(int $user_id,array $data,$code): void{
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();

		if($data['password']!=$data['password_repeat']){
			throw new Exception(lang('Entered passwords are different'));
		}elseif($data['password']=='' or strlen((string) $data['password'])>32){
			throw new Exception(lang('The password is incorrect.'));
		}
		
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'reset_password SET used=1, active=0 WHERE code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET password=:password WHERE id=:id LIMIT 1');
		$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
		$sth->bindValue(':id', $user_id, PDO::PARAM_INT);
		$sth->execute();
	}

	public function createPassword(string $password): string{
		return createPasswordHash($password);
	}

	public function checkPassword(string $password, string $hash): bool {
		return verifyPasswordHash($password, $hash);
	}

	public function checkCompleteData($code): mixed{
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE (ISNULL(username) OR username="") AND active=1 AND activation_code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public function completeData($code,array $data): void{
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();
		if(!isset($data['rules'])){
			throw new Exception(lang('You must approve the rules and privacy policy'));
		}
		$old_username = $data['username'];
		$data['username'] = slugWithUpper(strip_tags((string) $data['username']));
		if(!$data['username'] or strlen($data['username'])>64 or $old_username!=$data['username']){
			throw new Exception(lang('Invalid username'));
		}
		
		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1');
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->execute();
		if($sth->fetchColumn()){
			throw new Exception(lang('The selected username is already taken'));
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET username=:username WHERE active=1 AND activation_code=:code AND (ISNULL(username) OR username="") LIMIT 1');
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();

		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1');
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->execute();
		$user = $sth->fetch(PDO::FETCH_ASSOC);

		if ($user) {
			$this->id = (int)$user['id'];
			$this->user_data = $user;
			
			$userData = [
				'address' => $data['address'] ?? '',
				'phone' => $data['phone'] ?? '',
				'state_id' => isset($data['state_id']) ? (int)$data['state_id'] : 0,
				'state2_id' => isset($data['state2_id']) ? (int)$data['state2_id'] : 0,
				'url_website' => $data['url_website'] ?? '',
				'url_facebook' => $data['url_facebook'] ?? '',
				'url_linkedin' => $data['url_linkedin'] ?? '',
				'url_youtube' => $data['url_youtube'] ?? '',
				'company_name' => $data['company_name'] ?? '',
				'company_nip' => $data['company_nip'] ?? '',
				'hide_phone' => 0,
				'hide_email' => 0,
				'notify_newsletter' => 1,
				'notify_messages' => 1
			];
			$changes = $this->saveUserData($userData);
			if (!empty($changes)) {
				$_SESSION['flash_profile_completed_fields'] = $changes;
			}
		}

		$session_code = bin2hex(random_bytes(32));

		$_SESSION['user']['id'] = $user['id'];
		$_SESSION['user']['session_code'] = $session_code;

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'session_user`(`user_id`, `code`, `ip`, `date`) VALUES (:user_id,:code,:ip,NOW())');
		$sth->bindValue(':user_id', $user['id'], PDO::PARAM_STR);
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();

		setRememberMeCookie('user_id', (string)$user['id'], time() + (86400 * 30));
		setRememberMeCookie('user_code', $session_code, time() + (86400 * 30));

		static::logUserLoginAndNotify((int)$user['id'], (string)$user['username'], (string)$user['email']);

	}

	public function getAllData(): void{
		$db = \App\Core\App::db();

		$sth = $db->prepare('SELECT count(1) FROM '._DB_PREFIX_.'offer WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['number_offers'] = $sth->fetchColumn();

		$sth = $db->prepare('SELECT count(1) FROM '._DB_PREFIX_.'logs_user WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['number_login'] = $sth->fetchColumn();

		$sth = $db->prepare('SELECT date FROM '._DB_PREFIX_.'logs_user WHERE user_id=:user_id order by date desc LIMIT 1,1');
		$sth->bindValue(':user_id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['last_login'] = $sth->fetch(PDO::FETCH_ASSOC)['date'];

		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'reset_password WHERE user_id=:user_id AND used=1 order by date desc LIMIT 1');
		$sth->bindValue(':user_id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['last_reset_password'] = $sth->fetch(PDO::FETCH_ASSOC)['date'];
	}

	public static function getUsernameFromId(int $user_id){
		$db = \App\Core\App::db();
		$username = '';
		if($user_id>0){
			$sth = $db->prepare('SELECT username FROM '._DB_PREFIX_.'user WHERE id=:user_id LIMIT 1');
			$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$sth->execute();
			$username_temp = $sth->fetch(PDO::FETCH_ASSOC);
			if($username_temp){$username = $username_temp['username'];}
		}
		return $username;
	}

	public function changePassword(array $data): void{
		$db = \App\Core\App::db();
		if($data['new_password']==$data['repeat_new_password']){
			if($this->checkPassword($data['old_password'], $this->password)){
				$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET password=:password WHERE id=:id LIMIT 1');
				$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
				$sth->bindValue(':password', $this->createPassword($data['new_password']), PDO::PARAM_STR);
				$sth->execute();
			}else{
				throw new Exception(lang('Enter proper old password'));
			}
		}else{
			throw new Exception(lang('Entered passwords are different'));
		}
	}

	public function saveDescription($description): void{
		$db = \App\Core\App::db();
        $purifier = \App\Core\App::purifier();
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET description=:description WHERE id=:id LIMIT 1');
		$sth->bindValue(':description', checkWordsBlackList(nofollow($purifier->purify(trim((string) $description)))), PDO::PARAM_STR);
		$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['description'] = $description;
	}

	/**
     * @return list
     */
    public function saveUserData(array $data): array{
		$db = \App\Core\App::db();
		if(!isset($data['state_id'])){$data['state_id']=0;}
		if(!isset($data['state2_id'])){$data['state2_id']=0;}
		$hide_phone = !empty($data['hide_phone']) ? 1 : 0;
		$hide_email = !empty($data['hide_email']) ? 1 : 0;
		$notify_newsletter = !empty($data['notify_newsletter']) ? 1 : 0;
		$notify_messages = !empty($data['notify_messages']) ? 1 : 0;

		$fields_map = [
			'phone' => 'Phone number',
			'address' => 'Address / Company Head Office',
			'state_id' => 'State',
			'state2_id' => 'City',
			'company_name' => 'Company name',
			'company_nip' => 'Company NIP',
			'url_website' => 'Website',
			'url_facebook' => 'Profil Facebook',
			'url_linkedin' => 'Profil LinkedIn',
			'url_youtube' => 'Kanał YouTube'
		];

		$updated_fields = [];
		foreach ($fields_map as $key => $name) {
			$new_val = isset($data[$key]) ? trim(strip_tags((string)$data[$key])) : '';
			$old_val = isset($this->user_data[$key]) ? trim((string)$this->user_data[$key]) : '';
			
			if ($key === 'state_id' || $key === 'state2_id') {
				$new_val = (int)$new_val;
				$old_val = (int)$old_val;
				if ($new_val !== $old_val) {
					if ($new_val > 0) {
						$updated_fields[] = lang($name);
					}
				}
			} else {
				if ($new_val !== $old_val) {
					if ($new_val !== '') {
						$updated_fields[] = lang($name);
					}
				}
			}
		}

		$phone_changed = false;
		$company_changed = false;

		$new_phone = isset($data['phone']) ? trim(strip_tags((string)$data['phone'])) : '';
		$old_phone = isset($this->user_data['phone']) ? trim((string)$this->user_data['phone']) : '';
		if ($new_phone !== $old_phone) {
			$phone_changed = true;
		}

		$new_company_name = isset($data['company_name']) ? trim(strip_tags((string)$data['company_name'])) : '';
		$old_company_name = isset($this->user_data['company_name']) ? trim((string)$this->user_data['company_name']) : '';
		$new_company_nip = isset($data['company_nip']) ? trim(strip_tags((string)$data['company_nip'])) : '';
		$old_company_nip = isset($this->user_data['company_nip']) ? trim((string)$this->user_data['company_nip']) : '';
		if ($new_company_name !== $old_company_name || $new_company_nip !== $old_company_nip) {
			$company_changed = true;
		}

		$sql = 'UPDATE '._DB_PREFIX_.'user SET address=:address, phone=:phone, state_id=:state_id, state2_id=:state2_id, hide_phone=:hide_phone, hide_email=:hide_email, url_website=:url_website, url_facebook=:url_facebook, url_linkedin=:url_linkedin, url_youtube=:url_youtube, company_name=:company_name, company_nip=:company_nip, notify_newsletter=:notify_newsletter, notify_messages=:notify_messages';
		if ($phone_changed) {
			$sql .= ', verified_phone=0';
		}
		if ($company_changed) {
			$sql .= ', verified_company=0';
		}
		$sql .= ' WHERE id=:id LIMIT 1';

		$sth = $db->prepare($sql);
		$sth->bindValue(':address', trim(strip_tags((string) $data['address'])), PDO::PARAM_STR);
		$sth->bindValue(':phone', trim(strip_tags((string) $data['phone'])), PDO::PARAM_STR);
		$sth->bindValue(':state_id', $data['state_id'], PDO::PARAM_INT);
		$sth->bindValue(':state2_id', $data['state2_id'], PDO::PARAM_INT);
		$sth->bindValue(':hide_phone', $hide_phone, PDO::PARAM_INT);
		$sth->bindValue(':hide_email', $hide_email, PDO::PARAM_INT);
		$sth->bindValue(':url_website', trim(strip_tags($data['url_website'] ?? '')), PDO::PARAM_STR);
		$sth->bindValue(':url_facebook', trim(strip_tags($data['url_facebook'] ?? '')), PDO::PARAM_STR);
		$sth->bindValue(':url_linkedin', trim(strip_tags($data['url_linkedin'] ?? '')), PDO::PARAM_STR);
		$sth->bindValue(':url_youtube', trim(strip_tags($data['url_youtube'] ?? '')), PDO::PARAM_STR);
		$sth->bindValue(':company_name', trim(strip_tags($data['company_name'] ?? '')), PDO::PARAM_STR);
		$sth->bindValue(':company_nip', trim(strip_tags($data['company_nip'] ?? '')), PDO::PARAM_STR);
		$sth->bindValue(':notify_newsletter', $notify_newsletter, PDO::PARAM_INT);
		$sth->bindValue(':notify_messages', $notify_messages, PDO::PARAM_INT);
		$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		
		$this->user_data['address'] = $data['address'];
		$this->user_data['phone'] = $data['phone'];
		$this->user_data['state_id'] = $data['state_id'];
		$this->user_data['state2_id'] = $data['state2_id'];
		$this->user_data['hide_phone'] = $hide_phone;
		$this->user_data['hide_email'] = $hide_email;
		$this->user_data['url_website'] = $data['url_website'] ?? '';
		$this->user_data['url_facebook'] = $data['url_facebook'] ?? '';
		$this->user_data['url_linkedin'] = $data['url_linkedin'] ?? '';
		$this->user_data['url_youtube'] = $data['url_youtube'] ?? '';
		$this->user_data['company_name'] = $data['company_name'] ?? '';
		$this->user_data['company_nip'] = $data['company_nip'] ?? '';
		$this->user_data['notify_newsletter'] = $notify_newsletter;
		$this->user_data['notify_messages'] = $notify_messages;
		if ($phone_changed) {
			$this->user_data['verified_phone'] = 0;
		}
		if ($company_changed) {
			$this->user_data['verified_company'] = 0;
		}

		return $updated_fields;
	}

	public function getId(): ?int{
		if($this->logged_in){
			return $this->id;
		}
		return 0;
	}

	public function loginFB(): void{
		$settings = \App\Core\App::settings();
		$fb_email = '';
		$fb_picture = '';
		if(!empty($_REQUEST['code'])){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/oauth/access_token?fields=email,name&client_id=".$settings['facebook_api']."&redirect_uri=".urlencode(path('login').'?facebook_login')."&client_secret=".$settings['facebook_secret']."&code=".$_REQUEST['code']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/certs/cacert.pem');
			$fb_params = json_decode(curl_exec($ch));
			curl_close($ch);
			if(isset($fb_params->access_token)){
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/me?fields=email,name,picture.type(large)&access_token=".$fb_params->access_token);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/certs/cacert.pem');
				$fb_user = json_decode(curl_exec($ch), true);
				if(isset($fb_user['email'])){
					$fb_email = $fb_user['email'];
					if(!empty($fb_user['picture']['data']['url'])){
						$fb_picture = $fb_user['picture']['data']['url'];
					}
				}
				curl_close($ch);
			}
		}
		if($fb_email){
			$this->loginByEmail($fb_email,'fb', $fb_picture);
		}
	}

	public function loginGoogle(): void{
		$settings = \App\Core\App::settings();
		$google_email = '';
		$google_picture = '';
		if(!empty($_REQUEST['code'])){
			$url = 'https://accounts.google.com/o/oauth2/token';
			$curlPost = 'client_id='.$settings['google_id'].'&redirect_uri='.urlencode((string) path('login')).'&client_secret='.$settings['google_secret'].'&code='.$_REQUEST['code'].'&grant_type=authorization_code';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/certs/cacert.pem');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			$data = json_decode(curl_exec($ch), true);
			$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			if($http_code == 200){
				$url = 'https://www.googleapis.com/oauth2/v2/userinfo';

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/certs/cacert.pem');
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$data['access_token']]);
				$data2 = json_decode(curl_exec($ch), true);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if($http_code == 200){
					$google_email = $data2['email'];
					if(!empty($data2['picture'])){
						$google_picture = $data2['picture'];
					}
				}
				curl_close($ch);
			}
		}
		if($google_email){
			$this->loginByEmail($google_email,'google', $google_picture);
		}
	}

	public function saveAvatarFromUrl($url): void {
		$db = \App\Core\App::db();
		if (empty($url)) {
			return;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/certs/cacert.pem');
		$img_data = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code == 200 && !empty($img_data)) {
			$temp_file = tempnam(sys_get_temp_dir(), 'avatar_');
			file_put_contents($temp_file, $img_data);

			$image_info = @getimagesize($temp_file);
			if ($image_info && isset($image_info['mime'])) {
				$allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/webp'];
				if (in_array(strtolower($image_info['mime']), $allowed_mimes)) {

					$filename = 'social_avatar_' . $this->id . '.png';

					$src = null;
					if ($image_info[2] === IMAGETYPE_JPEG) {
						$src = @imagecreatefromjpeg($temp_file);
					} elseif ($image_info[2] === IMAGETYPE_PNG) {
						$src = @imagecreatefrompng($temp_file);
					} elseif (defined('IMAGETYPE_WEBP') && $image_info[2] === IMAGETYPE_WEBP) {
						$src = @imagecreatefromwebp($temp_file);
					}

					if ($src) {
						$width = $image_info[0];
						$height = $image_info[1];
						$newheight = ($height >= 80) ? 80 : $height;
						$newwidth = (int)round($newheight / $height * $width);

						$tmp = imagecreatetruecolor($newwidth, $newheight);
						imagesavealpha($tmp, true);
						$color = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
						imagefill($tmp, 0, 0, $color);

						imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

						imagepng($tmp, _FOLDER_AVATARS_ . $filename);
						imagedestroy($src);
						imagedestroy($tmp);

						$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET avatar=:avatar WHERE id=:id LIMIT 1');
						$sth->bindValue(':avatar', $filename, PDO::PARAM_STR);
						$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
						$sth->execute();
						if(isset($this->user_data)){
							$this->user_data['avatar'] = $filename;
						}
					}
				}
			}
			@unlink($temp_file);
		}
	}

	public function loginByEmail($email,$source='',$picture_url=''): void{
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();

		if(checkEmailBlackList($email) or checkIpBlackList(getClientIp())){
			$error['info'] = lang('The account could not be submitted');
		}else{
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE email=:email LIMIT 1');
			$sth->bindValue(':email', $email, PDO::PARAM_STR);
			$sth->execute();
			$user_data = $sth->fetch(PDO::FETCH_ASSOC);

			if($user_data){

				if($user_data['active']=='0'){
					$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET active=1 WHERE id=:id');
					$sth->bindValue(':id', $user_data['id'], PDO::PARAM_INT);
					$sth->execute();
				}
				
				$this->id = (int)$user_data['id'];
				if(empty($user_data['avatar']) && !empty($picture_url)){
					$this->saveAvatarFromUrl($picture_url);
				}

				if($user_data['username']==''){
					header("Location: ".path('login')."?complete_data=".$user_data['activation_code']);
					die('redirect');
				}

					$session_code = bin2hex(random_bytes(32));

					regenerateSessionId();
					$_SESSION['user']['id'] = $user_data['id'];
					$_SESSION['user']['session_code'] = $session_code;

				$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'session_user`(`user_id`, `code`, `ip`, `date`) VALUES (:user_id,:code,:ip,NOW())');
				$sth->bindValue(':user_id', $user_data['id'], PDO::PARAM_STR);
				$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
				$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
				$sth->execute();

					setRememberMeCookie('user_id', (string)$user_data['id'], time() + (86400 * 30));
					setRememberMeCookie('user_code', $session_code, time() + (86400 * 30));

				static::logUserLoginAndNotify((int)$user_data['id'], (string)$user_data['username'], (string)$user_data['email']);

				$redirectUrl = getSafeRedirectUrl($_GET['redirect'] ?? '', $settings['base_url']);
				header("Location: " . $redirectUrl);
				die('redirect');
			}else{

				$activation_code = bin2hex(random_bytes(32));
				$password = randomPassword();
				$register_fb = $register_google = 0;

				if($source=='fb'){
					sendMail('register_fb',$email,['activation_code'=>$activation_code, 'password'=>$password, 'email'=>$email]);
					$register_fb = 1;
				}elseif($source=='google'){
					sendMail('register_google',$email,['activation_code'=>$activation_code, 'password'=>$password, 'email'=>$email]);
					$register_google = 1;
				}

				$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'user`(`active`, `verified_email`, `email`, `password`, `activation_code`, `register_fb`, `register_google`, `register_ip`, `activation_date`, `activation_ip`, `date`) VALUES (1, 1, :email,:password,:activation_code,:register_fb,:register_google,:register_ip,NOW(), :activation_ip, NOW())');
				$sth->bindValue(':email', strip_tags((string) $email), PDO::PARAM_STR);
				$sth->bindValue(':password', $this->createPassword($password), PDO::PARAM_STR);
				$sth->bindValue(':activation_code', $activation_code, PDO::PARAM_STR);
				$sth->bindValue(':register_fb', $register_fb, PDO::PARAM_INT);
				$sth->bindValue(':register_google', $register_google, PDO::PARAM_INT);
				$sth->bindValue(':register_ip', getClientIp(), PDO::PARAM_STR);
				$sth->bindValue(':activation_ip', getClientIp(), PDO::PARAM_STR);
				$sth->execute();
				$new_user_id = $db->lastInsertId();

				if(!empty($picture_url) && $new_user_id){
					$this->id = (int)$new_user_id;
					$this->saveAvatarFromUrl($picture_url);
				}

				header("Location: ".path('login')."?complete_data=".$activation_code);
				die('redirect');
			}
		}
	}

	public static function getProfile($username){
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT u.*, 
		(SELECT count(1) FROM '._DB_PREFIX_.'offer WHERE user_id=u.id) AS number_offers,
		(SELECT count(1) FROM '._DB_PREFIX_.'logs_user WHERE user_id=u.id) AS number_login,
		(SELECT date FROM '._DB_PREFIX_.'logs_user WHERE user_id=u.id ORDER BY date DESC LIMIT 1,1) AS last_login,
		(SELECT AVG(rating) FROM '._DB_PREFIX_.'opinion WHERE user_id=u.id AND active=1) AS rating_avg,
		(SELECT COUNT(id) FROM '._DB_PREFIX_.'opinion WHERE user_id=u.id AND active=1) AS rating_count,
		(SELECT COUNT(id) FROM '._DB_PREFIX_.'opinion WHERE user_id=u.id AND active=1 AND rating>=4) AS rating_positive_count
		FROM '._DB_PREFIX_.'user u WHERE u.active=1 AND u.username=:username LIMIT 1');
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->execute();
		$profile = $sth->fetch(PDO::FETCH_ASSOC);
		return ($profile);
	}

	public static function getData(int $id): mixed{
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public function saveAvatar(): void{
		$db = \App\Core\App::db();

		static::removeAvatar($this->getId());

		$path_parts = pathinfo((string) $_FILES['avatar']['name']);
		$path_parts['extension'] = strtolower($path_parts['extension']);

		if(in_array($path_parts['extension'], ['jpg','jpeg','png','webp'])){
			$image_info = @getimagesize($_FILES['avatar']['tmp_name']);
			if(!$image_info || !isset($image_info['mime'])){
				return;
			}
			$allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/webp'];
			if(!in_array(strtolower($image_info['mime']), $allowed_mimes)){
				return;
			}

			$url = slug($path_parts['filename']).'.png';
			$i = 0;
			while(file_exists(_FOLDER_AVATARS_.$url)) {
				$url = slug($path_parts['filename']).'_'.$i.'.png';
				$i++;
			}

			if($path_parts['extension']=="jpg" || $path_parts['extension']=="jpeg"){
				$src = imagecreatefromjpeg($_FILES['avatar']['tmp_name']);
			}elseif($path_parts['extension']=="png"){
				$src = imagecreatefrompng($_FILES['avatar']['tmp_name']);
			}elseif($path_parts['extension']=="webp"){
				$src = imagecreatefromwebp($_FILES['avatar']['tmp_name']);
			}

			[$width, $height] = getimagesize($_FILES['avatar']['tmp_name']);

			if($height >= 80){
				$newheight = 80;
			}else{
				$newheight = $height;
			}
			$newwidth = round($newheight / $height * $width);

			$tmp=imagecreatetruecolor($newwidth,$newheight);
			if($path_parts['extension']=="png"){
				imagesavealpha($tmp, true);
				$color = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
				imagefill($tmp, 0, 0, $color);
			}
			imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight, $width,$height);

			imagepng($tmp,_FOLDER_AVATARS_.$url);
			imagedestroy($src);

			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET avatar=:avatar WHERE id=:id LIMIT 1');
			$sth->bindValue(':avatar', $url, PDO::PARAM_STR);
			$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
			$sth->execute();
			$this->user_data['avatar'] = $url;
		}
	}

	public static function removeAvatar(int $user_id): void{
		global $user;
        $db = \App\Core\App::db();

		$sth = $db->prepare('SELECT avatar FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $user_id, PDO::PARAM_INT);
		$sth->execute();
		$old_avatar = $sth->fetch(PDO::FETCH_ASSOC)['avatar'];

		if($old_avatar){
			chmod(_FOLDER_AVATARS_, 0777);
			unlink(_FOLDER_AVATARS_.$old_avatar);
			chmod(_FOLDER_AVATARS_, 0755);
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET avatar="" WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $user_id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function remove(int $id): void{
		$db = \App\Core\App::db();
		static::removeAvatar($id);
		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'offer WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->execute();
		foreach($sth as $row){;
			offer::remove($row['id']);
		}
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'reset_password WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->execute();
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'session_user WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->execute();
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'clipboard WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->execute();
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function logUserLoginAndNotify(int $userId, string $username, string $email): void {
		$db = \App\Core\App::db();
		$ip = getClientIp();

		// Sprawdzamy czy ten adres IP był już używany przez tego użytkownika
		$sth = $db->prepare('SELECT COUNT(1) FROM `'._DB_PREFIX_.'logs_user` WHERE `user_id`=:user_id AND `ip`=:ip');
		$sth->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$sth->bindValue(':ip', $ip, PDO::PARAM_STR);
		$sth->execute();
		$has_logged_before = ($sth->fetchColumn() > 0);

		// Zapisanie logu logowania
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'logs_user`(`user_id`, `ip`, `date`) VALUES (:user_id,:ip,NOW())');
		$sth->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$sth->bindValue(':ip', $ip, PDO::PARAM_STR);
		$sth->execute();

		// Jeśli IP jest nowe, wyślij powiadomienie e-mail
		if (!$has_logged_before) {
			sendMail('new_device_login', $email, [
				'username' => $username,
				'ip' => $ip,
				'date' => date('Y-m-d H:i:s'),
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
			]);
		}
	}

	public function requestMagicLink(array $data): void {
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();

		// Weryfikacja captcha
		if (empty($data['captcha']) || !isset($_SESSION['captcha']) || strtolower((string) $data['captcha']) != strtolower((string) $_SESSION['captcha'])) {
			throw new Exception(lang('Invalid captcha code.'));
		}

		// Walidacja e-mail
		$email = trim((string) $data['email']);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new Exception(lang('Incorrect e-mail address'));
		}

		// Znalezienie użytkownika
		$sth = $db->prepare('SELECT * FROM `'._DB_PREFIX_.'user` WHERE `email`=:email LIMIT 1');
		$sth->bindValue(':email', $email, PDO::PARAM_STR);
		$sth->execute();
		$user_data = $sth->fetch(PDO::FETCH_ASSOC);

		if (!$user_data) {
			throw new Exception(lang('The user with this email address does not exist.'));
		}

		if ($user_data['active'] != 1) {
			throw new Exception(lang('The account has not been activated yet.'));
		}

		// Generowanie tokena i ważności (15 minut)
		$token = bin2hex(random_bytes(32));
		$expires = date('Y-m-d H:i:s', time() + 900);

		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET `magic_link_token`=:token, `magic_link_expires`=:expires WHERE `id`=:id');
		$sth->bindValue(':token', $token, PDO::PARAM_STR);
		$sth->bindValue(':expires', $expires, PDO::PARAM_STR);
		$sth->bindValue(':id', $user_data['id'], PDO::PARAM_INT);
		$sth->execute();

		// Wysłanie maila
		sendMail('magic_link', $email, [
			'username' => $user_data['username'],
			'magic_link' => path('login') . '?magic_token=' . $token
		]);
	}

	public function loginWithMagicToken($token): void {
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();

		$sth = $db->prepare('SELECT * FROM `'._DB_PREFIX_.'user` WHERE `magic_link_token`=:token AND `magic_link_expires` > NOW() AND `active`=1 LIMIT 1');
		$sth->bindValue(':token', $token, PDO::PARAM_STR);
		$sth->execute();
		$user = $sth->fetch(PDO::FETCH_ASSOC);

		if (!$user) {
			throw new Exception(lang('Invalid or expired magic login link.'));
		}

		// Usunięcie tokena (jednorazowe użycie)
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET `magic_link_token` = NULL, `magic_link_expires` = NULL WHERE `id`=:id');
		$sth->bindValue(':id', $user['id'], PDO::PARAM_INT);
		$sth->execute();

		// Logowanie
		regenerateSessionId();
		$_SESSION['user']['id'] = $user['id'];
		$_SESSION['user']['session_code'] = bin2hex(random_bytes(32));
		$_SESSION['user']['ip'] = getClientIp();
		$_SESSION['user']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'session_user`(`user_id`, `code`, `ip`, `date`) VALUES (:user_id,:code,:ip,NOW())');
		$sth->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
		$sth->bindValue(':code', $_SESSION['user']['session_code'], PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();

		static::logUserLoginAndNotify((int)$user['id'], (string)$user['username'], (string)$user['email']);

		header("Location: " . $settings['base_url']);
		die('redirect');
	}
}
