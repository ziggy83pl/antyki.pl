<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action']) and checkToken('admin_suggestions')){
		if($_POST['action']=='approve' and isset($_POST['id']) and $_POST['id']>0){
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'suggestions` SET status = "approved" WHERE id = ?');
			$sth->execute([(int)$_POST['id']]);
			$render_variables['alert_success'][] = 'Sugestia została zatwierdzona.';
		}elseif($_POST['action']=='reject' and isset($_POST['id']) and $_POST['id']>0){
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'suggestions` SET status = "rejected" WHERE id = ?');
			$sth->execute([(int)$_POST['id']]);
			$render_variables['alert_danger'][] = 'Sugestia została odrzucona.';
		}elseif($_POST['action']=='implement' and isset($_POST['id']) and $_POST['id']>0){
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'suggestions` SET status = "implemented" WHERE id = ?');
			$sth->execute([(int)$_POST['id']]);
			$render_variables['alert_success'][] = 'Sugestia została oznaczona jako zrealizowana.';
		}elseif($_POST['action']=='delete' and isset($_POST['id']) and $_POST['id']>0){
			// Delete votes first
			$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'suggestion_votes` WHERE suggestion_id = ?');
			$sth->execute([(int)$_POST['id']]);
			// Delete suggestion
			$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'suggestions` WHERE id = ?');
			$sth->execute([(int)$_POST['id']]);
			$render_variables['alert_danger'][] = 'Sugestia została usunięta.';
		}
	}

	$limit = 50;
	$where_statement = ' true ';
	$bind_values = [];

	if(isset($_GET['status']) and in_array($_GET['status'], ['pending', 'approved', 'rejected', 'implemented'])){
		$where_statement .= ' AND s.status = :status ';
		$bind_values['status'] = $_GET['status'];
	}

	if(!empty($_GET['type']) and in_array($_GET['type'], ['bug', 'improvement', 'feature'])){
		$where_statement .= ' AND s.type = :type ';
		$bind_values['type'] = $_GET['type'];
	}

	if(!empty($_GET['search'])){
		$where_statement .= ' AND (s.title LIKE :search OR s.description LIKE :search) ';
		$bind_values['search'] = '%'.$_GET['search'].'%';
	}

	$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS s.*, u.username, 
		COALESCE(v_for.count, 0) AS votes_for,
		COALESCE(v_against.count, 0) AS votes_against
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
		WHERE '.$where_statement.' 
		ORDER BY s.created_at DESC 
		LIMIT :limit_from, :limit_to');

	foreach($bind_values as $key => $value){
		$sth->bindValue(':'.$key, $value, PDO::PARAM_STR);
	}
	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
	$sth->execute();

	$suggestions = $sth->fetchAll(PDO::FETCH_ASSOC);

	foreach($suggestions as &$s){
		$votes_for = (int)$s['votes_for'];
		$votes_against = (int)$s['votes_against'];
		$total = $votes_for + $votes_against;
		$s['total_votes'] = $total;
		$s['ratio_for'] = $total > 0 ? round(($votes_for / $total) * 100) : 50;
		$s['ratio_against'] = $total > 0 ? round(($votes_against / $total) * 100) : 50;
	}

	$render_variables['suggestions'] = $suggestions;
	generatePagination($limit);

	$title = 'Zgłoszenia i sugestie - '.$title_default;
}
