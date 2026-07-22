<?php

declare(strict_types=1);

namespace App;

use PDO;
use Exception;


class Category {

	private static $categories_list = [];

	public static function getCategories(int $category_id = 0): array
	{
		$db = \App\Core\App::db();
		$categories = [];
		$sth = $db->prepare('SELECT *, COALESCE((SELECT count FROM '._DB_PREFIX_.'subcategory WHERE category_id=:category_id AND id = subcategory_id ),0) as number_offers FROM '._DB_PREFIX_.'category WHERE category_id=:category_id ORDER BY position');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){$categories[] = $row;}
		return $categories;
	}

	public static function getAllCategoriesTree(): array
	{
		$db = \App\Core\App::db();
		$sth = $db->query('SELECT *, COALESCE((SELECT count FROM '._DB_PREFIX_.'subcategory WHERE category_id = c.category_id AND id = subcategory_id ),0) as number_offers FROM '._DB_PREFIX_.'category c ORDER BY position');
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);

		$categories = [];
		$subcategories = [];
		foreach ($all as $cat) {
			if ((int)$cat['category_id'] === 0) {
				$categories[$cat['id']] = $cat;
				$categories[$cat['id']]['list_subcategories'] = [];
			} else {
				$subcategories[] = $cat;
			}
		}
		foreach ($subcategories as $subcat) {
			$parentId = (int)$subcat['category_id'];
			if (isset($categories[$parentId])) {
				$categories[$parentId]['list_subcategories'][] = $subcat;
			}
		}
		return array_values($categories);
	}

	public static function getCategory(int $id, bool $breadcrumbs = false): array|false
	{
		$db = \App\Core\App::db();
		$category = [];
		if($id>0){
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
			$category = $sth->fetch(PDO::FETCH_ASSOC);
			if($category and $breadcrumbs){
				$category['breadcrumbs'] = static::getBreadcrumbs($category);
			}
		}
		return $category;
	}

	public static function getAllCategories(){
		global $categories_temp;
		self::$categories_list = [];
		static::getSubcategories();
		return self::$categories_list;
	}

	public static function getSubcategories(int $category_id=0,int $depth=0): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE category_id=:category_id ORDER BY position, name');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$depth++;
			$row['depth'] = $depth;
			self::$categories_list[$row['id']] = $row;
			static::getSubcategories((int)$row['id'],$depth);
			$depth--;
		}
	}

	public static function getBreadcrumbs(array $category): array{
		$db = \App\Core\App::db();
		$breadcrumbs[] = $category;
		$navigation_overcategory = $category['category_id'];
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE id=:navigation_overcategory LIMIT 1');
		while($navigation_overcategory!=0){
			$sth->bindValue(':navigation_overcategory', $navigation_overcategory, PDO::PARAM_INT);
			$sth->execute();
			$overcategory = $sth->fetch(PDO::FETCH_ASSOC);
			if($overcategory){
				$navigation_overcategory = $overcategory['category_id'];
				$breadcrumbs[] = $overcategory;
			}else{
				$navigation_overcategory = 0;
			}
		}
		return(array_reverse($breadcrumbs));
	}

	public static function getCategoryFromPath($path){
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE path=:path LIMIT 1');
		$sth->bindValue(':path', $path, PDO::PARAM_STR);
		$sth->execute();
		$category = $sth->fetch(PDO::FETCH_ASSOC);
		return $category;
	}

	public static function refreshAllSubcategories(int $category_id=0): void{
		$db = \App\Core\App::db();
		if($category_id){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'subcategory WHERE category_id=:category_id OR ( category_id=0 AND subcategory_id=:category_id)');
			$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
			$sth->execute();
			$over_category = static::getCategory($category_id)['category_id'];
		}else{
			$db->query('TRUNCATE `'._DB_PREFIX_.'subcategory`');
			$over_category = 0;
		}
		static::countInSubcategories($category_id);

		$count = 0;
		$query_string = '';
		foreach(self::$categories_list as $category){
			if($query_string){$query_string .= ',';}
			$query_string .= ' ('.$category['category_id'].','.$category['subcategory_id'].','.$category['count'].') ';
			if($category['category_id']==$category_id){$count += $category['count'];}
		}

		if($query_string){$query_string .= ',';}
		$query_string .= ' ('.$over_category.','.$category_id.','.($count+static::countInCategory($category_id)).') ';

		$db->query('INSERT INTO `'._DB_PREFIX_.'subcategory`(`category_id`, `subcategory_id`, `count`) VALUES '.$query_string);
	}

	public static function countInCategory($category_id=0): int|string|null|false{
		if($category_id===null) return 0;
		$db = \App\Core\App::db();
		$sth = $db->prepare('SELECT COUNT(1) as count FROM '._DB_PREFIX_.'offer WHERE active=1 AND category_id=:category_id');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetchColumn();
	}

	/**
     * @return mixed[][]|float[]|int[]
     */
    public static function countInSubcategories($category_id=0): array{
		if($category_id===null) return ['count'=>0, 'subcategories'=>[]];
		$db = \App\Core\App::db();
		$count = 0;
		$subcategories = [];
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE category_id=:category_id');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		foreach($sth as $row){
			$count_offers = static::countInCategory($row['id']);
			$return = static::countInSubcategories($row['id']);
			$subcategories[$row['id']] = $count_offers + $return['count'];
			foreach($return['subcategories'] as $subcategory_id=>$subcategory_count){
				$subcategories[$subcategory_id] = $subcategory_count;
			}
			$count = $count + $count_offers + $return['count'];
		}
		foreach($subcategories as $subcategory_id=>$subcategory_count){
			array_push(self::$categories_list,['category_id'=>$category_id, 'subcategory_id'=>$subcategory_id,'count'=>$subcategory_count]);
		}
		return ['count'=>$count, 'subcategories'=>$subcategories];
	}

	public static function refreshCount($category_id=0): void{
		$db = \App\Core\App::db();
		self::$categories_list = [];
		if($category_id>0){
			$category = static::getCategory($category_id, true);
			$category_id = $category['breadcrumbs'][0]['id'] ?? null;
			if($category_id===null){ return; }
			$return = static::countInSubcategories($category_id);
			array_push(self::$categories_list,['category_id'=>0, 'subcategory_id'=>$category_id,'count'=>$return['count']+static::countInCategory($category_id)]);
		}else{
			static::countInSubcategories();
		}
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'subcategory` SET `count`=:count WHERE `category_id`=:category_id AND `subcategory_id`=:subcategory_id LIMIT 1');
		foreach(self::$categories_list as $subcategory){
			$sth->bindValue(':category_id', $subcategory['category_id'], PDO::PARAM_INT);
			$sth->bindValue(':subcategory_id', $subcategory['subcategory_id'], PDO::PARAM_INT);
			$sth->bindValue(':count', $subcategory['count'], PDO::PARAM_INT);
			$sth->execute();
		}
	}

	public static function add(array $data,int $category_id=0): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'category`(`category_id`, `position`, `slug`, `name`) VALUES (:category_id,:position,:slug,:name)');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->bindValue(':position', getPosition('category', 'category_id='.$category_id), PDO::PARAM_INT);
		$sth->bindValue(':slug', slug($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':name', trim((string) $data['name']), PDO::PARAM_STR);
		$sth->execute();

		$id = $db->lastInsertId();
		static::edit($data,(int)$id);

		$category = static::getCategory((int)$id, true);

		$over_categories = [];
		if(!empty($category['breadcrumbs'])){
			foreach($category['breadcrumbs'] as $value){
				$over_categories[] = $value['category_id'];
			}
		}
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'subcategory`(`category_id`, `subcategory_id`) VALUES (:category_id,'.$id.')');
		foreach($over_categories as $value){
			$sth->bindValue(':category_id', $value, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	public static function edit(array $data,int $id): void{
		$db = \App\Core\App::db();
        $purifier = \App\Core\App::purifier();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'category` SET slug=:slug, name=:name, cost=:cost, thumb=:thumb, content=:content, h1=:h1, title=:title, keywords=:keywords, description=:description WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);

		$name = isset($data['name']) ? trim((string)$data['name']) : '';
		$cost = (!isset($data['cost']) || trim((string)$data['cost']) === '') ? '0.00' : trim((string)$data['cost']);
		$thumb = isset($data['thumb']) ? trim((string)$data['thumb']) : '';
		$content = isset($data['content']) ? trim((string)$data['content']) : '';
		if ($purifier) {
			$content = $purifier->purify($content);
		}
		$h1 = isset($data['h1']) ? trim((string)$data['h1']) : '';
		$title = isset($data['title']) ? trim((string)$data['title']) : '';
		$keywords = isset($data['keywords']) ? trim((string)$data['keywords']) : '';
		$description = isset($data['description']) ? trim((string)$data['description']) : '';

		$sth->bindValue(':slug', slug($name), PDO::PARAM_STR);
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':cost', $cost, PDO::PARAM_STR);
		$sth->bindValue(':thumb', $thumb, PDO::PARAM_STR);
		$sth->bindValue(':content', $content, PDO::PARAM_STR);
		$sth->bindValue(':h1', $h1, PDO::PARAM_STR);
		$sth->bindValue(':title', $title, PDO::PARAM_STR);
		$sth->bindValue(':keywords', $keywords, PDO::PARAM_STR);
		$sth->bindValue(':description', $description, PDO::PARAM_STR);
		$sth->execute();
		static::refreshPath($id);
	}

	public static function refreshPath(int $id): void{
		$db = \App\Core\App::db();
		$category = static::getCategory($id, true);
		if($category){
			$over_categories = [];
			foreach($category['breadcrumbs'] as $key => $value){
				$over_categories[] = $value['slug'];
			}
			$path = implode('/',$over_categories);

			if($path!=$category['path']){

				$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'category` SET path=:path WHERE id=:id LIMIT 1');
				$sth->bindValue(':id', $id, PDO::PARAM_INT);
				$sth->bindValue(':path', $path, PDO::PARAM_STR);
				$sth->execute();

				self::$categories_list = [];

				static::getSubcategories($id);

				$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'category` SET path=:path WHERE id=:id LIMIT 1');
				foreach(self::$categories_list as $subcategory){
					$category = static::getCategory($subcategory['id'], true);
					$over_categories = [];
					foreach($category['breadcrumbs'] as $key => $value){
						$over_categories[] = $value['slug'];
					}
					$path = implode('/',$over_categories);
					$sth->bindValue(':id', $subcategory['id'], PDO::PARAM_INT);
					$sth->bindValue(':path', $path, PDO::PARAM_STR);
					$sth->execute();
				}
			}
		}
	}

	public static function remove(int $id): void{
		$db = \App\Core\App::db();
		$category = static::getCategory($id);
		if($category){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'subcategory WHERE subcategory_id=:id');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'offer SET category_id="0" WHERE category_id=:category_id');
			$sth->bindValue(':category_id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'option_category WHERE option_category=:id');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'category WHERE category_id=:id');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			foreach($sth as $row){static::remove((int)$row['id']);}
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'category WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			static::refreshCount((int)$category['category_id']);
		}
	}
}
