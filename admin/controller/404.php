<?php

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied!');
}

header('HTTP/1.0 404 Not Found');

$controller = '404';
