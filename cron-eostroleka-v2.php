<?php

require_once(realpath(dirname(__FILE__)).'/config/config.php');

if (php_sapi_name() !== 'cli') {
	session_start();
	$cron_token = getenv('CRON_TOKEN') ?: ($settings['cron_token'] ?? 'TAJNY_TOKEN_123');
	$is_admin = !empty($_SESSION['admin']);
	$valid_token = !empty($_GET['token']) && $_GET['token'] === $cron_token;
	if (!$is_admin && !$valid_token) {
		header('HTTP/1.0 403 Forbidden');
		die('Access denied');
	}
}

echo "Scraper sprzedajemy.pl (Antyki, Sztuka, Kolekcje) started.\n";

$is_forced = !empty($_GET['force']) || (isset($argv) && in_array('force=1', $argv));

$scraper_enabled = isset($settings['scraper_eostroleka_enabled']) ? (bool)$settings['scraper_eostroleka_enabled'] : false;
if (!$scraper_enabled && !$is_forced) {
	echo "Scraper (sprzedajemy.pl) is disabled in settings.\n";
	exit(0);
}

$now = time();
$last_run = isset($settings['cron_last_scraper_eostroleka']) ? (int)$settings['cron_last_scraper_eostroleka'] : 0;
if (php_sapi_name() !== 'cli' && !$is_forced && ($now - $last_run) < 300) {
	echo "Scraper was already executed recently (lock active). Exiting.\n";
	exit(0);
}
$db->query("UPDATE `"._DB_PREFIX_."settings` SET value = UNIX_TIMESTAMP() WHERE name = 'cron_last_scraper_eostroleka'");

$display_days = isset($settings['scraper_eostroleka_display_days']) ? (int)$settings['scraper_eostroleka_display_days'] : 7;
if ($display_days <= 0) $display_days = 7;

$max_imports = isset($settings['scraper_eostroleka_max_imports']) ? (int)$settings['scraper_eostroleka_max_imports'] : 10;
if ($max_imports <= 0) $max_imports = 10;

echo "Configured display days: $display_days\n";
echo "Configured maximum imports per run: $max_imports\n";

$target_url = "https://sprzedajemy.pl/antyki-sztuka-kolekcje";
echo "Fetching listings from $target_url ...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$html = curl_exec($ch);
curl_close($ch);

if (empty($html)) {
	echo "Empty response from sprzedajemy.pl.\n";
	exit(1);
}

libxml_use_internal_errors(true);
$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();

$xpath = new DOMXPath($doc);
$links = [];
$seen = [];

foreach ($xpath->query("//a[contains(@href, '-nr')]") as $a) {
	$href = trim($a->getAttribute("href"));
	if (!empty($href)) {
		if (strpos($href, 'http') === false) {
			$href = 'https://sprzedajemy.pl/' . ltrim($href, '/');
		}
		if (!isset($seen[$href])) {
			$seen[$href] = true;
			$links[] = $href;
		}
	}
}

if (empty($links)) {
	echo "No advertisement links found on category page.\n";
	exit(0);
}

echo "Found " . count($links) . " advertisement links.\n";

// Category resolution logic with keyword mapping
function resolveAntykiCategoryIdForSprzedajemy($title, $description) {
	global $db;
	
	// Try matching existing categories or create if needed
	$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'category WHERE name LIKE :name1 OR name LIKE :name2 LIMIT 1');
	$sth->bindValue(':name1', '%Antyk%', PDO::PARAM_STR);
	$sth->bindValue(':name2', '%Militaria%', PDO::PARAM_STR);
	$sth->execute();
	$cat_id = $sth->fetchColumn();
	if ($cat_id) {
		return (int)$cat_id;
	}

	$parent_category_id = 21;
	$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'category WHERE category_id = :parent_id AND name = :name LIMIT 1');
	$sth->bindValue(':parent_id', $parent_category_id, PDO::PARAM_INT);
	$sth->bindValue(':name', 'Inne', PDO::PARAM_STR);
	$sth->execute();
	$cat_id = $sth->fetchColumn();

	if (!$cat_id) {
		$sth = $db->query('SELECT id FROM '._DB_PREFIX_.'category ORDER BY id ASC LIMIT 1');
		$cat_id = $sth->fetchColumn() ?: 1;
	}
	return (int)$cat_id;
}

