<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if (isset($_GET['id'])) {
    $_GET['id'] = (int)$_GET['id'];
}
if (isset($_POST['id'])) {
    $_POST['id'] = (int)$_POST['id'];
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='remove_offer' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_offer')){
			\App\Offer::remove($_POST['id']);
			if(isset($_POST['add_email_black_list']) and !empty($_POST['email'])){
				addEmailToBlackList($_POST['email']);
			}
			if(isset($_POST['add_ip_black_list']) and !empty($_POST['ip'])){
				addIpToBlackList($_POST['ip']);
			}
			$render_variables['alert_danger'][] = lang('The offer has been deleted');
		}elseif($_POST['action']=='deactivate_offer' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_deactivate_offer')){
			\App\Offer::deactivate($_POST['id']);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='activate_offer' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['date_finish']) and checkToken('admin_activate_offer')){
			\App\Offer::activate($_POST['id'],$_POST['date_finish'],1);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='disable_promote_offer' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_disable_promote_offer')){
			\App\Offer::disablePromote($_POST['id']);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='enable_promote_offer' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['date']) and checkToken('admin_enable_promote_offer')){
			\App\Offer::enablePromote($_POST['id'],$_POST['date']);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='change_date_finish' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['date_finish']) and checkToken('admin_change_date_finish')){
			global $db;
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'offer` SET date_finish=:date_finish WHERE id=:id LIMIT 1');
			$sth->bindValue(':date_finish', $_POST['date_finish'], PDO::PARAM_STR);
			$sth->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
			$sth->execute();
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='remove_offers' and isset($_POST['offers']) and is_array($_POST['offers']) and checkToken('admin_action_offers')){
			foreach($_POST['offers'] as $key => $value){
				if($value>0){
					\App\Offer::remove($value);
				}
			}
			$render_variables['alert_danger'][] = lang('The offer has been deleted');
		}elseif($_POST['action']=='active_offers' and isset($_POST['offers']) and is_array($_POST['offers']) and checkToken('admin_action_offers')){
			foreach($_POST['offers'] as $key => $value){
				if($value>0){
					\App\Offer::activate($value);
				}
			}
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='deactive_offers' and isset($_POST['offers']) and is_array($_POST['offers'] and checkToken('admin_action_offers'))){
			foreach($_POST['offers'] as $key => $value){
				if($value>0){
					\App\Offer::deactivate($value);
				}
			}
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}
	}

	$render_variables['offers'] = \App\Offer::loadOffers(50,'admin');
	
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'user where active = 1 order by username');
	foreach($sth as $row){$users[] = $row;}
	if(isset($users)){$render_variables['users'] = $users;}

	$title = lang('Offers').' - '.$title_default;

}
