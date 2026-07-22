<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='delete' and !empty($_POST['id']) and checkToken('admin_delete_opinion')){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'opinion WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->execute();
			$render_variables['alert_success'][] = lang('Opinion has been deleted');
		}elseif($_POST['action']=='bulk_delete' and !empty($_POST['ids']) and is_array($_POST['ids']) and checkToken('admin_bulk_delete_opinions')){
			$ids = array_map('intval', $_POST['ids']);
			if(!empty($ids)){
				$in = str_repeat('?,', count($ids) - 1) . '?';
				$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'opinion WHERE id IN ('.$in.')');
				$sth->execute($ids);
				$render_variables['alert_success'][] = lang('Selected opinions have been deleted');
			}
		}
	}

	$limit = 50;

	$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS o.*, u1.username as target_username, u2.username as author_username FROM '._DB_PREFIX_.'opinion o LEFT JOIN '._DB_PREFIX_.'user u1 ON o.user_id = u1.id LEFT JOIN '._DB_PREFIX_.'user u2 ON o.author_id = u2.id ORDER BY o.date DESC LIMIT :limit_from, :limit_to');
	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
	$sth->execute();

	$opinions = [];
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
		$opinions[] = $row;
	}
	if(!empty($opinions)){
		$render_variables['opinions'] = $opinions;
	}

	generatePagination($limit);

	$title = lang('Opinions').' - '.$title_default;
}
