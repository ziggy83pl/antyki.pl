<?php
/************************************************************************

 *
 * *********************************************************************
 * THIS SOFTWARE IS LICENSED - YOU CAN MODIFY THESE FILES
 * BUT YOU CAN NOT REMOVE OF ORIGINAL COMMENTS!
 * ACCORDING TO THE LICENSE YOU CAN USE THE SCRIPT ON ONE DOMAIN. DETECTION
 * COPY SCRIPT WILL RESULT IN A HIGH FINANSIAL PENALTY AND WITHDRAWAL
 * LICENSE THE SCRIPT
 * *********************************************************************/

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(empty($_GET['slug']) && $user->logged_in && !empty($user->username)){
	header('Location: '.path('profile', 0, $user->username));
	exit;
}

if(!empty($_GET['slug'])){
	$profile = \App\User::getProfile($_GET['slug']);

	if($profile){
		$settings['seo_title'] = lang('Profile of').': '.$profile['username'].' - '.$settings['title'];
		$settings['seo_description'] = lang('Profile of').': '.$profile['username'].' - '.$settings['description'];
		$render_variables['profile'] = $profile;
		// Handle username edit
		if (isset($_POST['action']) && $_POST['action'] == 'edit_username' && $user->getId() && $user->getId() == $profile['id']) {
			if (!checkToken('edit_username')) {
				$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
			} else {
                // Validate captcha
                if (!isset($_POST['captcha']) || $_POST['captcha'] !== $_SESSION['captcha']) {
                    $render_variables['alert_danger'][] = lang('Invalid captcha code.');
                } else {
				$newUsername = trim($_POST['new_username'] ?? '');
				if (strlen($newUsername) < 3 || strlen($newUsername) > 30 || !preg_match('/^[A-Za-z0-9_]+$/', $newUsername)) {
					$render_variables['alert_danger'][] = lang('Invalid username. Use 3‑30 alphanumeric characters or underscores.');
				} else {
					// Optional uniqueness check
					$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'user WHERE username = ?');
					$sth->execute([$newUsername]);
					$existing = $sth->fetchColumn();
					if ($existing && $existing != $profile['id']) {
						$render_variables['alert_danger'][] = lang('Username already taken.');
					} else {
						$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET username = ? WHERE id = ?');
						$sth->execute([$newUsername, $profile['id']]);
						$render_variables['alert_success'][] = lang('Username updated successfully.');
						// Refresh profile data
						$profile = \App\User::getProfile($newUsername);
						$render_variables['profile'] = $profile;
						if (!headers_sent()) {
							header('Location: '.$settings['base_url'].'/profile?slug='.$newUsername);
							exit;
						}
					}
				}
			}
		}
		}



		// Handle adding/updating reviews
		if(isset($_POST['action']) and $_POST['action']=='add_opinion' and $user->getId() and $user->getId() != $profile['id']){
			if(!checkToken('add_opinion')){
				$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
			} else {
				$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
				$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
				
				if($rating < 1 or $rating > 5){
					$render_variables['alert_danger'][] = lang('Please select a rating (1-5 stars)');
				} elseif(empty($comment)){
					$render_variables['alert_danger'][] = lang('Please write a comment');
				} else {
					// Check if already reviewed
					$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'opinion WHERE author_id=:author_id AND user_id=:user_id LIMIT 1');
					$sth->bindValue(':author_id', $user->getId(), PDO::PARAM_INT);
					$sth->bindValue(':user_id', $profile['id'], PDO::PARAM_INT);
					$sth->execute();
					$existing_id = $sth->fetchColumn();
					
					if($existing_id){
						$sth = $db->prepare('UPDATE '._DB_PREFIX_.'opinion SET rating=:rating, comment=:comment, date=NOW() WHERE id=:id');
						$sth->bindValue(':rating', $rating, PDO::PARAM_INT);
						$sth->bindValue(':comment', $comment, PDO::PARAM_STR);
						$sth->bindValue(':id', $existing_id, PDO::PARAM_INT);
						$sth->execute();
						$render_variables['alert_success'][] = lang('Review updated successfully');
					} else {
						$sth = $db->prepare('INSERT INTO '._DB_PREFIX_.'opinion (user_id, author_id, rating, comment, date, active) VALUES (:user_id, :author_id, :rating, :comment, NOW(), 1)');
						$sth->bindValue(':user_id', $profile['id'], PDO::PARAM_INT);
						$sth->bindValue(':author_id', $user->getId(), PDO::PARAM_INT);
						$sth->bindValue(':rating', $rating, PDO::PARAM_INT);
						$sth->bindValue(':comment', $comment, PDO::PARAM_STR);
						$sth->execute();
						$render_variables['alert_success'][] = lang('Review added successfully');
					}
					
					// Reload profile averages
					$profile = \App\User::getProfile($profile['username']);
					$render_variables['profile'] = $profile;
				}
			}
		}

		// Load opinions
		$sth = $db->prepare('SELECT o.*, u.username as author_username, u.avatar as author_avatar FROM '._DB_PREFIX_.'opinion o LEFT JOIN '._DB_PREFIX_.'user u ON o.author_id = u.id WHERE o.user_id=:user_id AND o.active=1 ORDER BY o.date DESC');
		$sth->bindValue(':user_id', $profile['id'], PDO::PARAM_INT);
		$sth->execute();
		$opinions = $sth->fetchAll(PDO::FETCH_ASSOC);
		$render_variables['opinions'] = $opinions;

		// Calculate reputation stats
		$histogram = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
		$rating_sum = 0;
		$rating_count = count($opinions);
		$positive_count = 0;
		foreach ($opinions as $op) {
			$r = (int)$op['rating'];
			if (isset($histogram[$r])) {
				$histogram[$r]++;
			}
			$rating_sum += $r;
			if ($r >= 4) {
				$positive_count++;
			}
		}
		
		$histogram_percentages = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
		if ($rating_count > 0) {
			foreach ($histogram as $stars => $count) {
				$histogram_percentages[$stars] = round(($count / $rating_count) * 100);
			}
		}
		
		$recommendation_percent = 0;
		if ($rating_count > 0) {
			$recommendation_percent = round(($positive_count / $rating_count) * 100);
		}
		
		$is_super_wykonawca = false;
		$avg_rating = $rating_count > 0 ? ($rating_sum / $rating_count) : 0;
		if ($avg_rating >= 4.7 && $rating_count >= 3) {
			$is_super_wykonawca = true;
		}

		$render_variables['reputation'] = [
			'histogram' => $histogram,
			'histogram_percentages' => $histogram_percentages,
			'recommendation_percent' => $recommendation_percent,
			'is_super_wykonawca' => $is_super_wykonawca,
			'avg_rating' => round($avg_rating, 2)
		];

		if($settings['show_contact_form_profile'] and isset($_POST['action']) and $_POST['action']=='send_message' and !empty($_POST['name']) and (!empty($_POST['email']) or $user->getId()) and !empty($_POST['message']) and !empty($_POST['captcha']) and (isset($_POST['rules']) or $user->getId())){

		if(!checkToken('send_message')){
			$render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
		}elseif($_POST['captcha']!=$_SESSION['captcha']){
			$error['captcha'] = lang('Invalid captcha code.');
		}elseif(!$user->getId() and !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$error['email'] = lang('Incorrect e-mail address');
		}

		if(isset($error)){
			$render_variables['error'] = $error;
			$render_variables['alert_danger'][] = lang('The message was not sent');
			$render_variables['input'] = ['name'=>$_POST['name'], 'email'=>$_POST['email'], 'message'=>$_POST['message']];
		}else{
			if($user->getId()){
				$email = $user->email;
			}else{
				$email = $_POST['email'];
			}
			if(sendMail('profile',$profile['email'],['name'=>$_POST['name'], 'email'=>$email, 'message'=>$_POST['message'], 'username'=>$profile['username']])){
				$render_variables['alert_success'][] = lang('The message was correctly sent');
			}else{
				$render_variables['alert_danger'][] = lang('The message was not sent');
			}
		}
	}
	}else{
		throw new noFoundException();
	}
}else{
	throw new noFoundException();
}
