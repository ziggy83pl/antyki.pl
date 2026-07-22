<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='remove_logs' and !empty($_POST['type']) and checkToken('admin_remove_logs')){
			if($_POST['type']=='only_removed'){
				$db->query('DELETE FROM '._DB_PREFIX_.'logs_user WHERE user_id NOT IN (SELECT id FROM '._DB_PREFIX_.'user)');
				$render_variables['alert_danger'][] = lang('Successfully deleted');
			}elseif($_POST['type']=='all'){
				$db->query('TRUNCATE `'._DB_PREFIX_.'logs_user`');
				$render_variables['alert_danger'][] = lang('Successfully deleted');
			}
		}
	}
	
	$limit = 100;
	
	$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS lu.*, u.username, u.email FROM '._DB_PREFIX_.'logs_user lu LEFT JOIN '._DB_PREFIX_.'user u ON lu.user_id = u.id ORDER BY '.sortBy().' LIMIT :limit_from, :limit_to');
	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
	$sth->execute();
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
		$logs_users[] = $row;
	}
	if(isset($logs_users)){$render_variables['logs_users'] = $logs_users;}	

	generatePagination($limit);
	
	$title = lang('Logs users').' - '.$title_default;
}
