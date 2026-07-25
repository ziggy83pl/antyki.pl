<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	$getStatCount = function(string $query) use ($db): int {
		try {
			$sth = $db->query($query);
			return $sth ? (int)$sth->fetchColumn() : 0;
		} catch (\Throwable $e) {
			return 0;
		}
	};

	$statistics['users'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'user');
	$statistics['users_register_fb'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'user WHERE register_fb=1');
	$statistics['users_register_google'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'user WHERE register_google=1');
	$statistics['offers'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'offer');
	$statistics['offers_active'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'offer WHERE active=1');
	$statistics['logs_mails'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_mail');
	$statistics['logs_offers'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_offer');
	$statistics['logs_users'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_user');
	$statistics['photos'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'photo');
	$statistics['categories'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'category');
	$statistics['reset_password'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'reset_password');
	$statistics['mails_queue'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'mails_queue');
	$statistics['offers_promoted'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'offer WHERE promoted=1 AND promoted_date_end >= NOW()');
	$statistics['black_list_ip'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'black_list_ip');
	$statistics['black_list_email'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'black_list_email');

	$render_variables['statistics'] = $statistics;

	$title = lang('Statistics').' - '.$title_default;
}
