<?php

require_once(realpath(dirname(__FILE__)).'/config/config.php');

if (php_sapi_name() !== 'cli') {
	$cron_token = getenv('CRON_TOKEN') ?: ($settings['cron_token'] ?? 'TAJNY_TOKEN_123');
	if (empty($_GET['token']) || $_GET['token'] !== $cron_token) {
		header('HTTP/1.0 403 Forbidden');
		die('Access denied');
	}
}

// Pseudo-cron Lock (InfinityFree support)
$now = time();
$last_run = isset($settings['cron_last_daily']) ? (int)$settings['cron_last_daily'] : 0;
if (php_sapi_name() !== 'cli' && !isset($_GET['force']) && ($now - $last_run) < 3600) {
	echo "Cron daily was already executed recently. Exiting.\n";
	exit(0);
}
$db->query("UPDATE `"._DB_PREFIX_."settings` SET value = UNIX_TIMESTAMP() WHERE name = 'cron_last_daily'");

function cron(){
	global $settings, $db;

	$db->query('DELETE FROM '._DB_PREFIX_.'admin_session WHERE date<(CURDATE() - INTERVAL 1 DAY)');

	/* Modified: Rotate/clean up logs older than 90 days */
	$db->query('DELETE FROM '._DB_PREFIX_.'admin_logs WHERE date<(CURDATE() - INTERVAL 90 DAY)');
	$db->query('DELETE FROM '._DB_PREFIX_.'logs_offer WHERE date<(CURDATE() - INTERVAL 90 DAY)');
	$db->query('DELETE FROM '._DB_PREFIX_.'logs_user WHERE date<(CURDATE() - INTERVAL 90 DAY)');
	$db->query('DELETE FROM '._DB_PREFIX_.'logs_mail WHERE date<(CURDATE() - INTERVAL 90 DAY)');

	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'photo WHERE (offer_id=0 OR offer_id IS NULL) AND date<(CURDATE() - INTERVAL 1 DAY)');
	foreach($sth as $row){
		unlink(_FOLDER_PHOTOS_.$row['folder'].$row['thumb']);
		unlink(_FOLDER_PHOTOS_.$row['folder'].$row['url']);
	}
	$db->query('DELETE FROM '._DB_PREFIX_.'photo WHERE (offer_id=0 OR offer_id IS NULL) AND date<(CURDATE() - INTERVAL 1 DAY)');

	$db->query('UPDATE '._DB_PREFIX_.'reset_password SET active=0 WHERE active=1 and date<(CURDATE() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM '._DB_PREFIX_.'user WHERE active=0 and date<(CURDATE() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM '._DB_PREFIX_.'session_offer WHERE date<(CURDATE() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM '._DB_PREFIX_.'session_user WHERE date<(CURDATE() - INTERVAL 1 DAY)');

	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'offer WHERE promoted=1 and promoted_date_end<CURDATE()');
	foreach($sth as $row){
		mailsQueueAdd('finish_promote',$row['email'],['offer_name'=>$row['name'], 'offer_url'=>$row['id'].','.$row['slug'], 'user_id'=>$row['user_id']],3);
	}
	$db->query('UPDATE '._DB_PREFIX_.'offer SET promoted=0 WHERE promoted=1 and promoted_date_end<CURDATE()');

	$sth = $db->query('SELECT id FROM '._DB_PREFIX_.'offer WHERE active=0 and date_finish<(CURDATE() - INTERVAL '.$settings['days_to_remove'].' DAY)');
	foreach($sth as $row){
		\App\Offer::remove($row['id']);
	}

	$offers_deactivate = [];
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'offer WHERE active=1 and date_finish<CURDATE()');
	foreach($sth as $row){
		\App\Offer::deactivate($row['id']);
		if (strpos($row['code'], 'imported_') !== 0) {
			$offers_deactivate[$row['email']][] = $row;
		}
	}
	foreach($offers_deactivate as $email=>$offers){
		if($offers[0]['user_id']){
			mailsQueueAdd('offers_finish',$email,['offers_list'=>$offers, 'user_id'=>$offers[0]['user_id']],4);
		}else{
			mailsQueueAdd('offers_finish_not_logged',$email,['offers_list'=>$offers],4);
		}
	}

	refresh_ecu();

}
cron();

if($settings['generate_sitemap']){
	include(realpath(dirname(__FILE__)).'/php/sitemap_generator.php');
	sitemap_generator();
}
