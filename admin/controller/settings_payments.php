<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action']=='save_settings_payments' and checkToken('admin_save_settings_payments')){

		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name=:name limit 1');

		$sth->bindValue(':value', $_POST['currency'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'currency', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['add_cost'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'add_cost', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['promote_days'], PDO::PARAM_INT);
		$sth->bindValue(':name', 'promote_days', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['promote_cost'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'promote_cost', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['promote_only_by_author']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'promote_only_by_author', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['pay_by_dotpay']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'pay_by_dotpay', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['dotpay_id'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'dotpay_id', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['dotpay_pin'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'dotpay_pin', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['dotpay_currency'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'dotpay_currency', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['dotpay_test_mode']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'dotpay_test_mode', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['pay_by_paypal']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'pay_by_paypal', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['paypal_email'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'paypal_email', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['paypal_lc'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'paypal_lc', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['paypal_currency'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'paypal_currency', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['paypal_test_mode']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'paypal_test_mode', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['pay_by_p24']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'pay_by_p24', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['p24_merchant_id'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'p24_merchant_id', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['p24_pos_id'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'p24_pos_id', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['p24_crc'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'p24_crc', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', $_POST['p24_api_key'], PDO::PARAM_STR);
		$sth->bindValue(':name', 'p24_api_key', PDO::PARAM_STR);
		$sth->execute();
		$sth->bindValue(':value', isset($_POST['p24_sandbox']), PDO::PARAM_INT);
		$sth->bindValue(':name', 'p24_sandbox', PDO::PARAM_STR);
		$sth->execute();

		getSettings();
		$render_variables['alert_success'][] = lang('Changes have been saved');
	}

	$title = lang('Payment settings').' - '.$title_default;
}
