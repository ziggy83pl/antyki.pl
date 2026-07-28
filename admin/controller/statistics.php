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

	// Financial payments summary
	$statistics['payments_p24_count'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'log_przelewy24 WHERE status="1" OR status="true"');
	$statistics['payments_paypal_count'] = $getStatCount('SELECT COUNT(1) FROM '._DB_PREFIX_.'log_paypal WHERE status="1" OR status="Completed"');

	// Top Categories distribution
	$topCategories = [];
	try {
		$sthCat = $db->query('SELECT c.name, COUNT(o.id) as total FROM '._DB_PREFIX_.'category c LEFT JOIN '._DB_PREFIX_.'offer o ON o.category_id = c.id GROUP BY c.id ORDER BY total DESC LIMIT 6');
		if ($sthCat) {
			$topCategories = $sthCat->fetchAll(PDO::FETCH_ASSOC);
		}
	} catch (\Throwable $e) {}

	// Moderator Activity Stats
	$moderatorActivity = [];
	try {
		$sthMod = $db->query('SELECT admin_username, COUNT(1) as total_actions FROM '._DB_PREFIX_.'admin_activity_log GROUP BY admin_username ORDER BY total_actions DESC LIMIT 10');
		if ($sthMod) {
			$moderatorActivity = $sthMod->fetchAll(PDO::FETCH_ASSOC);
		}
	} catch (\Throwable $e) {}

	$render_variables['statistics'] = $statistics;
	$render_variables['top_categories'] = $topCategories;
	$render_variables['moderator_activity'] = $moderatorActivity;

	$title = lang('Statistics').' - '.$title_default;
}
