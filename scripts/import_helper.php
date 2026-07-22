<?php

require_once __DIR__ . '/../config/config.php';

if (php_sapi_name() !== 'cli') {
    die('Only CLI allowed');
}

$action = $argv[1] ?? '';

if ($action === 'load_states') {
    $sth = $db->query("SELECT id, state_id, slug, name FROM " . _DB_PREFIX_ . "state");
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        echo implode("\t", [$row['id'], $row['state_id'], $row['slug'], $row['name']]) . "\n";
    }
} elseif ($action === 'check_offer_exists') {
    $title = $argv[2] ?? '';
    $sth = $db->prepare("SELECT COUNT(1) FROM " . _DB_PREFIX_ . "offer WHERE name = :name");
    $sth->execute([':name' => $title]);
    echo $sth->fetchColumn() . "\n";
} elseif ($action === 'import_offer') {
    // Read JSON from stdin
    $json = file_get_contents('php://stdin');
    $offer = json_decode($json, true);
    if (!$offer) {
        die(json_encode(['success' => false, 'error' => 'Invalid JSON input']));
    }

    try {
        $db->beginTransaction();

        // Check if offer already exists by title
        $check_sth = $db->prepare("SELECT id FROM " . _DB_PREFIX_ . "offer WHERE name = :name LIMIT 1");
        $check_sth->execute([':name' => $offer['title']]);
        $existing_id = $check_sth->fetchColumn();
        if ($existing_id) {
            $db->rollBack();
            echo json_encode(['success' => true, 'status' => 'duplicate', 'offer_id' => $existing_id]);
            exit;
        }

        $slug = $offer['slug'];
        $code = $offer['code'];
        $price_negotiate = $offer['price'] == 0 ? 1 : 0;
        $price_free = 0;
        $price_val = $offer['price'] > 0 ? $offer['price'] : null;

        $insert_offer_sql = "
            INSERT INTO " . _DB_PREFIX_ . "offer (
                user_id, name, slug, price, price_negotiate, price_free,
                address, address_lat, address_long, phone, email,
                category_id, state_id, state2_id, type_id, description,
                active, admin_confirmed, promoted, code, ip,
                date_start, days, date_finish, date
            ) VALUES (
                0, :name, :slug, :price, :price_negotiate, :price_free,
                :address, NULL, NULL, '500-111-222', 'imported@example.com',
                :category_id, :state_id, :state2_id, :type_id, :description,
                1, 1, 0, :code, '127.0.0.1',
                NOW(), 30, (NOW() + INTERVAL 30 DAY), NOW()
            )
        ";

        $sth = $db->prepare($insert_offer_sql);
        $sth->execute([
            ':name' => $offer['title'],
            ':slug' => $slug,
            ':price' => $price_val,
            ':price_negotiate' => $price_negotiate,
            ':price_free' => $price_free,
            ':address' => $offer['address'],
            ':category_id' => $offer['category_id'],
            ':state_id' => $offer['state_id'],
            ':state2_id' => $offer['state2_id'],
            ':type_id' => $offer['type_id'],
            ':description' => $offer['desc'],
            ':code' => $code
        ]);

        $offer_id = $db->lastInsertId();

        $db->commit();
        echo json_encode(['success' => true, 'status' => 'imported', 'offer_id' => $offer_id]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($action === 'insert_photo') {
    $json = file_get_contents('php://stdin');
    $photo = json_decode($json, true);
    if (!$photo) {
        die(json_encode(['success' => false, 'error' => 'Invalid JSON input']));
    }

    try {
        $sth = $db->prepare("
            INSERT INTO " . _DB_PREFIX_ . "photo (
                user_id, offer_id, position, folder, thumb, url, date
            ) VALUES (
                0, :offer_id, :position, :folder, :thumb, :url, NOW()
            )
        ");
        $sth->execute([
            ':offer_id' => $photo['offer_id'],
            ':position' => $photo['position'],
            ':folder' => $photo['folder'],
            ':thumb' => $photo['thumb'],
            ':url' => $photo['url']
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    die('Invalid action');
}
