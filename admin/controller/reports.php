<?php

if (!isset(\App\Core\App::settings()['base_url'])) {
	die('Access denied!');
}

if ($admin->is_logged()) {
	
	// Handle resolving report
	if (isset($_GET['action']) && $_GET['action'] === 'resolve' && isset($_GET['id'])) {
		$reportId = (int)$_GET['id'];
		
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'abuse_reports` SET status = 1 WHERE id = :id');
		$sth->bindValue(':id', $reportId, PDO::PARAM_INT);
		$sth->execute();
		
		header('Location: ?controller=reports&msg=resolved');
		exit;
	}

	// Fetch reports with offer details
	$sql = '
		SELECT r.*, o.name as offer_name, o.slug as offer_slug 
		FROM `'._DB_PREFIX_.'abuse_reports` r 
		LEFT JOIN `'._DB_PREFIX_.'offer` o ON r.offer_id = o.id 
		ORDER BY r.status ASC, r.created_at DESC
	';
	$sth = $db->query($sql);
	$reports = $sth->fetchAll(PDO::FETCH_ASSOC);

	$render_variables['reports'] = $reports;
}
