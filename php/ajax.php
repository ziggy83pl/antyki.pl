<?php

/* Modified: Secure session and cookie options */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', '1');
}

session_start();

/* Modified: Session timeout (30 minutes) */
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

require_once('../config/config.php');

$user = new \App\User();
header('Content-Type: application/json');

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'getCoordinates' && !empty($_POST['address'])) {
        echo json_encode(getCoordinates($_POST['address']));

    } elseif ($action === 'get_categories_and_options' && isset($_POST['category']) && (int)$_POST['category'] >= 0) {
        $categoryId = (int)$_POST['category'];
        if (!empty($_POST['load_options'])) {
            echo json_encode([
                'categories' => \App\Category::getCategories($categoryId),
                'options'    => \App\Option::getOptions($categoryId, 'add'),
                'price'      => \App\Option::getPrice($categoryId),
            ]);
        } else {
            echo json_encode(['categories' => \App\Category::getCategories($categoryId)]);
        }

    } elseif ($action === 'add_photo') {
        if (checkToken('add_offer', '', true) && ($settings['add_offers_not_logged'] || $user->logged_in) && $settings['photo_add'] && isset($_FILES['file'])) {
            echo json_encode(\App\Offer::addPhoto());
        } else {
            echo json_encode(['status' => false, 'info' => lang('Access denied or invalid session. Please try again.')]);
        }

    } elseif ($action === 'increment_click') {
        if (checkToken('click_statistics', '', true) && isset($_POST['offer_id']) && isset($_POST['click_type'])) {
            $offerId = (int)$_POST['offer_id'];
            $clickType = $_POST['click_type'];
            if ($offerId > 0 && in_array($clickType, ['phone', 'email', 'website'])) {
                $column = 'clicks_' . $clickType;
                $sth = $db->prepare('UPDATE '._DB_PREFIX_.'offer SET `'.$column.'` = `'.$column.'` + 1 WHERE id = :id');
                $sth->bindValue(':id', $offerId, PDO::PARAM_INT);
                $sth->execute();
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false, 'info' => 'Invalid parameters']);
            }
        } else {
            echo json_encode(['status' => false, 'info' => 'Access denied or invalid token']);
        }

    } elseif ($action === 'send_chat_message') {
        if ($user->logged_in && isset($_POST['room_id']) && isset($_POST['message']) && checkToken('send_message', '', true)) {
            $roomId = (int)$_POST['room_id'];
            $msgId = \App\Chat::sendMessage($roomId, (int)$user->id, $_POST['message']);
            if ($msgId) {
                echo json_encode(['status' => true, 'message_id' => $msgId]);
            } else {
                echo json_encode(['status' => false, 'info' => 'Failed to send message']);
            }
        } else {
            echo json_encode(['status' => false, 'info' => 'Access denied or invalid session']);
        }

    } elseif ($action === 'poll_chat_messages') {
        if ($user->logged_in && isset($_POST['room_id']) && isset($_POST['last_id'])) {
            $roomId = (int)$_POST['room_id'];
            $lastId = (int)$_POST['last_id'];
            $newMessages = \App\Chat::getNewMessages($roomId, (int)$user->id, $lastId);
            echo json_encode(['status' => true, 'messages' => $newMessages]);
        } else {
            echo json_encode(['status' => false, 'info' => 'Access denied']);
        }

    } elseif ($action === 'get_unread_chat_count') {
        if ($user->logged_in) {
            $count = \App\Chat::getUnreadCount((int)$user->id);
            echo json_encode(['status' => true, 'unread_count' => $count]);
        } else {
            echo json_encode(['status' => true, 'unread_count' => 0]);
        }

    } elseif ($action === 'sync_clipboard') {
        if ($user->logged_in && !empty($_POST['ids'])) {
            $raw_ids = explode(',', (string) $_POST['ids']);
            foreach ($raw_ids as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    \App\Offer::clipboardAdd($id);
                }
            }
            echo json_encode(['status' => true]);
        } else {
            echo json_encode(['status' => false, 'info' => 'Access denied or invalid parameters']);
        }

    } elseif ($action === 'clipboard_toggle') {
        if ($user->logged_in && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            if ($id > 0) {
                $sth = $db->prepare('SELECT 1 FROM `'._DB_PREFIX_.'clipboard` WHERE user_id=:user_id AND offer_id=:offer_id LIMIT 1');
                $sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
                $sth->bindValue(':offer_id', $id, PDO::PARAM_INT);
                $sth->execute();
                if ($sth->fetchColumn()) {
                    \App\Offer::clipboardRemove($id);
                    echo json_encode(['status' => true, 'added' => false]);
                } else {
                    \App\Offer::clipboardAdd($id);
                    echo json_encode(['status' => true, 'added' => true]);
                }
            } else {
                echo json_encode(['status' => false, 'info' => 'Invalid parameters']);
            }
        } else {
            echo json_encode(['status' => false, 'info' => 'Access denied or invalid session']);
        }

    } elseif ($action === 'subscribe_alert') {
        $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => false, 'info' => lang('Incorrect e-mail address')]);
        } elseif ($categoryId <= 0) {
            echo json_encode(['status' => false, 'info' => lang('Please select a category')]);
        } else {
            $sth = $db->prepare('SELECT id FROM `'._DB_PREFIX_.'alerts` WHERE email=:email AND category_id=:category_id LIMIT 1');
            $sth->bindValue(':email', $email, PDO::PARAM_STR);
            $sth->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $sth->execute();
            if ($sth->fetchColumn()) {
                echo json_encode(['status' => true, 'info' => lang('You are already subscribed to this category.')]);
            } else {
                $sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'alerts` (email, category_id, created_at) VALUES (:email, :category_id, NOW())');
                $sth->bindValue(':email', $email, PDO::PARAM_STR);
                $sth->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
                $sth->execute();
                echo json_encode(['status' => true, 'info' => lang('Successfully subscribed to notifications!')]);
            }
        }

    } elseif ($action === 'report_offer') {
        $offerId = isset($_POST['offer_id']) ? (int)$_POST['offer_id'] : 0;
        $reason = isset($_POST['reason']) ? trim((string) $_POST['reason']) : '';
        $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
        $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';

        if ($offerId <= 0) {
            echo json_encode(['status' => false, 'info' => 'Invalid offer ID']);
        } elseif (empty($reason)) {
            echo json_encode(['status' => false, 'info' => lang('Please select a reason.')]);
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => false, 'info' => lang('Please enter a valid e-mail address.')]);
        } else {
            $sth_off = $db->prepare('SELECT name, slug FROM `'._DB_PREFIX_.'offer` WHERE id=:id LIMIT 1');
            $sth_off->bindValue(':id', $offerId, PDO::PARAM_INT);
            $sth_off->execute();
            $offerData = $sth_off->fetch(PDO::FETCH_ASSOC);

            if (!$offerData) {
                echo json_encode(['status' => false, 'info' => 'Offer not found']);
            } else {
                $sth_ins = $db->prepare('INSERT INTO `'._DB_PREFIX_.'abuse_reports` (offer_id, reason, description, email, created_at, ip) VALUES (:offer_id, :reason, :description, :email, NOW(), :ip)');
                $sth_ins->bindValue(':offer_id', $offerId, PDO::PARAM_INT);
                $sth_ins->bindValue(':reason', $reason, PDO::PARAM_STR);
                $sth_ins->bindValue(':description', $description, PDO::PARAM_STR);
                $sth_ins->bindValue(':email', $email, PDO::PARAM_STR);
                $sth_ins->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
                $sth_ins->execute();

                $offerUrl = makeAbsoluteUrl(path('offer', $offerId, $offerData['slug']));
                $reasons_pl = [
                    'spam' => 'Spam',
                    'fraud' => 'Oszustwo / Wyłudzenie',
                    'outdated' => 'Nieaktualne',
                    'other' => 'Inny powód'
                ];
                $reason_label = $reasons_pl[$reason] ?? $reason;

                mailsQueueAdd('report_offer', $settings['email'], [
                    'offer_name' => $offerData['name'],
                    'offer_url' => $offerUrl,
                    'reason' => $reason_label,
                    'description' => $description ? htmlspecialchars($description) : 'Brak dodatkowego opisu.',
                    'email' => $email
                ]);

                echo json_encode(['status' => true, 'info' => lang('Report has been submitted successfully.')]);
            }
        }

    } else {
        echo json_encode(['status' => false, 'info' => 'Invalid action']);
    }

} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'offers_sugested_keywords' && !empty($_GET['keywords'])) {
        echo json_encode(\App\Offer::getNames($_GET['keywords']));
    } elseif (($action === 'compare_offers' || $action === 'load_clipboard_offers') && !empty($_GET['ids'])) {
        $raw_ids = explode(',', (string) $_GET['ids']);
        $ids = [];
        foreach ($raw_ids as $id) {
            $id = (int)$id;
            if ($id > 0) $ids[] = $id;
        }
        if (empty($ids)) {
            echo json_encode([]);
            exit;
        }
        $in_placeholder = implode(',', $ids);
        $sth = $db->query("SELECT o.*, 
            u.username as username, 
            u.avatar as user_avatar, 
            c.name as category_name, 
            c.slug as category_slug, 
            s.name as state_name, 
            s.slug as state_slug, 
            t.name as type_name, 
            t.slug as type_slug,
            (SELECT AVG(rating) FROM "._DB_PREFIX_."opinion WHERE user_id=o.user_id AND active=1) AS user_rating_avg,
            (SELECT COUNT(id) FROM "._DB_PREFIX_."opinion WHERE user_id=o.user_id AND active=1) AS user_rating_count,
            (SELECT COUNT(id) FROM "._DB_PREFIX_."opinion WHERE user_id=o.user_id AND active=1 AND rating>=4) AS user_rating_positive_count
            FROM "._DB_PREFIX_."offer o 
            LEFT JOIN "._DB_PREFIX_."user u ON o.user_id=u.id 
            LEFT JOIN "._DB_PREFIX_."category c ON o.category_id=c.id 
            LEFT JOIN "._DB_PREFIX_."state s ON o.state_id=s.id 
            LEFT JOIN "._DB_PREFIX_."type t ON o.type_id=t.id 
            WHERE o.id IN ($in_placeholder) AND o.active=1");
        $offers = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($offers as &$offer) {
            $offer['formatted_price'] = $offer['price_free'] ? lang('For free') : ($offer['price'] > 0 ? showCurrency($offer['price']) : lang('Price on request'));
            if ($offer['thumb']) {
                $offer['thumb_url'] = 'upload/photos/' . $offer['thumb'];
            } else {
                $offer['thumb_url'] = 'views/' . $settings['template'] . '/images/no_image.png';
            }
            $offer['url'] = path('offer', $offer['id'], $offer['slug']);
        }
        echo json_encode($offers);
    } else {
        echo json_encode(['status' => false, 'info' => 'Access denied']);
    }
}
