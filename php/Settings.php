<?php

namespace App;

use PDO;
use Exception;

class Settings {

	public static function save($name, $type = 'str'): void {
		$db = \App\Core\App::db();
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'settings` (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value=VALUES(value)');
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		if ($type == 'isset') {
			$val = (!empty($_POST[$name]) && $_POST[$name] !== '0') ? '1' : '';
			$sth->bindValue(':value', $val, PDO::PARAM_STR);
		} else {
			$sth->bindValue(':value', $_POST[$name] ?? '', PDO::PARAM_STR);
		}
		$sth->execute();
	}

}
