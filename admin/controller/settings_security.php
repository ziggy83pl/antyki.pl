<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if(!isset($admin)){
	die('Błąd: Plik panelu administratora został nieprawidłowo wgrany.');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action']=='save_settings'){
		
		// Zapisywanie ustawień z opcją INSERT ON DUPLICATE KEY UPDATE
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'settings` (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value=VALUES(value)');

		// Lista ustawień do zaktualizowania
		$fieldsToUpdate = [
			'security_alert_email'      => $_POST['security_alert_email'] ?? '',
			'security_alert_from'       => $_POST['security_alert_from'] ?? '',
			'security_block_attempts'   => (int)($_POST['security_block_attempts'] ?? 10),
			'security_block_minutes'    => (int)($_POST['security_block_minutes'] ?? 30),
			'security_captcha_attempts' => (int)($_POST['security_captcha_attempts'] ?? 3),
			'security_alert_after'      => (int)($_POST['security_alert_after'] ?? 3),
			'security_session_timeout'  => (int)($_POST['security_session_timeout'] ?? 30),
			'security_fail_delay'       => (int)($_POST['security_fail_delay'] ?? 1),
			'security_2fa_enabled'      => (int)($_POST['security_2fa_enabled'] ?? 0)
		];

		foreach($fieldsToUpdate as $name => $value) {
			$sth->bindValue(':name', $name, PDO::PARAM_STR);
			$sth->bindValue(':value', $value, PDO::PARAM_STR);
			$sth->execute();
		}

		// Przeładuj ustawienia globalne
		getSettings();
		$render_variables['alert_success'][] = 'Ustawienia bezpieczeństwa zostały zapisane.';
	}

	$title = 'Ustawienia bezpieczeństwa - '.$title_default;
}
