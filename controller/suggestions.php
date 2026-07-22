<?php
if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(!empty($path_parts[1])){
	throw new noFoundException();
}

$current_user_id = $user->getId() ?: 0;

// Handle AJAX Voting Action
if (isset($_POST['action']) && $_POST['action'] === 'vote' && isset($_POST['suggestion_id']) && isset($_POST['vote'])) {
    header('Content-Type: application/json');
    if (!$user->logged_in) {
        echo json_encode(['status' => 'error', 'message' => lang('Please log in to submit suggestions or vote.')]);
        exit;
    }
    if (!checkToken('suggestions_vote')) {
        echo json_encode(['status' => 'error', 'message' => lang('Session expired or invalid token. Please try again.')]);
        exit;
    }

    $suggestion_id = (int)$_POST['suggestion_id'];
    $vote_val = (int)$_POST['vote'] === 1 ? 1 : -1;

    try {
        // Verify suggestion is approved/implemented
        $check = $db->prepare('SELECT COUNT(*) FROM `'._DB_PREFIX_.'suggestions` WHERE id = ? AND status IN ("approved", "implemented")');
        $check->execute([$suggestion_id]);
        if (!$check->fetchColumn()) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid suggestion or status.']);
            exit;
        }

        // Cast vote
        $sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'suggestion_votes` (suggestion_id, user_id, vote, created_at) 
            VALUES (:s_id, :u_id, :vote, NOW()) 
            ON DUPLICATE KEY UPDATE vote = VALUES(vote)');
        $sth->execute([
            ':s_id' => $suggestion_id,
            ':u_id' => $current_user_id,
            ':vote' => $vote_val
        ]);

        // Get updated vote count
        $countSth = $db->prepare('SELECT 
            COALESCE(SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END), 0) AS votes_for,
            COALESCE(SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END), 0) AS votes_against
            FROM `'._DB_PREFIX_.'suggestion_votes` WHERE suggestion_id = ?');
        $countSth->execute([$suggestion_id]);
        $row = $countSth->fetch(PDO::FETCH_ASSOC);
        
        $votes_for = (int)$row['votes_for'];
        $votes_against = (int)$row['votes_against'];
        $total = $votes_for + $votes_against;

        $ratio_for = $total > 0 ? round(($votes_for / $total) * 100) : 50;
        $ratio_against = $total > 0 ? round(($votes_against / $total) * 100) : 50;

        echo json_encode([
            'status' => 'success',
            'votes_for' => $votes_for,
            'votes_against' => $votes_against,
            'ratio_for' => $ratio_for,
            'ratio_against' => $ratio_against,
            'message' => lang('Vote cast successfully.')
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => getSafeExceptionMessage($e)]);
        exit;
    }
}

// Handle Add Suggestion Form Action
if (isset($_POST['action']) && $_POST['action'] === 'add_suggestion') {
    if (!$user->logged_in) {
        $render_variables['alert_danger'][] = lang('Please log in to submit suggestions or vote.');
    } elseif (!checkToken('add_suggestion')) {
        $render_variables['alert_danger'][] = lang('Session expired or invalid token. Please try again.');
    } elseif (empty($_POST['title']) || strlen(trim($_POST['title'])) < 3) {
        $render_variables['alert_danger'][] = 'Tytuł jest zbyt krótki (min. 3 znaki).';
        $render_variables['input'] = $_POST;
    } elseif (empty($_POST['description']) || strlen(trim($_POST['description'])) < 10) {
        $render_variables['alert_danger'][] = 'Opis jest zbyt krótki (min. 10 znaków).';
        $render_variables['input'] = $_POST;
    } elseif (empty($_POST['type']) || !in_array($_POST['type'], ['bug', 'improvement', 'feature'])) {
        $render_variables['alert_danger'][] = 'Niepoprawny typ zgłoszenia.';
        $render_variables['input'] = $_POST;
    } else {
        try {
            $sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'suggestions` (user_id, title, description, type, status, created_at) 
                VALUES (:u_id, :title, :description, :type, "pending", NOW())');
            $sth->execute([
                ':u_id' => $current_user_id,
                ':title' => strip_tags(trim($_POST['title'])),
                ':description' => strip_tags(trim($_POST['description'])),
                ':type' => $_POST['type']
            ]);
            $render_variables['alert_success'][] = lang('Thanks! Your suggestion has been submitted and is waiting for moderator approval.');
        } catch (Exception $e) {
            $render_variables['alert_danger'][] = getSafeExceptionMessage($e);
        }
    }
}

// Fetch Approved Suggestions List
try {
    $sth = $db->prepare('SELECT s.*, u.username, 
           COALESCE(v_for.count, 0) AS votes_for,
           COALESCE(v_against.count, 0) AS votes_against,
           COALESCE(user_v.vote, 0) AS user_vote
    FROM `'._DB_PREFIX_.'suggestions` s
    LEFT JOIN `'._DB_PREFIX_.'user` u ON s.user_id = u.id
    LEFT JOIN (
        SELECT suggestion_id, COUNT(*) AS count 
        FROM `'._DB_PREFIX_.'suggestion_votes` WHERE vote = 1 GROUP BY suggestion_id
    ) v_for ON s.id = v_for.suggestion_id
    LEFT JOIN (
        SELECT suggestion_id, COUNT(*) AS count 
        FROM `'._DB_PREFIX_.'suggestion_votes` WHERE vote = -1 GROUP BY suggestion_id
    ) v_against ON s.id = v_against.suggestion_id
    LEFT JOIN `'._DB_PREFIX_.'suggestion_votes` user_v ON s.id = user_v.suggestion_id AND user_v.user_id = :u_id
    WHERE s.status IN ("approved", "implemented")
    ORDER BY s.created_at DESC');
    
    $sth->bindValue(':u_id', $current_user_id, PDO::PARAM_INT);
    $sth->execute();
    $suggestions = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Calculate ratios
    foreach ($suggestions as &$s) {
        $votes_for = (int)$s['votes_for'];
        $votes_against = (int)$s['votes_against'];
        $total = $votes_for + $votes_against;
        
        $s['total_votes'] = $total;
        $s['ratio_for'] = $total > 0 ? round(($votes_for / $total) * 100) : 50;
        $s['ratio_against'] = $total > 0 ? round(($votes_against / $total) * 100) : 50;
    }
    
    $render_variables['suggestions'] = $suggestions;
} catch (Exception $e) {
    $render_variables['alert_danger'][] = getSafeExceptionMessage($e);
}

$settings['seo_title'] = lang('Suggestions') . ' - ' . $settings['title'];
$settings['seo_description'] = lang('Suggestions') . ' - ' . $settings['description'];
