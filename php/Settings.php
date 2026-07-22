<?php


namespace App;

use PDO;
use Exception;
class Settings {

	public static function save($name,$type='str'): void{
		$db = \App\Core\App::db();
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'settings` SET value=:value WHERE name=:name LIMIT 1');
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		if($type=='isset'){
			$sth->bindValue(':value', isset($_POST[$name]), PDO::PARAM_INT);
		}else{
			$sth->bindValue(':value', $_POST[$name], PDO::PARAM_STR);
		}
		$sth->execute();
	}

}
