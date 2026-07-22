<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

function sitemap_generator(): void{
	ini_set('memory_limit', '256M');

	global $links;
	$settings = \App\Core\App::settings();
	$db = \App\Core\App::db();

	$sitemapFile = __DIR__."/../sitemap.xml";
	$sitemap_links = [];

	// Strona główna
	$sitemap_links[] = [
		'url' => $settings['base_url'],
		'lastmod' => date('c')
	];

	// Podstrony statyczne
	$blacklist = ['logowanie', 'captcha', 'schowek', 'dodaj', 'kontakt', 'czat', 'sugestie', 'info', 'feed', 'my_offers', 'edit', 'settings', 'profile'];
	if (!$settings['enable_articles']) {
		$blacklist[] = 'articles';
	}
	$blacklist[] = 'article'; // Wykluczamy dynamiczne podstrony pojedynczego artykułu

	foreach($links as $link => $url){
		if (!in_array($link, $blacklist, true)) {
			$sitemap_links[] = [
				'url' => $settings['base_url'] . '/' . $url,
				'lastmod' => date('c')
			];
		}
	}

	// Oferty
	$sth = $db->query('SELECT id, slug, date, promoted FROM '._DB_PREFIX_.'offer WHERE active=1 ORDER BY promoted desc, id desc');
	while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		$sitemap_links[] = [
			'url' => path('offer', $row['id'], $row['slug']),
			'lastmod' => date('c', strtotime($row['date']))
		];
	}

	// Artykuły
	if($settings['enable_articles']){
		$sth = $db->query('SELECT id, slug, date FROM '._DB_PREFIX_.'article ORDER BY date desc');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$sitemap_links[] = [
				'url' => path('article', $row['id'], $row['slug']),
				'lastmod' => date('c', strtotime($row['date']))
			];
		}
	}

	$fh = fopen($sitemapFile, 'w');

	$html = '<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
	fwrite($fh, $html);

	foreach($sitemap_links as $row){
		$entry = "\n";
		$entry .= '<url>';
		$entry .= "\n";
		$entry .= '  <loc>' . htmlspecialchars($row['url'], ENT_XML1, 'UTF-8') . '</loc>';
		$entry .= "\n";
		$entry .= '  <lastmod>' . $row['lastmod'] . '</lastmod>';
		$entry .= "\n";
		$entry .= '</url>';
		fwrite($fh, $entry);
	}

	$html = "\n" . '</urlset>';
	fwrite($fh, $html);
	fclose($fh);
}
