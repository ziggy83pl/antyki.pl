<?php


namespace App;

use PDO;
use Exception;
class Slider {

	/**
     * Pobiera slajdy z bazy.
     * Każdy slajd zwracany jest jako tablica ['image' => '...', 'title' => '...', 'description' => '...'].
     * Obsługuje stary format (czysty HTML) oraz nowy format (JSON).
     * @return array{image: mixed, title: mixed, description: mixed}[]
     */
    public static function getSlider(): array{
		$db = \App\Core\App::db();
		$slider = [];
		$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'slider');
		foreach($sth as $row){
			$decoded = json_decode((string) $row['content'], true);
			if(is_array($decoded) && isset($decoded['image'])){
				// Nowy format JSON
				$slider[] = [
					'image' => $decoded['image'] ?? '',
					'title' => $decoded['title'] ?? '',
					'description' => $decoded['description'] ?? ''
				];
			} else {
				// Stary format HTML – migracja: wyciągnij src z tagu <img>, nagłówek z <h3> i opis z <p>
				$image = '';
				$title = '';
				$description = '';
				if(preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', (string) $row['content'], $m)){
					$image = $m[1];
				}
				if(preg_match('/<h3[^>]*>(.*?)<\/h3>/si', (string) $row['content'], $m)){
					$title = strip_tags($m[1]);
				}
				if(preg_match('/<p[^>]*>(.*?)<\/p>/si', (string) $row['content'], $m)){
					$description = strip_tags($m[1]);
				}
				$slider[] = [
					'image' => $image,
					'title' => trim($title),
					'description' => trim($description)
				];
			}
		}
		return $slider;
	}

	/**
	 * Dodaje pusty slajd jako strukturalny JSON.
	 */
	public static function add(): void{
		$db = \App\Core\App::db();
		$empty = json_encode(['image' => '', 'title' => '', 'description' => ''], JSON_UNESCAPED_UNICODE);
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'slider`(content) VALUES (:content)');
		$sth->bindValue(':content', $empty, PDO::PARAM_STR);
		$sth->execute();
	}

	/**
	 * Zapisuje slajdy. Oczekuje $_POST z tablicami slide_image[], slide_title[] i slide_description[].
	 */
	public static function save(array $data): void{
		$db = \App\Core\App::db();
		$db->query('TRUNCATE `'._DB_PREFIX_.'slider`');
		if(isset($data['slide_image']) && is_array($data['slide_image'])){
			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'slider`(content) VALUES (:content)');
			foreach($data['slide_image'] as $i => $image){
				$title = $data['slide_title'][$i] ?? '';
				$description = $data['slide_description'][$i] ?? '';
				$content = json_encode([
					'image' => trim((string) $image),
					'title' => trim($title),
					'description' => trim($description)
				], JSON_UNESCAPED_UNICODE);
				$sth->bindValue(':content', $content, PDO::PARAM_STR);
				$sth->execute();
			}
		}
	}

}