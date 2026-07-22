<?php
/**
 * Admin controller entry.
 * Now loads dashboard statistics and latest elements.
 */

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	$dashboard = [];

	// 1. Stats
	$offer_where = [];
	$user_where = [];
	$params = [];

	if (!empty($_GET['date_from'])) {
		$offer_where[] = 'date >= :date_from';
		$user_where[] = 'date >= :date_from';
		$params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
	}
	if (!empty($_GET['date_to'])) {
		$offer_where[] = 'date <= :date_to';
		$user_where[] = 'date <= :date_to';
		$params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
	}

	$sql_active = 'SELECT COUNT(1) FROM '._DB_PREFIX_.'offer WHERE active=1';
	$sql_pending = 'SELECT COUNT(1) FROM '._DB_PREFIX_.'offer WHERE active=0';
	$sql_users = 'SELECT COUNT(1) FROM '._DB_PREFIX_.'user';

	if (!empty($offer_where)) {
		$sql_active .= ' AND ' . implode(' AND ', $offer_where);
		$sql_pending .= ' AND ' . implode(' AND ', $offer_where);
	}
	if (!empty($user_where)) {
		$sql_users .= ' WHERE ' . implode(' AND ', $user_where);
	}

	$sth = $db->prepare($sql_active);
	foreach ($params as $key => $val) {
		$sth->bindValue($key, $val, PDO::PARAM_STR);
	}
	$sth->execute();
	$dashboard['offers_active'] = $sth->fetchColumn();

	$sth = $db->prepare($sql_pending);
	foreach ($params as $key => $val) {
		$sth->bindValue($key, $val, PDO::PARAM_STR);
	}
	$sth->execute();
	$dashboard['offers_pending'] = $sth->fetchColumn();

	$sth = $db->prepare($sql_users);
	foreach ($params as $key => $val) {
		$sth->bindValue($key, $val, PDO::PARAM_STR);
	}
	$sth->execute();
	$dashboard['users_total'] = $sth->fetchColumn();

	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'mails_queue');
	$dashboard['mails_queue'] = $sth->fetchColumn();

	// 2. Latest offers
	$latest_offer_where = [];
	if (isset($_GET['only_pending']) && $_GET['only_pending'] == '1') {
		$latest_offer_where[] = 'active=0';
	}
	if (!empty($_GET['date_from'])) {
		$latest_offer_where[] = 'date >= :date_from';
	}
	if (!empty($_GET['date_to'])) {
		$latest_offer_where[] = 'date <= :date_to';
	}

	$sql_latest_offers = 'SELECT id, name, date, active, slug FROM '._DB_PREFIX_.'offer';
	if (!empty($latest_offer_where)) {
		$sql_latest_offers .= ' WHERE ' . implode(' AND ', $latest_offer_where);
	}
	$sql_latest_offers .= ' ORDER BY date DESC LIMIT 5';

	$sth = $db->prepare($sql_latest_offers);
	foreach ($params as $key => $val) {
		$sth->bindValue($key, $val, PDO::PARAM_STR);
	}
	$sth->execute();
	$dashboard['latest_offers'] = $sth->fetchAll(PDO::FETCH_ASSOC);

	// 3. Latest users
	$latest_user_where = [];
	if (!empty($_GET['date_from'])) {
		$latest_user_where[] = 'date >= :date_from';
	}
	if (!empty($_GET['date_to'])) {
		$latest_user_where[] = 'date <= :date_to';
	}

	$sql_latest_users = 'SELECT id, username, email, date, active FROM '._DB_PREFIX_.'user';
	if (!empty($latest_user_where)) {
		$sql_latest_users .= ' WHERE ' . implode(' AND ', $latest_user_where);
	}
	$sql_latest_users .= ' ORDER BY date DESC LIMIT 5';

	$sth = $db->prepare($sql_latest_users);
	foreach ($params as $key => $val) {
		$sth->bindValue($key, $val, PDO::PARAM_STR);
	}
	$sth->execute();
	$dashboard['latest_users'] = $sth->fetchAll(PDO::FETCH_ASSOC);

	$dashboard['stats'] = $admin->getDashboardStats(14);

	$render_variables['dashboard'] = $dashboard;
}
