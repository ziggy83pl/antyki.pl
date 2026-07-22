<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(!isset($user)){
	die('Błąd: Plik ustawień użytkownika (controller/settings.php) został nieprawidłowo wgrany do katalogu panelu administratora (admin/controller/settings.php). Zastąp go właściwym plikiem z repozytorium.');
}

if(!empty($path_parts[1])){
	throw new noFoundException();
}

if($user->logged_in){
	
	if(isset($_POST['action'])){
		if($_POST['action']=='save_avatar' and isset($_FILES['avatar']) and $_FILES['avatar']["type"] and checkToken('save_avatar')){
			
			$user->saveAvatar();
			$render_variables['alert_success'][] = lang('Avatar has been saved');

		}elseif($_POST['action']=='remove_avatar' and checkToken('remove_avatar')){

			\App\User::removeAvatar($user->getId());
			$user->user_data['avatar'] = '';
			$render_variables['alert_success'][] = lang('Avatar has been removed');
			
		}elseif($_POST['action']=='save_description' and isset($_POST['description']) and checkToken('save_description')){
			
			$user->saveDescription($_POST['description']);
			$render_variables['alert_success'][] = lang('Description has been saved');
			
		}elseif($_POST['action']=='save_user_data' and isset($_POST['address']) and isset($_POST['phone']) and checkToken('save_user_data')){
			
			$changes = $user->saveUserData($_POST);
			if (!empty($changes)) {
				$render_variables['alert_success'][] = lang('Changes have been saved') . ' (' . lang('updated') . ': ' . implode(', ', $changes) . ')';
			} else {
				$render_variables['alert_success'][] = lang('Changes have been saved');
			}
			
		}elseif($_POST['action']=='change_password' and !empty($_POST['old_password']) and !empty($_POST['new_password']) and !empty($_POST['repeat_new_password']) and checkToken('change_password')){
			
			try{
				$user->changePassword($_POST);
				$render_variables['alert_success'][] = lang('The password has been correctly updated');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
				$render_variables['input'] = $_POST;
			}
		}elseif($_POST['action']=='change_username' and !empty($_POST['new_username']) and !empty($_POST['captcha']) and checkToken('change_username')){

			if($_POST['captcha'] != $_SESSION['captcha']){
				$render_variables['alert_danger'][] = lang('Invalid captcha code.');
			} else {
				$newUsername = trim($_POST['new_username']);
				if(strlen($newUsername) < 3 || strlen($newUsername) > 30 || !preg_match('/^[A-Za-z0-9_]+$/', $newUsername)){
					$render_variables['alert_danger'][] = lang('Invalid username. Use 3-30 alphanumeric characters or underscores.');
				} elseif(strtolower($newUsername) === strtolower($user->user_data['username'])){
					$render_variables['alert_danger'][] = lang('The new username is the same as the current one.');
				} else {
					$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'user WHERE username = ? AND id != ?');
					$sth->execute([$newUsername, $user->getId()]);
					if($sth->fetchColumn()){
						$render_variables['alert_danger'][] = lang('Username already taken.');
					} else {
						$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET username = ? WHERE id = ?');
						$sth->execute([$newUsername, $user->getId()]);
						$user->user_data['username'] = $newUsername;
						$render_variables['alert_success'][] = lang('Username updated successfully.') . ' ' . lang('From now on, you can log in using the new username:') . ' <strong>' . htmlspecialchars($newUsername) . '</strong>';
					}
				}
			}
		}elseif($_POST['action']=='order_ad' and !empty($_POST['slot']) and !empty($_POST['ad_url']) and !empty($_POST['ad_start']) and !empty($_POST['ad_end']) and isset($_FILES['attachment']) and checkToken('order_ad')){
			
			try {
				if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
					throw new Exception(lang('Error uploading file'));
				}
				if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
					throw new Exception(lang('File size is too large (max 5MB)'));
				}
				$ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
				if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
					throw new Exception(lang('Invalid file extension (allowed: JPG, PNG, WEBP)'));
				}
				
				$startDate = $_POST['ad_start'];
				$endDate = $_POST['ad_end'];
				if ($startDate > $endDate) {
					throw new Exception(lang('The start date cannot be later than the end date'));
				}
				
				if (!filter_var($_POST['ad_url'], FILTER_VALIDATE_URL)) {
					throw new Exception(lang('Incorrect URL address'));
				}

				$slotLabels = [
					'ads_1' => lang('Top Banner') . ' (ads_1)',
					'ads_2' => lang('Index Middle Banner') . ' (ads_2)',
					'ads_3' => lang('Index Bottom Banner') . ' (ads_3)',
					'ads_4' => lang('Footer Banner') . ' (ads_4)',
					'ads_side_1' => lang('Sidebar Top Banner') . ' (ads_side_1)',
					'ads_side_2' => lang('Sidebar Bottom Banner') . ' (ads_side_2)'
				];
				$slotLabel = $slotLabels[$_POST['slot']] ?? $_POST['slot'];
				
				$locationTargetingText = 'Cała Polska';
				if (isset($_POST['ad_location_type'])) {
					if ($_POST['ad_location_type'] === 'state' && !empty($_POST['ad_state_id'])) {
						$stateData = getStateById((int)$_POST['ad_state_id']);
						$locationTargetingText = 'Województwo: ' . ($stateData['name'] ?? $_POST['ad_state_id']);
					} elseif ($_POST['ad_location_type'] === 'city' && !empty($_POST['ad_state_id'])) {
						$stateData = getStateById((int)$_POST['ad_state_id']);
						$cityId = !empty($_POST['ad_state2_id']) ? (int)$_POST['ad_state2_id'] : 0;
						$cityName = '';
						if ($cityId) {
							$sthCity = $db->prepare('SELECT name FROM '._DB_PREFIX_.'state WHERE id = ? LIMIT 1');
							$sthCity->execute([$cityId]);
							$cityName = $sthCity->fetchColumn();
						}
						$locationTargetingText = 'Miasto: ' . ($cityName ?: $cityId) . ' (Województwo: ' . ($stateData['name'] ?? $_POST['ad_state_id']) . ')';
					}
				}

				$oldMailAttachmentSetting = $settings['mail_attachment'] ?? false;
				$settings['mail_attachment'] = true;
				
				$subject = 'Nowe zamowienie reklamy - ' . $user->user_data['username'];
				$message = '
				<h3>Nowe zamówienie reklamy w serwisie ' . htmlspecialchars($settings['title']) . '</h3>
				<p><strong>Użytkownik:</strong> ' . htmlspecialchars($user->user_data['username']) . ' (' . htmlspecialchars($user->user_data['email']) . ')</p>
				<p><strong>Wybrana pozycja:</strong> ' . htmlspecialchars($slotLabel) . '</p>
				<p><strong>Obszar wyświetlania reklamy:</strong> ' . htmlspecialchars($locationTargetingText) . '</p>
				<p><strong>Adres URL docelowy:</strong> <a href="' . htmlspecialchars($_POST['ad_url']) . '" target="_blank">' . htmlspecialchars($_POST['ad_url']) . '</a></p>
				<p><strong>Okres emisji:</strong> od ' . htmlspecialchars($startDate) . ' do ' . htmlspecialchars($endDate) . '</p>
				<p>Baner reklamowy został załączony do tej wiadomości.</p>
				';
				
				$mailData = [
					'subject' => $subject,
					'message' => $message,
					'email' => $user->user_data['email']
				];
				
				$mailSent = sendMail('mailing', $settings['email'], $mailData);
				
				$settings['mail_attachment'] = $oldMailAttachmentSetting;
				
				if ($mailSent) {
					$render_variables['alert_success'][] = lang('The ad order has been sent successfully');
				} else {
					throw new Exception(lang('Error sending email'));
				}
				
			} catch (Exception $e) {
				$render_variables['alert_danger'][] = getSafeExceptionMessage($e);
				$render_variables['input'] = $_POST;
			}
		}
	}

	$user->getAllData();

	$render_variables['states'] = getAllStates();

	$settings['seo_title'] = lang('Settings').' - '.$settings['title'];
	$settings['seo_description'] = lang('Settings').' - '.$settings['description'];
}else{
	header("Location: ".path('login')."?redirect=".path('settings'));
	die('redirect');
}
