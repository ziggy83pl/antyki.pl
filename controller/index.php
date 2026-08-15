<?php
/**
 * Frontend controller entry.
 * NOTICE2 modernization in progress.
 */

if(!isset($settings['base_url'])){
	die('Access denied!');
}

$render_variables['slider'] = \App\Slider::getSlider();

$render_variables['offers'] = \App\Offer::loadOffers($settings['limit_page_index'],'index_page');

if (!empty($settings['show_most_viewed_offers']) && $settings['show_most_viewed_offers'] !== '0') {
	$most_viewed_limit = !empty($settings['number_most_viewed_offers']) ? (int)$settings['number_most_viewed_offers'] : 3;
	$render_variables['most_viewed_offers'] = \App\Offer::getMostViewedOffers($most_viewed_limit);
}

$render_variables['categories'] = \App\Category::getAllCategoriesTree();

$render_variables['states'] = getAllStates();
if($settings['search_box_type']){
	$render_variables['types'] = getTypes();
}

if($settings['search_box_price']){
  $render_variables['search_show_price'] = \App\Option::checkShowPrice();
}else{
  $render_variables['search_show_price'] = false;
}

if($settings['enable_articles']){
	$render_variables['articles'] = \App\Article::getArticles(6);
}
