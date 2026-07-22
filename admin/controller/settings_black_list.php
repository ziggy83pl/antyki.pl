<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action']=='save_settings_black_list' and checkToken('admin_save_settings_black_list')){
		
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name=:name limit 1');

		// 1. Process E-mail Blacklist
		$email_inputs = isset($_POST['black_list_email_emails']) && is_array($_POST['black_list_email_emails']) ? $_POST['black_list_email_emails'] : [];
		$email_notes = isset($_POST['black_list_email_notes']) && is_array($_POST['black_list_email_notes']) ? $_POST['black_list_email_notes'] : [];
		
		$email_list = [];
		foreach ($email_inputs as $index => $email) {
			$email = trim($email);
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$note = isset($email_notes[$index]) ? trim(strip_tags($email_notes[$index])) : '';
				$email_list[$email] = $note;
			}
		}
		ksort($email_list);
		$email_json = json_encode($email_list, JSON_UNESCAPED_UNICODE);
		
		$sth->bindValue(':value', $email_json, PDO::PARAM_STR);
		$sth->bindValue(':name', 'black_list_email', PDO::PARAM_STR);
		$sth->execute();

		// 2. Process IP Blacklist
		$ip_inputs = isset($_POST['black_list_ip_ips']) && is_array($_POST['black_list_ip_ips']) ? $_POST['black_list_ip_ips'] : [];
		$ip_notes = isset($_POST['black_list_ip_notes']) && is_array($_POST['black_list_ip_notes']) ? $_POST['black_list_ip_notes'] : [];
		
		$ip_list = [];
		foreach ($ip_inputs as $index => $ip) {
			$ip = trim($ip);
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				$note = isset($ip_notes[$index]) ? trim(strip_tags($ip_notes[$index])) : '';
				$ip_list[$ip] = $note;
			}
		}
		ksort($ip_list);
		$ip_json = json_encode($ip_list, JSON_UNESCAPED_UNICODE);
		
		$sth->bindValue(':value', $ip_json, PDO::PARAM_STR);
		$sth->bindValue(':name', 'black_list_ip', PDO::PARAM_STR);
		$sth->execute();

		// 3. Process Exclude IP views
		// Ensure the setting row exists in settings table before update
		$check_sth = $db->prepare('SELECT COUNT(1) FROM `'._DB_PREFIX_.'settings` WHERE name = "exclude_ip_views"');
		$check_sth->execute();
		if ($check_sth->fetchColumn() == 0) {
			$db->query('INSERT INTO `'._DB_PREFIX_.'settings` (name, value) VALUES ("exclude_ip_views", "")');
		}

		$exclude_ip_views_ips = isset($_POST['exclude_ip_views_ips']) && is_array($_POST['exclude_ip_views_ips']) ? $_POST['exclude_ip_views_ips'] : [];
		$exclude_ip_views_notes = isset($_POST['exclude_ip_views_notes']) && is_array($_POST['exclude_ip_views_notes']) ? $_POST['exclude_ip_views_notes'] : [];
		
		$exclude_ip_views_list = [];
		foreach ($exclude_ip_views_ips as $index => $ip) {
			$ip = trim($ip);
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				$note = isset($exclude_ip_views_notes[$index]) ? trim(strip_tags($exclude_ip_views_notes[$index])) : '';
				$exclude_ip_views_list[$ip] = $note;
			}
		}
		ksort($exclude_ip_views_list);
		$exclude_ip_views_json = json_encode($exclude_ip_views_list, JSON_UNESCAPED_UNICODE);

		$sth->bindValue(':value', $exclude_ip_views_json, PDO::PARAM_STR);
		$sth->bindValue(':name', 'exclude_ip_views', PDO::PARAM_STR);
		$sth->execute();
		
		// 4. Process Blacklist Words
		$sth->bindValue(':value', $_POST['black_list_words'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'black_list_words', PDO::PARAM_STR);
		$sth->execute();

		// Sync black_list_email table
		$db->exec('TRUNCATE TABLE `'._DB_PREFIX_.'black_list_email`');
		if (!empty($email_list)) {
			$ins_stmt = $db->prepare('INSERT IGNORE INTO `'._DB_PREFIX_.'black_list_email` (email) VALUES (:email)');
			foreach ($email_list as $email => $note) {
				$ins_stmt->execute([':email' => $email]);
			}
		}

		// Sync black_list_ip table
		$db->exec('TRUNCATE TABLE `'._DB_PREFIX_.'black_list_ip`');
		if (!empty($ip_list)) {
			$ins_stmt = $db->prepare('INSERT IGNORE INTO `'._DB_PREFIX_.'black_list_ip` (ip) VALUES (:ip)');
			foreach ($ip_list as $ip => $note) {
				$ins_stmt->execute([':ip' => $ip]);
			}
		}
		
		getSettings();
		$render_variables['alert_success'][] = lang('Changes have been saved');
	}
	
	// Load and decode Black list email
	$black_list_email_list = [];
	if (!empty(\App\Core\App::settings()['black_list_email'])) {
		$decoded = json_decode(\App\Core\App::settings()['black_list_email'], true);
		if (is_array($decoded)) {
			$black_list_email_list = $decoded;
		} else {
			$emails = array_map('trim', explode(PHP_EOL, \App\Core\App::settings()['black_list_email']));
			foreach ($emails as $email) {
				if ($email !== '') {
					$black_list_email_list[$email] = '';
				}
			}
		}
	}
	$render_variables['black_list_email_list'] = $black_list_email_list;

	// Load and decode Black list IP
	$black_list_ip_list = [];
	if (!empty(\App\Core\App::settings()['black_list_ip'])) {
		$decoded = json_decode(\App\Core\App::settings()['black_list_ip'], true);
		if (is_array($decoded)) {
			$black_list_ip_list = $decoded;
		} else {
			$ips = array_map('trim', explode(PHP_EOL, \App\Core\App::settings()['black_list_ip']));
			foreach ($ips as $ip) {
				if ($ip !== '') {
					$black_list_ip_list[$ip] = '';
				}
			}
		}
	}
	$render_variables['black_list_ip_list'] = $black_list_ip_list;

	// Load and decode Excluded IP views
	$exclude_ip_views_list = [];
	if (!empty(\App\Core\App::settings()['exclude_ip_views'])) {
		$decoded = json_decode(\App\Core\App::settings()['exclude_ip_views'], true);
		if (is_array($decoded)) {
			$exclude_ip_views_list = $decoded;
		} else {
			$ips = array_map('trim', explode(PHP_EOL, \App\Core\App::settings()['exclude_ip_views']));
			foreach ($ips as $ip) {
				if ($ip !== '') {
					$exclude_ip_views_list[$ip] = '';
				}
			}
		}
	}
	$render_variables['exclude_ip_views_list'] = $exclude_ip_views_list;

	$title = lang('Black list').' - '.$title_default;
}
