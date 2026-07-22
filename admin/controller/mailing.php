<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='send_mailing' and !empty($_POST['subject']) and isset($_POST['message']) and checkToken('admin_send_mailing')){

			$sth = $db->query('SELECT email FROM '._DB_PREFIX_.'user WHERE active=1');
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
				mailsQueueAdd('mailing',$row['email'],['subject'=>$_POST['subject'], 'message'=>$_POST['message']]);
			}
			header('Location: '.\App\Core\App::settings()['base_url'].'/'.basename(dirname($_SERVER['REQUEST_URI'])).'/?controller=mailing');
			die('redirect');
		}elseif($_POST['action']=='cancel_mailing' and checkToken('admin_cancel_mailing')){
			$db->query('TRUNCATE '._DB_PREFIX_.'mails_queue');
		}
	}

	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'mails_queue');
	$mails_queue = $sth->fetchColumn();
	if($mails_queue){
		$render_variables['alert_danger'][] = lang('Warning! Mailing is in progress').'. '.lang('Mails in queue').': '.$mails_queue;
		$render_variables['mails_queue'] = $mails_queue;
		
		$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'mails_queue ORDER BY priority DESC, id ASC LIMIT 50');
		$queued_emails = $sth->fetchAll(PDO::FETCH_ASSOC);
		foreach($queued_emails as &$q_email){
			$data = unserialize($q_email['data']);
			$q_email['data'] = $data;
			$q_email['sender'] = \App\Core\App::settings()['email'];

			if($q_email['action'] == 'mailing' || $q_email['action'] == 'test'){
				$q_email['subject'] = $data['subject'] ?? '';
				$q_email['message'] = $data['message'] ?? '';
			} elseif($q_email['action'] == 'magic_link') {
				$q_email['subject'] = lang('Magic login link');
				$q_email['message'] = 'System magic link message';
			} elseif($q_email['action'] == 'new_device_login') {
				$q_email['subject'] = lang('New login notification');
				$q_email['message'] = 'System login notification';
			} elseif($q_email['action'] == 'new_offer_alert') {
				$q_email['subject'] = lang('New offers in subscribed category');
				$q_email['message'] = 'System new offer alert';
			} elseif($q_email['action'] == 'report_offer') {
				$q_email['subject'] = lang('New abuse report');
				$q_email['message'] = 'System abuse report message';
			} else {
				$sth2 = $db->prepare('SELECT * FROM '._DB_PREFIX_.'mails WHERE name=:name limit 1');
				$sth2->bindParam(':name', $q_email['action'], PDO::PARAM_STR);
				$sth2->execute();
				$mail_content = $sth2->fetch(PDO::FETCH_ASSOC);
				$q_email['subject'] = $mail_content ? $mail_content['subject'] : $q_email['action'];
				$q_email['message'] = $mail_content ? $mail_content['message'] : 'System email template: '.$q_email['action'];
			}
		}
		$render_variables['queued_emails'] = $queued_emails;
	}
}
