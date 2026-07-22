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

echo "Scraper elbordo.pl started.\n";

// Check if scraper is enabled in settings
$scraper_enabled = isset($settings['scraper_enabled']) ? (bool)$settings['scraper_enabled'] : false;
if (!$scraper_enabled && empty($_GET['force'])) {
	echo "Scraper is disabled in settings.\n";
	exit(0);
}

// Pseudo-cron Lock & Rate Limiting (InfinityFree support)
$now = time();
$last_run = isset($settings['cron_last_scraper']) ? (int)$settings['cron_last_scraper'] : 0;
if (php_sapi_name() !== 'cli' && !isset($_GET['force']) && ($now - $last_run) < 300) {
	echo "Scraper was already executed recently (lock active). Exiting.\n";
	exit(0);
}
$db->query("UPDATE `"._DB_PREFIX_."settings` SET value = UNIX_TIMESTAMP() WHERE name = 'cron_last_scraper'");

$display_days = isset($settings['scraper_display_days']) ? (int)$settings['scraper_display_days'] : 7;
if ($display_days <= 0) {
	$display_days = 7;
}
echo "Configured display days: $display_days\n";

$max_imports = isset($settings['scraper_max_imports']) ? (int)$settings['scraper_max_imports'] : 30;
if ($max_imports <= 0) {
	$max_imports = 30;
}
echo "Configured maximum imports per run: $max_imports\n";

$user_profile_url = "https://elbordo.pl/ogloszenia/uzytkownik/1139/dariusz111/";
echo "Fetching profile page from $user_profile_url ...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_profile_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$profile_html = curl_exec($ch);

if (curl_errno($ch)) {
	echo "cURL error fetching profile: " . curl_error($ch) . "\n";
	curl_close($ch);
	exit(1);
}
curl_close($ch);

if (empty($profile_html)) {
	echo "Empty response from user profile page.\n";
	exit(1);
}

libxml_use_internal_errors(true);
$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="UTF-8">' . $profile_html);
libxml_clear_errors();

$xpath = new DOMXPath($doc);
$links = [];
$seen = [];

foreach ($xpath->query("//a[contains(@href, 'ogloszenie-')]") as $a) {
	$href = trim($a->getAttribute("href"));
	if (!empty($href) && !isset($seen[$href])) {
		$seen[$href] = true;
		$links[] = $href;
	}
}

if (empty($links)) {
	echo "No advertisement links found on profile page.\n";
	exit(0);
}

echo "Found " . count($links) . " advertisement links on user profile.\n";

// Helper function to resolve category ID based on name or keywords
function resolveAntykiCategoryId($title, $description) {
	global $db;
	
	// 1. Try to find category with name "Antyki" or "Militaria"
	$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'category WHERE name LIKE :name1 OR name LIKE :name2 LIMIT 1');
	$sth->bindValue(':name1', '%Antyk%', PDO::PARAM_STR);
	$sth->bindValue(':name2', '%Militaria%', PDO::PARAM_STR);
	$sth->execute();
	$cat_id = $sth->fetchColumn();
	if ($cat_id) {
		return (int)$cat_id;
	}

	// 2. Fallback to subcategory "Inne" under category 21 or first existing parent category
	$parent_category_id = 21;
	$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'category WHERE category_id = :parent_id AND name = :name LIMIT 1');
	$sth->bindValue(':parent_id', $parent_category_id, PDO::PARAM_INT);
	$sth->bindValue(':name', 'Inne', PDO::PARAM_STR);
	$sth->execute();
	$cat_id = $sth->fetchColumn();

	if (!$cat_id) {
		// Get any category ID
		$sth = $db->query('SELECT id FROM '._DB_PREFIX_.'category ORDER BY id ASC LIMIT 1');
		$cat_id = $sth->fetchColumn() ?: 1;
	}
	return (int)$cat_id;
}

