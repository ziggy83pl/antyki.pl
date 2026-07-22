<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if($user->logged_in){
	$user_id = (int)$user->id;

	if (isset($_GET['start_chat'])) {
		$offer_id = (int)$_GET['start_chat'];
		$room_id = \App\Chat::getOrCreateRoom($offer_id, $user_id);
		if ($room_id !== false) {
			header("Location: " . path('chat') . "?room_id=" . $room_id);
			die('redirect');
		} else {
			header("Location: " . $settings['base_url']);
			die('redirect');
		}
	}

	if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
		if (isset($_POST['room_id']) && isset($_POST['message']) && checkToken('send_message')) {
			$room_id = (int)$_POST['room_id'];
			\App\Chat::sendMessage($room_id, $user_id, $_POST['message']);
			header("Location: " . path('chat') . "?room_id=" . $room_id);
			die('redirect');
		}
	}

	$active_room = null;
	$messages = [];

	if (isset($_GET['room_id'])) {
		$room_id = (int)$_GET['room_id'];
		$messages = \App\Chat::getMessages($room_id, $user_id);
	}

	$rooms = \App\Chat::getRooms($user_id);

	if (isset($_GET['room_id'])) {
		foreach ($rooms as $room) {
			if ((int)$room['id'] === $room_id) {
				$active_room = $room;
				break;
			}
		}
	}

	$render_variables['rooms'] = $rooms;
	$render_variables['active_room'] = $active_room;
	$render_variables['messages'] = $messages;

	$settings['seo_title'] = lang('Inbox') . ' - ' . $settings['title'];
	$settings['seo_description'] = lang('Inbox') . ' - ' . $settings['description'];

} else {
	header("Location: " . path('login') . "?redirect=" . path('chat'));
	die('redirect');
}
