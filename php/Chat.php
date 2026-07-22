<?php


namespace App;

use PDO;
use Exception;
class Chat {

	public static function getOrCreateRoom($offer_id, $buyer_id): false|int {
		$db = \App\Core\App::db();
		
		$sth = $db->prepare('SELECT user_id FROM '._DB_PREFIX_.'offer WHERE id = :offer_id LIMIT 1');
		$sth->bindValue(':offer_id', $offer_id, PDO::PARAM_INT);
		$sth->execute();
		$offer = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$offer) {
			return false;
		}
		$seller_id = (int)$offer['user_id'];

		if ($buyer_id == $seller_id) {
			return false;
		}

		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'chat_room WHERE offer_id = :offer_id AND buyer_id = :buyer_id LIMIT 1');
		$sth->bindValue(':offer_id', $offer_id, PDO::PARAM_INT);
		$sth->bindValue(':buyer_id', $buyer_id, PDO::PARAM_INT);
		$sth->execute();
		$room = $sth->fetch(PDO::FETCH_ASSOC);

		if ($room) {
			return (int)$room['id'];
		}

		$sth = $db->prepare('INSERT INTO '._DB_PREFIX_.'chat_room (offer_id, buyer_id, seller_id, created_at) VALUES (:offer_id, :buyer_id, :seller_id, NOW())');
		$sth->bindValue(':offer_id', $offer_id, PDO::PARAM_INT);
		$sth->bindValue(':buyer_id', $buyer_id, PDO::PARAM_INT);
		$sth->bindValue(':seller_id', $seller_id, PDO::PARAM_INT);
		$sth->execute();

		return (int)$db->lastInsertId();
	}

	public static function sendMessage(string $room_id, $sender_id, $message): false|int {
		$db = \App\Core\App::db();
        $settings = \App\Core\App::settings();
		$message = trim((string) $message);
		if ($message === '') {
			return false;
		}
		
		$sth = $db->prepare('SELECT buyer_id, seller_id, last_notified_at FROM '._DB_PREFIX_.'chat_room WHERE id = :room_id LIMIT 1');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->execute();
		$room = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$room) {
			return false;
		}
		if ($sender_id != $room['buyer_id'] && $sender_id != $room['seller_id']) {
			return false;
		}

		$sth = $db->prepare('INSERT INTO '._DB_PREFIX_.'chat_message (room_id, sender_id, message, created_at, is_read) VALUES (:room_id, :sender_id, :message, NOW(), 0)');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->bindValue(':sender_id', $sender_id, PDO::PARAM_INT);
		$sth->bindValue(':message', $message, PDO::PARAM_STR);
		$sth->execute();

		$new_msg_id = (int)$db->lastInsertId();
		if (!$new_msg_id) {
			return false;
		}

		// Email notification logic - throttle to max 1 per 10 minutes per room
		$can_notify = true;
		if (!empty($room['last_notified_at'])) {
			$last_notified_ts = strtotime((string) $room['last_notified_at']);
			if ($last_notified_ts && (time() - $last_notified_ts) < 600) {
				$can_notify = false;
			}
		}

		if ($can_notify) {
			$email_data = self::getEmailDataForRoom($room_id, $sender_id, $room);
			if ($email_data && !empty($email_data['recipient_email']) && !empty($email_data['recipient_notify'])) {
				$mail_payload = [
					'username'           => $email_data['sender_username'],
					'recipient_username' => $email_data['recipient_username'],
					'message'            => mb_substr($message, 0, 150),
					'offer_name'         => $email_data['offer_name'],
					'offer_url'          => $settings['base_url'].'/'.$email_data['offer_id'].','.$email_data['offer_slug'],
					'chat_url'           => $settings['base_url'].'/czat?room_id='.$room_id,
				];
				mailsQueueAdd('chat_new_message', $email_data['recipient_email'], $mail_payload);

				// Update last_notified_at
				$sth2 = $db->prepare('UPDATE '._DB_PREFIX_.'chat_room SET last_notified_at = NOW() WHERE id = :room_id LIMIT 1');
				$sth2->bindValue(':room_id', $room_id, PDO::PARAM_INT);
				$sth2->execute();
			}
		}

		return $new_msg_id;
	}

	/**
	 * Fetch data needed to send a chat notification email.
	 * Returns array with sender/recipient info, offer info, or false on error.
	 */
	private static function getEmailDataForRoom(string $room_id, $sender_id, array $room): false|array {
		$db = \App\Core\App::db();

		// Determine recipient (the other participant)
		$recipient_id = ($sender_id == $room['buyer_id']) ? (int)$room['seller_id'] : (int)$room['buyer_id'];

		// Fetch sender username
		$sth = $db->prepare('SELECT username FROM '._DB_PREFIX_.'user WHERE id = :id LIMIT 1');
		$sth->bindValue(':id', $sender_id, PDO::PARAM_INT);
		$sth->execute();
		$sender = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$sender) {
			return false;
		}

		// Fetch recipient email and notification preference
		$sth = $db->prepare('SELECT username, email, notify_messages FROM '._DB_PREFIX_.'user WHERE id = :id LIMIT 1');
		$sth->bindValue(':id', $recipient_id, PDO::PARAM_INT);
		$sth->execute();
		$recipient = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$recipient) {
			return false;
		}

		// Fetch offer data
		$sth = $db->prepare('SELECT id, name, slug FROM '._DB_PREFIX_.'offer WHERE id = (SELECT offer_id FROM '._DB_PREFIX_.'chat_room WHERE id = :room_id LIMIT 1) LIMIT 1');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->execute();
		$offer = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$offer) {
			return false;
		}

		return [
			'sender_username'    => $sender['username'],
			'recipient_username' => $recipient['username'],
			'recipient_email'    => $recipient['email'],
			'recipient_notify'   => (int)$recipient['notify_messages'],
			'offer_id'           => $offer['id'],
			'offer_name'         => $offer['name'],
			'offer_slug'         => $offer['slug'],
		];
	}

	public static function getRooms($user_id): array {
		$db = \App\Core\App::db();
		$sth = $db->prepare('
			SELECT r.*, 
			       o.name AS offer_name, o.slug AS offer_slug, o.price AS offer_price,
			       u.username AS other_username, u.avatar AS other_avatar, u.id AS other_user_id,
			       m.message AS last_message, m.created_at AS last_message_date, m.sender_id AS last_message_sender_id,
			       (SELECT COUNT(*) FROM '._DB_PREFIX_.'chat_message WHERE room_id = r.id AND sender_id != :user_id AND is_read = 0) AS unread_count,
			       p.folder AS photo_folder, p.thumb AS photo_thumb
			FROM '._DB_PREFIX_.'chat_room r
			JOIN '._DB_PREFIX_.'offer o ON r.offer_id = o.id
			JOIN '._DB_PREFIX_.'user u ON (u.id = IF(r.buyer_id = :user_id, r.seller_id, r.buyer_id))
			LEFT JOIN '._DB_PREFIX_.'chat_message m ON m.id = (
				SELECT id FROM '._DB_PREFIX_.'chat_message 
				WHERE room_id = r.id 
				ORDER BY id DESC LIMIT 1
			)
			LEFT JOIN '._DB_PREFIX_.'photo p ON p.id = (
				SELECT id FROM '._DB_PREFIX_.'photo 
				WHERE offer_id = o.id 
				ORDER BY position ASC LIMIT 1
			)
			WHERE r.buyer_id = :user_id OR r.seller_id = :user_id
			ORDER BY IFNULL(m.created_at, r.created_at) DESC
		');
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function getMessages($room_id, $user_id): array {
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT buyer_id, seller_id FROM '._DB_PREFIX_.'chat_room WHERE id = :room_id LIMIT 1');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->execute();
		$room = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$room) {
			return [];
		}
		if ($user_id != $room['buyer_id'] && $user_id != $room['seller_id']) {
			return [];
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'chat_message SET is_read = 1 WHERE room_id = :room_id AND sender_id != :user_id AND is_read = 0');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->execute();

		$sth = $db->prepare('
			SELECT m.*, u.username AS sender_username, u.avatar AS sender_avatar
			FROM '._DB_PREFIX_.'chat_message m
			JOIN '._DB_PREFIX_.'user u ON m.sender_id = u.id
			WHERE m.room_id = :room_id
			ORDER BY m.id ASC
		');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function getUnreadCount($user_id): int {
		$db = \App\Core\App::db();
		if (!$user_id) {
			return 0;
		}
		$sth = $db->prepare('
			SELECT COUNT(*) 
			FROM '._DB_PREFIX_.'chat_message m
			JOIN '._DB_PREFIX_.'chat_room r ON m.room_id = r.id
			WHERE m.sender_id != :user_id 
			  AND m.is_read = 0 
			  AND (r.buyer_id = :user_id OR r.seller_id = :user_id)
		');
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->execute();
		return (int)$sth->fetchColumn();
	}

	public static function getNewMessages($room_id, $user_id, $last_id): array {
		$db = \App\Core\App::db();
		
		$sth = $db->prepare('SELECT buyer_id, seller_id FROM '._DB_PREFIX_.'chat_room WHERE id = :room_id LIMIT 1');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->execute();
		$room = $sth->fetch(PDO::FETCH_ASSOC);
		if (!$room) {
			return [];
		}
		if ($user_id != $room['buyer_id'] && $user_id != $room['seller_id']) {
			return [];
		}

		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'chat_message WHERE room_id = :room_id AND sender_id != :user_id AND is_read = 0 LIMIT 1');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->execute();
		if ($sth->fetch()) {
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'chat_message SET is_read = 1 WHERE room_id = :room_id AND sender_id != :user_id AND is_read = 0');
			$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
			$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$sth->execute();
		}

		$sth = $db->prepare('
			SELECT m.*, u.username AS sender_username, u.avatar AS sender_avatar
			FROM '._DB_PREFIX_.'chat_message m
			JOIN '._DB_PREFIX_.'user u ON m.sender_id = u.id
			WHERE m.room_id = :room_id AND m.id > :last_id
			ORDER BY m.id ASC
		');
		$sth->bindValue(':room_id', $room_id, PDO::PARAM_INT);
		$sth->bindValue(':last_id', $last_id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
}
