<?php
/************************************************************************

 *
 * *********************************************************************
 * THIS SOFTWARE IS LICENSED - YOU CAN MODIFY THESE FILES
 * BUT YOU CAN NOT REMOVE OF ORIGINAL COMMENTS!
 * ACCORDING TO THE LICENSE YOU CAN USE THE SCRIPT ON ONE DOMAIN. DETECTION
 * COPY SCRIPT WILL RESULT IN A HIGH FINANSIAL PENALTY AND WITHDRAWAL
 * LICENSE THE SCRIPT
 * *********************************************************************/

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='save_index_page' and isset($_POST['index_page']) and checkToken('admin_save_index_page')){
			global $purifier;
			if ($purifier) {
				$_POST['index_page'] = $purifier->purify($_POST['index_page']);
				if (isset($_POST['footer_bottom'])) {
					$_POST['footer_bottom'] = $purifier->purify($_POST['footer_bottom']);
				}
				if (isset($_POST['footer_text'])) {
					$_POST['footer_text'] = $purifier->purify($_POST['footer_text']);
				}
			}
			\App\Settings::save('index_page');
			if (isset($_POST['footer_bottom'])) {
				\App\Settings::save('footer_bottom');
			}
			if (isset($_POST['footer_text'])) {
				\App\Settings::save('footer_text');
			}
			\App\Settings::save('show_modernization_alert', 'isset');
			$render_variables['alert_success'][] = lang('Changes have been saved');
			getSettings();
		}elseif($_POST['action']=='add_slide' and checkToken('admin_add_slide')){
      \App\Slider::add();
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}elseif($_POST['action']=='save_slides' and checkToken('admin_save_slides')){
      \App\Slider::save($_POST);
			$render_variables['alert_success'][] = lang('Changes have been saved');
		}
	}

  $render_variables['slider'] = \App\Slider::getSlider();

	$title = lang('Index page').' - '.$title_default;

}
