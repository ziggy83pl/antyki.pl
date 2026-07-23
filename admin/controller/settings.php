<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if(!isset($admin)){
	die('Błąd: Plik panelu administratora (admin/controller/settings.php) został nieprawidłowo wgrany do głównego katalogu kontrolerów (controller/settings.php). Zastąp go właściwym plikiem z repozytorium.');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='save_settings'){
		if (!checkToken('admin_save_settings', '', true)) {
			$render_variables['alert_danger'][] = lang('Nieprawidłowy token formularza (CSRF). Odśwież stronę i spróbuj ponownie.');
		} elseif (empty($_POST['base_url']) || empty($_POST['email']) || empty($_POST['title'])) {
			$render_variables['alert_danger'][] = lang('Wypełnij wymagane pola: Adres URL, E-mail i Tytuł strony.');
		} else {

			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'settings` (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value=:value');

			$sth->bindValue(':value', webAddress($_POST['base_url']), PDO::PARAM_STR);
			$sth->bindValue(':name', 'base_url', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['email'], PDO::PARAM_STR);
			$sth->bindValue(':name', 'email', PDO::PARAM_STR);
			$sth->execute();
			if(\App\Core\App::settings()['lang']!=$_POST['lang']){
				unset($translate);
				$sth->bindValue(':value', langLoad($_POST['lang']), PDO::PARAM_STR);
				$sth->bindValue(':name', 'lang', PDO::PARAM_STR);
				$sth->execute();
			}
			$sth->bindValue(':value', $_POST['title'], PDO::PARAM_STR);
			$sth->bindValue(':name', 'title', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['keywords'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'keywords', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['description'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'description', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['analytics'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'analytics', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['number_char_title'] ?? '128'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'number_char_title', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['add_offers_not_logged']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'add_offers_not_logged', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['automatically_activate_offers']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'automatically_activate_offers', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['enable_articles']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'enable_articles', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['rss']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'rss', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['generate_sitemap']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'generate_sitemap', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['check_ip_user']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'check_ip_user', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['required_type']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'required_type', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['required_category']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'required_category', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['required_subcategory']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'required_subcategory', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['required_phone']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'required_phone', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['required_address']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'required_address', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['required_state']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'required_state', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['google_maps']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'google_maps', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['google_maps_api'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'google_maps_api', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['google_maps_lat'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'google_maps_lat', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['google_maps_zoom_add'] ?? '5'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'google_maps_zoom_add', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['google_maps_zoom_offer'] ?? '10'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'google_maps_zoom_offer', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['google_maps_long'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'google_maps_long', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['limit_page'] ?? '30'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'limit_page', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['limit_page_index'] ?? '12'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'limit_page_index', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['show_similar_offer']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'show_similar_offer', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['limit_similar_offer'] ?? '6'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'limit_similar_offer', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['photo_add']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'photo_add', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['photo_max'] ?? '10'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'photo_max', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['photo_max_size'] ?? '5'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'photo_max_size', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['photo_max_height'] ?? '1080'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'photo_max_height', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['photo_max_width'] ?? '1920'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'photo_max_width', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['photo_quality'] ?? '85'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'photo_quality', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['watermark_add']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'watermark_add', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['hide_data_not_logged']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'hide_data_not_logged', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['hide_phone']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'hide_phone', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['hide_email']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'hide_email', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['hide_views']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'hide_views', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['mail_attachment']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'mail_attachment', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', isset($_POST['smtp']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['smtp_host'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp_host', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['smtp_mail'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp_mail', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['smtp_user'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp_user', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['smtp_password'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp_password', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', (string)($_POST['smtp_port'] ?? '587'), PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp_port', PDO::PARAM_STR);
			$sth->execute();
			$sth->bindValue(':value', $_POST['smtp_secure'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'smtp_secure', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', isset($_POST['enable_verification_badges']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'enable_verification_badges', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', $_POST['admin_phone'] ?? '', PDO::PARAM_STR);
			$sth->bindValue(':name', 'admin_phone', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', isset($_POST['scraper_enabled']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_enabled', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_display_days'] ?? 7)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_display_days', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_max_imports'] ?? 30)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_max_imports', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', isset($_POST['scraper_mylomza_enabled']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_mylomza_enabled', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_mylomza_display_days'] ?? 7)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_mylomza_display_days', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_mylomza_max_imports'] ?? 10)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_mylomza_max_imports', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', isset($_POST['scraper_eostroleka_enabled']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_eostroleka_enabled', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_eostroleka_display_days'] ?? 7)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_eostroleka_display_days', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_eostroleka_max_imports'] ?? 10)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_eostroleka_max_imports', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', isset($_POST['scraper_mojaostroleka_enabled']) ? '1' : '0', PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_mojaostroleka_enabled', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_mojaostroleka_display_days'] ?? 7)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_mojaostroleka_display_days', PDO::PARAM_STR);
			$sth->execute();

			$sth->bindValue(':value', (string)((int)($_POST['scraper_mojaostroleka_max_imports'] ?? 10)), PDO::PARAM_STR);
			$sth->bindValue(':name', 'scraper_mojaostroleka_max_imports', PDO::PARAM_STR);
			$sth->execute();

			getSettings();
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}
	}elseif($_POST['action']=='send_test_message' and !empty($_POST['email']) and !empty($_POST['subject']) and !empty($_POST['message']) and checkToken('admin_send_test_message')){
			if(sendMail('test',$_POST['email'],['subject'=>$_POST['subject'], 'message'=>$_POST['message']])){
				$render_variables['alert_success'][] = lang('The message was correctly sent');
			}else{
				$render_variables['alert_danger'][] = lang('The message was not sent');
			}
		}
	}

	$render_variables['lang_list'] = langList();

	$title = lang('Settings').' - '.$title_default;

}
