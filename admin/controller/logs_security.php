<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	// Akcja odblokowania IP
	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='unblock_ip' and !empty($_POST['ip']) and checkToken('admin_unblock_ip')){
			$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'admin_logs` WHERE ip=:ip AND logged=0');
			$sth->bindValue(':ip', $_POST['ip'], PDO::PARAM_STR);
			$sth->execute();
			$render_variables['alert_success'][] = 'Adres IP '.$_POST['ip'].' został pomyślnie odblokowany.';
		} elseif($_POST['action']=='clear_logs' and checkToken('admin_clear_logs')) {
			$db->query('TRUNCATE `'._DB_PREFIX_.'admin_logs`');
			$render_variables['alert_success'][] = 'Historia logowań została wyczyszczona.';
		}
	}
	
	// Ustawienia blokad z fallbackiem
	$BLOCK_AFTER_ATTEMPTS   = isset(\App\Core\App::settings()['security_block_attempts']) ? (int)\App\Core\App::settings()['security_block_attempts'] : 10;
	$BLOCK_DURATION_MINUTES = isset(\App\Core\App::settings()['security_block_minutes']) ? (int)\App\Core\App::settings()['security_block_minutes'] : 30;

	// Pobierz zablokowane adresy IP
	$blocked_ips = [];
	$sth_blocked = $db->prepare('
		SELECT ip, COUNT(1) as attempts, MAX(date) as last_attempt 
		FROM `'._DB_PREFIX_.'admin_logs` 
		WHERE logged=0 AND date > DATE_ADD(NOW(), INTERVAL -:block_minutes MINUTE)
		GROUP BY ip 
		HAVING attempts >= :block_attempts
		ORDER BY last_attempt DESC
	');
	$sth_blocked->bindValue(':block_minutes', $BLOCK_DURATION_MINUTES, PDO::PARAM_INT);
	$sth_blocked->bindValue(':block_attempts', $BLOCK_AFTER_ATTEMPTS, PDO::PARAM_INT);
	$sth_blocked->execute();
	while ($row = $sth_blocked->fetch(PDO::FETCH_ASSOC)){
		$blocked_ips[] = $row;
	}
	$render_variables['blocked_ips'] = $blocked_ips;
	
	// Pobierz ostatnie logi z admin_logs
	$limit = 100;
	$admin_logs_list = [];
	$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM `'._DB_PREFIX_.'admin_logs` ORDER BY date DESC LIMIT :limit_from, :limit_to');
	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
	$sth->execute();
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
		$admin_logs_list[] = $row;
	}
	$render_variables['admin_logs_list'] = $admin_logs_list;

	generatePagination($limit);
	
	$title = 'Logi i Zablokowane IP - '.$title_default;
}
