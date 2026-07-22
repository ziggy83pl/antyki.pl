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

if($settings['index_box_subcategories']){
	$render_variables['categories'] = \App\Category::getAllCategoriesTree();
}elseif($settings['search_box_category']){
	$render_variables['categories'] = \App\Category::getCategories();
}

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
