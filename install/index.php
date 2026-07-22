<?php
/**
 * Ogłoszenia Nova installation bootstrap.
 * Modernization and cleanup in progress.
 */

/* Modified: Secure session and cookie options */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', '1');
}

session_start();

header('Content-Type: text/html; charset=utf-8');

ini_set('display_errors', '1');
error_reporting(E_ALL);

ob_start();

if(phpversion()<7){
	die('Wrong version of PHP on the server. The minimum supported is 7.0');
}

/* Modified: Check install.lock */
if (file_exists('install.lock') || file_exists('../install.lock')) {
	header('location: /admin');
	die('Installer is locked. Delete install/install.lock to run again.');
}

$install = true;
require_once('../config/db.php');

if(!empty($mysql_server) && $mysql_server !== "db"){
	header('location: /admin');
	die('redirect...');
}

if(!is_writable('../config/db.php') && !is_writable('../')){
	die('File config/db.php or root folder is not writable!');
}

$settings['base_url'] = true;
require_once('../php/global.php');

if(isset($_GET['lang']) and $_GET['lang']!=''){
	$settings['lang'] = langLoad($_GET['lang']);
}else{
	$settings['lang'] = langLoad();
}
$langList = langList();

if(!empty($_POST['base_url']) and !empty($_POST['server']) and !empty($_POST['user']) and !empty($_POST['name']) and !empty($_POST['admin']) and !empty($_POST['password_admin']) and !empty($_POST['password_admin_repeat']) and !empty($_POST['email']) and isset($_POST['db_prefix'])){

	if($_POST['password_admin']!=$_POST['password_admin_repeat']){
		$error = lang('Error! Entered the Password to Admin Panel are different!');
	}else{

		$db_host = str_replace(["\r", "\n"], "", $_POST['server']);
		$db_user = str_replace(["\r", "\n"], "", $_POST['user']);
		$db_pass = str_replace(["\r", "\n"], "", $_POST['password']);
		$db_name = str_replace(["\r", "\n"], "", $_POST['name']);
		$db_prefix = str_replace(["\r", "\n"], "", $_POST['db_prefix']);

		if (!preg_match('/^[a-zA-Z0-9._:-]+$/', $db_host) ||
			!preg_match('/^[a-zA-Z0-9_-]+$/', $db_user) ||
			!preg_match('/^[a-zA-Z0-9_-]+$/', $db_name) ||
			!preg_match('/^[a-z_]*$/', $db_prefix)) {
			$error = lang('Error! Incorrect database configuration parameters.');
		}else{

			define("_DB_PREFIX_", $db_prefix);

			try{
					$db = new PDO('mysql:host='.$db_host.';dbname='.$db_name, $db_user, $db_pass, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]);
			}catch (PDOException $e){
				$error = true;
			}

			if (isset($error)) {
				$error = lang('Error! Unable to connect to the server.');
			}else{

					/* Modified: Save credentials to .env file instead of config/db.php */
					$dir = '../.env';
					$env_content = "DB_HOST=" . $db_host . "\n" .
					               "DB_USER=" . $db_user . "\n" .
					               "DB_PASS=" . $db_pass . "\n" .
					               "DB_NAME=" . $db_name . "\n" .
					               "DB_PREFIX=" . _DB_PREFIX_ . "\n";
					file_put_contents($dir, $env_content, LOCK_EX);
					chmod($dir, 0600);

				$sql = file_get_contents('antyki.sql');

				if(isset($_POST['sample_data'])){
					$sql .= file_get_contents('antyki_sample_data.sql');
				}

				$sql = str_replace("CREATE TABLE IF NOT EXISTS `","CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_,$sql);
				$sql = str_replace("CREATE TABLE `","CREATE TABLE `"._DB_PREFIX_,$sql);
				$sql = str_replace("INSERT INTO `","INSERT INTO `"._DB_PREFIX_,$sql);
				$sql = str_replace("ALTER TABLE `","ALTER TABLE `"._DB_PREFIX_,$sql);
				$sql = str_replace("REFERENCES `","REFERENCES `"._DB_PREFIX_,$sql);

				$db->exec($sql);

				include('../admin/php/admin.class.php');
				$admin = new \App\Admin\Admin();
				$password_admin = $admin->createPassword($_POST['password_admin']);

				$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin WHERE username=:username LIMIT 1');
				$sth->bindValue(':username', $_POST['admin'], PDO::PARAM_STR);
				$sth->execute();
				if($sth->fetchColumn()){
					$sth = $db->prepare('UPDATE '._DB_PREFIX_.'admin SET password=:password WHERE username=:username LIMIT 1');
					$sth->bindValue(':password', $password_admin, PDO::PARAM_STR);
					$sth->bindValue(':username', $_POST['admin'], PDO::PARAM_STR);
					$sth->execute();
				}else{
					$sth = $db->prepare('INSERT INTO '._DB_PREFIX_.'admin (`username`, `password`) VALUES (:username, :password)');
					$sth->bindValue(':username', $_POST['admin'], PDO::PARAM_STR);
					$sth->bindValue(':password', $password_admin, PDO::PARAM_STR);
					$sth->execute();
				}

				$sth = $db->prepare('UPDATE '._DB_PREFIX_.'settings SET value=:base_url WHERE name="base_url" LIMIT 1');
				$sth->bindValue(':base_url', webAddress($_POST['base_url']), PDO::PARAM_STR);
				$sth->execute();

				$template = 'default';
				if (!file_exists('../views/'.$template) ) {
					$dirs = array_filter(glob('../views/*'), 'is_dir');
					$template = substr($dirs[0],9);
				}

				$sth = $db->prepare('UPDATE '._DB_PREFIX_.'settings SET value=:template WHERE name="template" LIMIT 1');
				$sth->bindValue(':template', $template, PDO::PARAM_STR);
				$sth->execute();

				$sth = $db->prepare('UPDATE '._DB_PREFIX_.'settings SET value=:email WHERE name="email" LIMIT 1');
				$sth->bindValue(':email', $_POST['email'], PDO::PARAM_STR);
				$sth->execute();

				$sth = $db->prepare('UPDATE '._DB_PREFIX_.'settings SET value=:lang WHERE name="lang" LIMIT 1');
				$sth->bindValue(':lang', $settings['lang'], PDO::PARAM_STR);
				$sth->execute();

					/* Modified: Create install.lock file */
					file_put_contents('install.lock', date('Y-m-d H:i:s'));
					file_put_contents('../install.lock', date('Y-m-d H:i:s'));

				header('location: ../admin');
				die('redirect...');
			}
		}
	}
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="author" content="Ogłoszenia Nova CMS">
	<title><?= lang('The installer script') ?></title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
	<a href="#" title="Installer"><img src="../admin/images/admin.png" alt="Admin Panel" id="logo"/></a>
	<h4 class="text-center"><?= lang('Welcome to the installation program! Please fill in the fields below to pre-configure page.') ?></h4>
	<?php
		if(isset($error)){
			echo('<h3 class="text-danger text-center">'.$error.'</h3>');
		}
	?>
	<br>
	<form method="get" class="form-horizontal">
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Select language') ?>:</label>
			<div class="col-sm-7">
				<select class="form-control" name="lang" title="<?= lang('Select language') ?>" onchange="this.form.submit()">
					<?php
						foreach($langList as $key=>$lang){
							echo('<option value="'.$lang.'"');
							if($settings['lang']==$lang){
								echo(' selected ');
							}
							echo('>'.$lang.'</option>');
						}
					?>
				</select>
			</div>
		</div>
	</form>
	<br>
	<form method="post" class="form-horizontal">
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Base URL') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="base_url" placeholder="<?= lang('Base URL') ?>" value="<?php if(isset($_POST['base_url'])){echo($_POST['base_url']);}else{echo('http://'.$_SERVER['HTTP_HOST']);}?>" required title="<?= lang('Base URL') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('The database server') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="server" placeholder="<?= lang('The database server') ?>" value="<?php if(isset($_POST['server'])){echo($_POST['server']);}else{echo('localhost');}?>" required title="<?= lang('The database server') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('The database user name') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="user" placeholder="<?= lang('The database user name') ?>" value="<?php if(isset($_POST['user'])){echo($_POST['user']);}?>" required title="<?= lang('The database user name') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('The database name') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="name" placeholder="<?= lang('The database name') ?>" value="<?php if(isset($_POST['name'])){echo($_POST['name']);}?>" required title="<?= lang('The database name') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Password for database') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="password" name="password" placeholder="<?= lang('Password for database') ?>" value="<?php if(isset($_POST['password'])){echo($_POST['password']);}?>" title="<?= lang('Password for database') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Username to Admin Panel') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="admin" placeholder="<?= lang('Username to Admin Panel') ?>" value="<?php if(isset($_POST['admin'])){echo($_POST['admin']);}else{echo('administrator');}?>" required title="<?= lang('Username to Admin Panel') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Password to Admin Panel') ?>:</label>
			<div class="col-sm-7">
				<p class="text-danger hide" id="alert_password_diferrent"><?= lang('The passwords are different') ?></p>
				<input  class="form-control" type="password" name="password_admin" placeholder="<?= lang('Password to Admin Panel') ?>" value="<?php if(isset($_POST['password_admin'])){echo($_POST['password_admin']);}?>" required title="<?= lang('Password to Admin Panel') ?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Repeat password to Admin Panel') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="password" name="password_admin_repeat" placeholder="<?= lang('Repeat password to Admin Panel') ?>" required title="<?= lang('Repeat password to Admin Panel') ?>"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('E-mail Administrator') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="email" name="email" placeholder="<?= lang('E-mail Administrator') ?>" value="<?php if(isset($_POST['email'])){echo($_POST['email']);}?>" title="<?= lang('E-mail Administrator') ?>" required/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-5 control-label"><?= lang('Prefix tables in the database') ?>:</label>
			<div class="col-sm-7">
				<input class="form-control" type="text" name="db_prefix" placeholder="<?= lang('Prefix tables in the database') ?>" value="<?php if(isset($_POST['db_prefix'])){echo($_POST['db_prefix']);}?>" title="<?= lang('Prefix tables in the database') ?>" pattern="[a-z_]*"/>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-7 col-sm-offset-5">
				<div class="checkbox">
					<label><input type="checkbox" name="sample_data" <?php if(isset($_POST['sample_data'])){echo('checked');}?> /><?= lang('Install sample data (categories, states, etc.)') ?></label>
				</div>
			</div>
		</div>
		<div class="form-group text-center">
			<input class="btn btn-primary" type="submit" value="<?= lang('Save') ?>"/>
		</div>
	</form>
</div>
<footer class="container-fluid">
	<div class="row">
		<div class="col-md-12">
			<p class="text-center small">Admin v4.3 - Project © 2017 - 2018</p>
		</div>
	</div>
</footer>
</body>
</html>