function processSprzedajemyPhotos($photos, $offer_id) {
	global $db, $settings;
	if (empty($photos)) return;

	$folder = date('Y') . '/' . date('m') . '/';
	$baseDir = _FOLDER_PHOTOS_;
	if (!file_exists($baseDir . date('Y'))) @mkdir($baseDir . date('Y'), 0755, true);
	if (!file_exists($baseDir . $folder)) @mkdir($baseDir . $folder, 0755, true);

	$position = 0;
	$max_photos = isset($settings['photo_max']) ? (int)$settings['photo_max'] : 10;
	if ($max_photos <= 0) $max_photos = 10;

	foreach ($photos as $img_url) {
		if ($position >= $max_photos) break;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $img_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$img_data = curl_exec($ch);
		curl_close($ch);

		if (!$img_data) continue;

		$src = @imagecreatefromstring($img_data);
		if (!$src) continue;

		imagesavealpha($src, true);

		$filename_base = md5(uniqid('', true));
		$url = $filename_base . '.jpg';
		$thumb = $filename_base . '_thumb.jpg';

		$width = imagesx($src);
		$height = imagesy($src);

		$max_w = !empty($settings['photo_max_width']) ? (int)$settings['photo_max_width'] : 1920;
		$max_h = !empty($settings['photo_max_height']) ? (int)$settings['photo_max_height'] : 1080;

		$newwidth = $width;
		$newheight = $height;

		if ($height > $max_h) {
			$newheight = $max_h;
			$newwidth = (int)round($newheight / $height * $width);
		}
		if ($newwidth > $max_w) {
			$newwidth = $max_w;
			$newheight = (int)round($newwidth / $width * $height);
		}

		$tmp_img = imagecreatetruecolor($newwidth, $newheight);
		imagecopyresampled($tmp_img, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
		imagejpeg($tmp_img, $baseDir . $folder . $url, !empty($settings['photo_quality']) ? (int)$settings['photo_quality'] : 85);

		// Create thumbnail
		$th_height = 150;
		$th_width = (int)round($th_height / $newheight * $newwidth);
		$tmp_thumb = imagecreatetruecolor($th_width, $th_height);
		imagecopyresampled($tmp_thumb, $tmp_img, 0, 0, 0, 0, $th_width, $th_height, $newwidth, $newheight);
		imagejpeg($tmp_thumb, $baseDir . $folder . $thumb, 90);

		imagedestroy($tmp_img);
		imagedestroy($tmp_thumb);
		imagedestroy($src);

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'photo`(`user_id`, `offer_id`, `folder`, `thumb`, `url`, `date`, `position`) VALUES (0, :offer_id, :folder, :thumb, :url, NOW(), :position)');
		$sth->bindValue(':offer_id', $offer_id, PDO::PARAM_INT);
		$sth->bindValue(':folder', $folder, PDO::PARAM_STR);
		$sth->bindValue(':thumb', $thumb, PDO::PARAM_STR);
		$sth->bindValue(':url', $url, PDO::PARAM_STR);
		$sth->bindValue(':position', $position, PDO::PARAM_INT);
		$sth->execute();

		$position++;
	}
}

$importedCount = 0;
$skippedCount = 0;

