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
$last_run = isset($settings['cron_last_10min']) ? (int)$settings['cron_last_10min'] : 0;
if (php_sapi_name() !== 'cli' && !isset($_GET['force']) && ($now - $last_run) < 180) {
	echo "Cron 10min was already executed recently. Exiting.\n";
	exit(0);
}
$db->query("UPDATE `"._DB_PREFIX_."settings` SET value = UNIX_TIMESTAMP() WHERE name = 'cron_last_10min'");

function cron_10min(){
	global $db, $settings;

	// 1. Process mails queue
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'mails_queue ORDER BY priority DESC, id LIMIT 10');
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
		if(sendMail($row['action'], $row['receiver'], unserialize($row['data']))){
			$sth2 = $db->prepare('DELETE FROM `'._DB_PREFIX_.'mails_queue` WHERE id=:id LIMIT 1');
			$sth2->bindValue(':id', $row['id'], PDO::PARAM_INT);
			$sth2->execute();
		}
	}

	// 2. Process alerts for new offers
	$last_id = isset($settings['last_alert_offer_id']) ? (int)$settings['last_alert_offer_id'] : 0;
	if ($last_id === 0) {
		// Initialize setting if not exists with the current maximum offer ID
		$sth_max = $db->query('SELECT MAX(id) FROM `'._DB_PREFIX_.'offer`');
		$max_id = (int)$sth_max->fetchColumn();
		
		$sth_ins = $db->prepare("INSERT INTO `" . _DB_PREFIX_ . "settings` (name, value) VALUES ('last_alert_offer_id', :max_id) 
				   ON DUPLICATE KEY UPDATE value=:max_id");
		$sth_ins->bindValue(':max_id', $max_id, PDO::PARAM_INT);
		$sth_ins->execute();
		$last_id = $max_id;
	}

	// Fetch new active offers since last run
	$sth_new = $db->prepare('SELECT id, name, slug, category_id FROM `'._DB_PREFIX_.'offer` WHERE id > :last_id AND active=1 ORDER BY id ASC');
	$sth_new->bindValue(':last_id', $last_id, PDO::PARAM_INT);
	$sth_new->execute();
	$new_offers = $sth_new->fetchAll(PDO::FETCH_ASSOC);

	if (!empty($new_offers)) {
		// Update last checked offer ID
		$max_new_id = (int)end($new_offers)['id'];
		$sth_update = $db->prepare("UPDATE `" . _DB_PREFIX_ . "settings` SET value=:max_new_id WHERE name='last_alert_offer_id'");
		$sth_update->bindValue(':max_new_id', $max_new_id, PDO::PARAM_INT);
		$sth_update->execute();

		// Fetch all active subscribers / alerts
		$sth_alerts = $db->query('SELECT email, category_id FROM `'._DB_PREFIX_.'alerts`');
		$alerts = $sth_alerts->fetchAll(PDO::FETCH_ASSOC);

		// Group offers by category_id
		$offers_by_category = [];
		foreach ($new_offers as $offer) {
			$offers_by_category[$offer['category_id']][] = $offer;
		}

		// Match alerts to offers
		foreach ($alerts as $alert) {
			$email = $alert['email'];
			$cat_id = (int)$alert['category_id'];
			
			// Match exact category
			if (isset($offers_by_category[$cat_id])) {
				mailsQueueAdd('new_offer_alert', $email, [
					'offers_list' => $offers_by_category[$cat_id]
				]);
			}
		}
	}

}
cron_10min();
