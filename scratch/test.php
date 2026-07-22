<?php
require 'config/config.php';
require 'php/db.php';
print_r($db->query('SELECT * FROM '._DB_PREFIX_.'settings LIMIT 1')->fetch());