foreach ($links as $itemUrl) {
	if ($importedCount >= $max_imports) {
		echo "Reached maximum import limit ($max_imports). Stopping scraper.\n";
		break;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $itemUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$itemHtml = curl_exec($ch);
	curl_close($ch);

	if (empty($itemHtml)) {
		echo "- [SKIPPED] Failed to fetch item page: $itemUrl\n";
		$skippedCount++;
		continue;
	}

	libxml_clear_errors();
	$itemDoc = new DOMDocument();
	@$itemDoc->loadHTML('<?xml encoding="UTF-8">' . $itemHtml);
	$itemXpath = new DOMXPath($itemDoc);

	$h1Node = $itemXpath->query("//h1");
	$title = $h1Node->length > 0 ? trim($h1Node->item(0)->textContent) : '';

	$descNode = $itemXpath->query("//*[contains(@class, 'offerDescription')] | //*[contains(@class, 'description')]");
	$description = $descNode->length > 0 ? trim($descNode->item(0)->textContent) : '';

	if (empty($title) || empty($description)) {
		echo "- [SKIPPED] Missing title or description: $itemUrl\n";
		$skippedCount++;
		continue;
	}

	// Extract price if available
	$price = null;
	$priceNode = $itemXpath->query("//*[contains(@class, 'price')] | //*[contains(@class, 'offerPrice')]");
	if ($priceNode->length > 0) {
		$priceText = trim($priceNode->item(0)->textContent);
		if (preg_match('/([0-9\s,.]+)/', $priceText, $pm)) {
			$cleanPrice = str_replace([' ', ','], ['', '.'], $pm[1]);
			if (is_numeric($cleanPrice) && (float)$cleanPrice > 0) {
				$price = (float)$cleanPrice;
			}
		}
	}

	// Extract photos
	$photos = [];
	$photoSeen = [];
	foreach ($itemXpath->query("//img[contains(@src, 'sprzedajemy.pl')] | //a[contains(@href, 'sprzedajemy.pl')]") as $img) {
		$src = $img->getAttribute("src") ?: $img->getAttribute("href");
		if (strpos($src, "thumbs") !== false || strpos($src, "orig") !== false) {
			if (!isset($photoSeen[$src])) {
				$photoSeen[$src] = true;
				$photos[] = $src;
			}
		}
	}

	$formattedDescription = '<p>' . nl2br(htmlspecialchars($description)) . '</p>';

	// Check if already exists
	$sthCheck = $db->prepare('SELECT id FROM '._DB_PREFIX_.'offer WHERE name = :name AND description = :description LIMIT 1');
	$sthCheck->bindValue(':name', $title, PDO::PARAM_STR);
	$sthCheck->bindValue(':description', $formattedDescription, PDO::PARAM_STR);
	$sthCheck->execute();
	if ($sthCheck->fetchColumn()) {
		echo "- [SKIPPED] Already exists: " . mb_substr($title, 0, 40) . "\n";
		$skippedCount++;
		continue;
	}

	$category_id = resolveAntykiCategoryIdForSprzedajemy($title, $description);

	$parsedDate = date('Y-m-d');
	$finishTimestamp = strtotime($parsedDate . " + $display_days days");
	$finishDate = date('Y-m-d H:i:s', $finishTimestamp);

	$code = 'imported_' . bin2hex(random_bytes(24));
	$date_start = $parsedDate . ' 00:00:00';
	$date_original = date('Y-m-d H:i:s');

	$sthInsert = $db->prepare('INSERT INTO `'._DB_PREFIX_.'offer` (
		`user_id`, `name`, `slug`, `price`, `price_negotiate`, `price_free`, 
		`address`, `phone`, `email`, `category_id`, `state_id`, `state2_id`, 
		`type_id`, `description`, `active`, `admin_confirmed`, `promoted`, 
		`code`, `ip`, `date_start`, `days`, `date_finish`, `date`
	) VALUES (
		0, :name, :slug, :price, 0, 0, 
		"Polska", NULL, NULL, :category_id, 0, 0, 
		0, :description, 1, 1, 0, 
		:code, "127.0.0.1", :date_start, :days, :date_finish, :date
	)');

	$sthInsert->bindValue(':name', $title, PDO::PARAM_STR);
	$sthInsert->bindValue(':slug', slug($title), PDO::PARAM_STR);
	$sthInsert->bindValue(':price', $price, $price !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
	$sthInsert->bindValue(':category_id', $category_id, PDO::PARAM_INT);
	$sthInsert->bindValue(':description', $formattedDescription, PDO::PARAM_STR);
	$sthInsert->bindValue(':code', $code, PDO::PARAM_STR);
	$sthInsert->bindValue(':date_start', $date_start, PDO::PARAM_STR);
	$sthInsert->bindValue(':days', $display_days, PDO::PARAM_INT);
	$sthInsert->bindValue(':date_finish', $finishDate, PDO::PARAM_STR);
	$sthInsert->bindValue(':date', $date_original, PDO::PARAM_STR);
	$sthInsert->execute();

	$offer_id = (int)$db->lastInsertId();

	if (!empty($photos) && $offer_id > 0) {
		processSprzedajemyPhotos($photos, $offer_id);
	}

	echo "- [IMPORTED] " . $title . " (ID: $offer_id, price: " . ($price ?: 'N/A') . ", photos: " . count($photos) . ")\n";
	$importedCount++;
}

$sthCats = $db->query('SELECT DISTINCT category_id FROM `'._DB_PREFIX_.'offer`');
if ($sthCats) {
	while ($catId = $sthCats->fetchColumn()) {
		\App\Category::refreshCount((int)$catId);
	}
}

echo "Scraper execution finished. Imported: $importedCount, Skipped: $skippedCount.\n";
