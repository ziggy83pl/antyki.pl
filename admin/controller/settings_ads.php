<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action']=='save_settings_ads' and checkToken('admin_save_settings_ads')){
		
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'settings` (`name`, `value`) VALUES (:name, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');

		function saveAdSettings($prefix, $sth) {
			// Zapisz aktywność
			$sth->bindValue(':name', $prefix.'_active', PDO::PARAM_STR);
			$sth->bindValue(':value', !empty($_POST[$prefix.'_active']) ? 1 : 0, PDO::PARAM_STR);
			$sth->execute();

			// Zapisz daty i URL oraz targetowanie geograficzne
			$params = ['url', 'start', 'end', 'location_type', 'state_id', 'state2_id'];
			foreach($params as $param) {
				$sth->bindValue(':name', $prefix.'_'.$param, PDO::PARAM_STR);
				$sth->bindValue(':value', isset($_POST[$prefix.'_'.$param]) ? $_POST[$prefix.'_'.$param] : '', PDO::PARAM_STR);
				$sth->execute();
			}

			// Usuwanie obrazka
			if(!empty($_POST[$prefix.'_delete_image'])) {
				$old_img = \App\Core\App::settings()[$prefix.'_image'] ?? '';
				if($old_img && file_exists(_FOLDER_ADS_ . $old_img)) {
					unlink(_FOLDER_ADS_ . $old_img);
				}
				$sth->bindValue(':name', $prefix.'_image', PDO::PARAM_STR);
				$sth->bindValue(':value', '', PDO::PARAM_STR);
				$sth->execute();
			}

			// Upload nowego obrazka
			if(isset($_FILES[$prefix.'_file']) && $_FILES[$prefix.'_file']['error'] == 0) {
				$ext = strtolower(pathinfo($_FILES[$prefix.'_file']['name'], PATHINFO_EXTENSION));
				if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
					$image_info = @getimagesize($_FILES[$prefix.'_file']['tmp_name']);
					if($image_info && isset($image_info['mime'])) {
						$allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/webp'];
						if(in_array(strtolower($image_info['mime']), $allowed_mimes)) {
							$filename = $prefix . '_' . time() . '.' . $ext;
							if(!is_dir(_FOLDER_ADS_)) {
								mkdir(_FOLDER_ADS_, 0777, true);
							}
							move_uploaded_file($_FILES[$prefix.'_file']['tmp_name'], _FOLDER_ADS_ . $filename);
							
							// Usuń stary jeśli istnieje
							$old_img = \App\Core\App::settings()[$prefix.'_image'] ?? '';
							if($old_img && file_exists(_FOLDER_ADS_ . $old_img)) {
								unlink(_FOLDER_ADS_ . $old_img);
							}

							$sth->bindValue(':name', $prefix.'_image', PDO::PARAM_STR);
							$sth->bindValue(':value', $filename, PDO::PARAM_STR);
							$sth->execute();
						}
					}
				}
			}
		}

		define('_FOLDER_ADS_', realpath(dirname(__FILE__)).'/../../upload/ads/');

		for ($i = 1; $i <= 4; $i++) {
			saveAdSettings('ads_'.$i, $sth);
		}

		for ($i = 1; $i <= 2; $i++) {
			saveAdSettings('ads_side_'.$i, $sth);
		}
		
		getSettings();
		$render_variables['alert_success'][] = lang('Changes have been saved');
	}
	
	$render_variables['states'] = getAllStates();
	$title = lang('Ads settings').' - '.$title_default;
}