// Function to process and download offer photos
function processOfferPhotos($photos, $offer_id) {
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
$todayDate = date('Y-m-d');

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

	$title = '';
	$description = '';
	$phone = null;
	$email = null;
	$city = '';
	$stateName = '';
	$price = null;
	$photos = [];

	// Try extracting JSON payload from script tag exportDatavEd1
	if (preg_match('/<script id="exportDatavEd1" type="application\/json">(.*?)<\/script>/s', $itemHtml, $matches)) {
		$jsonData = json_decode($matches[1], true);
		if ($jsonData && isset($jsonData['details'])) {
			$details = $jsonData['details'];
			$title = trim($details['title'] ?? '');
			$rawText = trim($details['text'] ?? '');
			// Remove trailing 'telefon***' placeholder if present
			$description = preg_replace('/telefon\s*\*+$/ui', '', $rawText);
			$description = trim($description);
			
			$phone = !empty($details['phone']) ? trim($details['phone']) : null;
			$email = !empty($details['email']) ? trim($details['email']) : null;
			$city = !empty($details['city']) ? trim($details['city']) : (!empty($details['address']) ? trim($details['address']) : '');
			$stateName = !empty($details['state']) ? trim($details['state']) : '';
			if (isset($details['cost']) && is_numeric($details['cost']) && (float)$details['cost'] > 0) {
				$price = (float)$details['cost'];
			}
			if (!empty($jsonData['photos']) && is_array($jsonData['photos'])) {
				$photos = $jsonData['photos'];
			}
		}
	}

	// DOM Fallback if title or description missing
	if (empty($title) || empty($description)) {
		libxml_clear_errors();
		$itemDoc = new DOMDocument();
		@$itemDoc->loadHTML('<?xml encoding="UTF-8">' . $itemHtml);
		$itemXpath = new DOMXPath($itemDoc);

		if (empty($title)) {
			$h1Node = $itemXpath->query("//h1");
			if ($h1Node->length > 0) {
				$title = trim($h1Node->item(0)->textContent);
			}
		}
		if (empty($description)) {
			$descNode = $itemXpath->query("//div[contains(@class, 'announcement-desc')]");
			if ($descNode->length > 0) {
				$rawText = trim($descNode->item(0)->textContent);
				$description = trim(preg_replace('/telefon\s*\*+$/ui', '', $rawText));
			}
		}
	}

	if (empty($title)) {
		$title = 'Ogłoszenie z elbordo.pl';
	}
	if (empty($description)) {
		echo "- [SKIPPED] Missing description: $itemUrl\n";
		$skippedCount++;
		continue;
	}

	// Prepare location (city / address)
	$address = !empty($city) ? $city : 'Polska';

	// Format description as HTML
	$formattedDescription = '<p>' . nl2br(htmlspecialchars($description)) . '</p>';

	// Check if already imported by description or name
	$sthCheck = $db->prepare('SELECT id FROM '._DB_PREFIX_.'offer WHERE name = :name AND description = :description LIMIT 1');
	$sthCheck->bindValue(':name', $title, PDO::PARAM_STR);
	$sthCheck->bindValue(':description', $formattedDescription, PDO::PARAM_STR);
	$sthCheck->execute();
	if ($sthCheck->fetchColumn()) {
		echo "- [SKIPPED] Already exists: " . mb_substr($title, 0, 40) . "\n";
		$skippedCount++;
		continue;
	}

	// Determine state_id and state2_id (województwo / miasto) if possible
	$state_id = 0;
	$state2_id = 0;
	if (!empty($stateName)) {
		$sthSt = $db->prepare('SELECT id FROM '._DB_PREFIX_.'state WHERE name LIKE :st LIMIT 1');
		$sthSt->bindValue(':st', '%' . $stateName . '%', PDO::PARAM_STR);
		$sthSt->execute();
		$state_id = (int)($sthSt->fetchColumn() ?: 0);
	}
	if (!empty($city)) {
		$sthSt2 = $db->prepare('SELECT id FROM '._DB_PREFIX_.'state WHERE name LIKE :city LIMIT 1');
		$sthSt2->bindValue(':city', '%' . $city . '%', PDO::PARAM_STR);
		$sthSt2->execute();
		$state2_id = (int)($sthSt2->fetchColumn() ?: 0);
	}

	$category_id = resolveAntykiCategoryId($title, $description);

	$parsedDate = date('Y-m-d');
	$finishTimestamp = strtotime($parsedDate . " + $display_days days");
	$finishDate = date('Y-m-d H:i:s', $finishTimestamp);

	$code = 'imported_' . bin2hex(random_bytes(24));
	$date_start = $parsedDate . ' 00:00:00';
	$date_original = date('Y-m-d H:i:s');

	// Insert into offer table
	$sthInsert = $db->prepare('INSERT INTO `'._DB_PREFIX_.'offer` (
		`user_id`, `name`, `slug`, `price`, `price_negotiate`, `price_free`, 
		`address`, `phone`, `email`, `category_id`, `state_id`, `state2_id`, 
		`type_id`, `description`, `active`, `admin_confirmed`, `promoted`, 
		`code`, `ip`, `date_start`, `days`, `date_finish`, `date`
	) VALUES (
		0, :name, :slug, :price, 0, 0, 
		:address, :phone, :email, :category_id, :state_id, :state2_id, 
		0, :description, 1, 1, 0, 
		:code, "127.0.0.1", :date_start, :days, :date_finish, :date
	)');

	$sthInsert->bindValue(':name', $title, PDO::PARAM_STR);
	$sthInsert->bindValue(':slug', slug($title), PDO::PARAM_STR);
	$sthInsert->bindValue(':price', $price, $price !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
	$sthInsert->bindValue(':address', $address, PDO::PARAM_STR);
	$sthInsert->bindValue(':phone', $phone, PDO::PARAM_STR);
	$sthInsert->bindValue(':email', $email, PDO::PARAM_STR);
	$sthInsert->bindValue(':category_id', $category_id, PDO::PARAM_INT);
	$sthInsert->bindValue(':state_id', $state_id, PDO::PARAM_INT);
	$sthInsert->bindValue(':state2_id', $state2_id, PDO::PARAM_INT);
	$sthInsert->bindValue(':description', $formattedDescription, PDO::PARAM_STR);
	$sthInsert->bindValue(':code', $code, PDO::PARAM_STR);
	$sthInsert->bindValue(':date_start', $date_start, PDO::PARAM_STR);
	$sthInsert->bindValue(':days', $display_days, PDO::PARAM_INT);
	$sthInsert->bindValue(':date_finish', $finishDate, PDO::PARAM_STR);
	$sthInsert->bindValue(':date', $date_original, PDO::PARAM_STR);
	$sthInsert->execute();

	$offer_id = (int)$db->lastInsertId();

	// Process photos if present
	if (!empty($photos) && $offer_id > 0) {
		processOfferPhotos($photos, $offer_id);
	}

	echo "- [IMPORTED] " . $title . " (ID: $offer_id, phone: " . ($phone ?? 'N/A') . ", city: " . ($city ?: 'N/A') . ", photos: " . count($photos) . ")\n";
	$importedCount++;
}

// Refresh category offer counts if category exists
$sthCats = $db->query('SELECT DISTINCT category_id FROM `'._DB_PREFIX_.'offer`');
if ($sthCats) {
	while ($catId = $sthCats->fetchColumn()) {
		\App\Category::refreshCount((int)$catId);
	}
}

echo "Scraper execution finished. Imported: $importedCount, Skipped: $skippedCount.\n";
