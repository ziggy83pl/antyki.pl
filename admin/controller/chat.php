<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		// Delete entire chat room (CASCADE removes messages)
		if($_POST['action']=='delete_room' and !empty($_POST['id']) and checkToken('admin_delete_chat_room')){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'chat_room WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->execute();
			$render_variables['alert_success'][] = lang('Chat room has been deleted');
		}

		// Delete a single message
		if($_POST['action']=='delete_message' and !empty($_POST['id']) and checkToken('admin_delete_chat_message')){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'chat_message WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->execute();
			$render_variables['alert_success'][] = lang('Message has been deleted');
		}
	}

	$limit = 50;

	// Get total count of chat rooms
	$total_rooms = (int)$db->query('SELECT COUNT(*) FROM '._DB_PREFIX_.'chat_room')->fetchColumn();

	// Get all chat rooms with offer, buyer, seller info and stats
	$sth = $db->prepare('
		SELECT
			r.id,
			r.offer_id,
			r.buyer_id,
			r.seller_id,
			r.created_at,
			o.name  AS offer_name,
			o.slug  AS offer_slug,
			ub.username AS buyer_username,
			us.username AS seller_username,
			(SELECT COUNT(*) FROM '._DB_PREFIX_.'chat_message WHERE room_id = r.id) AS message_count,
			m.message AS last_message,
			m.created_at AS last_message_at
		FROM '._DB_PREFIX_.'chat_room r
		JOIN '._DB_PREFIX_.'offer o  ON r.offer_id  = o.id
		JOIN '._DB_PREFIX_.'user  ub ON r.buyer_id  = ub.id
		JOIN '._DB_PREFIX_.'user  us ON r.seller_id = us.id
		LEFT JOIN '._DB_PREFIX_.'chat_message m ON m.id = (
			SELECT id FROM '._DB_PREFIX_.'chat_message
			WHERE room_id = r.id
			ORDER BY id DESC LIMIT 1
		)
		ORDER BY IFNULL(m.created_at, r.created_at) DESC
		LIMIT :limit_from, :limit_to
	');
	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
	$sth->bindValue(':limit_to',   $limit,                     PDO::PARAM_INT);
	$sth->execute();

	$rooms = [];
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
		$rooms[] = $row;
	}
	if(!empty($rooms)){
		$render_variables['rooms'] = $rooms;
	}

	generatePagination($limit, $total_rooms);

	// If room_id is provided, load its messages and participants
	$view_room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
	if($view_room_id > 0){
		$sth = $db->prepare('
			SELECT
				r.*,
				o.name AS offer_name, o.slug AS offer_slug,
				ub.username AS buyer_username,
				us.username AS seller_username
			FROM '._DB_PREFIX_.'chat_room r
			JOIN '._DB_PREFIX_.'offer o  ON r.offer_id  = o.id
			JOIN '._DB_PREFIX_.'user  ub ON r.buyer_id  = ub.id
			JOIN '._DB_PREFIX_.'user  us ON r.seller_id = us.id
			WHERE r.id = :room_id LIMIT 1
		');
		$sth->bindValue(':room_id', $view_room_id, PDO::PARAM_INT);
		$sth->execute();
		$view_room = $sth->fetch(PDO::FETCH_ASSOC);

		if($view_room){
			$render_variables['view_room'] = $view_room;

			$sth = $db->prepare('
				SELECT m.*, u.username AS sender_username
				FROM '._DB_PREFIX_.'chat_message m
				JOIN '._DB_PREFIX_.'user u ON m.sender_id = u.id
				WHERE m.room_id = :room_id
				ORDER BY m.id ASC
			');
			$sth->bindValue(':room_id', $view_room_id, PDO::PARAM_INT);
			$sth->execute();
			$view_messages = $sth->fetchAll(PDO::FETCH_ASSOC);
			if(!empty($view_messages)){
				$render_variables['view_messages'] = $view_messages;
			}
		}
	}

	$render_variables['view_room_id'] = $view_room_id;
	$title = lang('Chat moderation').' - '.$title_default;
}
