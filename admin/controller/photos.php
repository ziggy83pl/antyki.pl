<?php

if (!isset(\App\Core\App::settings()['base_url'])) {
	die('Access denied!');
}

if ($admin->is_logged()) {
	
	$limit = 30;

	// Handle deleting photo
	if (!_ADMIN_TEST_MODE_ && isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
		if (isset($_POST['id']) && $_POST['id'] > 0 && checkToken('admin_delete_photo')) {
			$photoId = (int)$_POST['id'];
			
			// a) Fetch photo path
			$sth = $db->prepare('SELECT folder, thumb, url FROM `'._DB_PREFIX_.'photo` WHERE id = :id LIMIT 1');
			$sth->bindValue(':id', $photoId, PDO::PARAM_INT);
			$sth->execute();
			$photo = $sth->fetch(PDO::FETCH_ASSOC);
			
			if ($photo) {
				// b) Delete physical file and thumbnail
				$baseDir = __DIR__ . '/../../upload/photos/';
				$photoPath = $baseDir . $photo['folder'] . $photo['url'];
				$thumbPath = $baseDir . $photo['folder'] . $photo['thumb'];
				
				if (file_exists($photoPath) && is_file($photoPath)) {
					@unlink($photoPath);
				}
				if (file_exists($thumbPath) && is_file($thumbPath)) {
					@unlink($thumbPath);
				}
				
				// c) Delete database entry
				$sthDel = $db->prepare('DELETE FROM `'._DB_PREFIX_.'photo` WHERE id = :id');
				$sthDel->bindValue(':id', $photoId, PDO::PARAM_INT);
				$sthDel->execute();
				
				$render_variables['alert_danger'][] = 'Zdjęcie zostało pomyślnie usunięte z bazy i dysku.';
			}
		}
	}

	// Fetch photos with pagination
	$photos = [];
	$sth = $db->prepare('
		SELECT SQL_CALC_FOUND_ROWS p.*, o.name as offer_name, o.slug as offer_slug 
		FROM `'._DB_PREFIX_.'photo` p 
		LEFT JOIN `'._DB_PREFIX_.'offer` o ON p.offer_id = o.id 
		ORDER BY p.id DESC 
		LIMIT :limit_from, :limit_to
	');
	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
	$sth->execute();
	
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		$photos[] = $row;
	}

	$render_variables['photos'] = $photos;
	generatePagination($limit);

	$title = 'Moderacja zdjęć - ' . $title_default;
}
