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
			$admin->logActivity('Usunięcie ogłoszenia', 'ID ogłoszenia: #'.$_POST['id']);
			if(isset($_POST['add_email_black_list']) and !empty($_POST['email'])){
				addEmailToBlackList($_POST['email']);
			}
			if(isset($_POST['add_ip_black_list']) and !empty($_POST['ip'])){
				addIpToBlackList($_POST['ip']);
			}
			$render_variables['alert_danger'][] = lang('The offer has been deleted');
		}elseif($_POST['action']=='deactivate_offer' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_deactivate_offer')){
			\App\Offer::deactivate($_POST['id']);
			$admin->logActivity('Dezaktywacja ogłoszenia', 'ID: #'.$_POST['id']);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='activate_offer' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['date_finish']) and checkToken('admin_activate_offer')){
			\App\Offer::activate($_POST['id'],$_POST['date_finish'],1);
			$admin->logActivity('Aktywacja/Zatwierdzenie ogłoszenia', 'ID: #'.$_POST['id']);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='add_offer_note' and isset($_POST['offer_id']) and $_POST['offer_id']>0 and !empty($_POST['note']) and checkToken('admin_offer_note')){
			$admin->addNote('offer', (int)$_POST['offer_id'], trim($_POST['note']));
			$render_variables['alert_success'][] = 'Dodano notatkę wewnętrzną do ogłoszenia';
		}elseif($_POST['action']=='remove_offers' and isset($_POST['offers']) and is_array($_POST['offers']) and checkToken('admin_action_offers')){
			$count = 0;
			foreach($_POST['offers'] as $key => $value){
				if($value>0){
					\App\Offer::remove($value);
					$count++;
				}
			}
			$admin->logActivity('Masowe usunięcie ogłoszeń', 'Usunięto '.$count.' ogłoszeń');
			$render_variables['alert_danger'][] = lang('The offer has been deleted');
		}elseif($_POST['action']=='active_offers' and isset($_POST['offers']) and is_array($_POST['offers']) and checkToken('admin_action_offers')){
			$count = 0;
			foreach($_POST['offers'] as $key => $value){
				if($value>0){
					\App\Offer::activate($value);
					$count++;
				}
			}
			$admin->logActivity('Masowe zatwierdzenie/aktywacja ogłoszeń', 'Aktywowano '.$count.' ogłoszeń');
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='deactive_offers' and isset($_POST['offers']) and is_array($_POST['offers']) and checkToken('admin_action_offers')){
			$count = 0;
			foreach($_POST['offers'] as $key => $value){
				if($value>0){
					\App\Offer::deactivate($value);
					$count++;
				}
			}
			$admin->logActivity('Masowa dezaktywacja ogłoszeń', 'Zdeaktywowano '.$count.' ogłoszeń');
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}
	}

	$render_variables['offers'] = \App\Offer::loadOffers(50,'admin');
	
	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'user where active = 1 order by username');
	foreach($sth as $row){$users[] = $row;}
	if(isset($users)){$render_variables['users'] = $users;}

	$title = lang('Offers').' - '.$title_default;

}
