<?php

declare(strict_types=1);

namespace App\Admin;

use PDO;
use Exception;

/**
 * Admin authentication helper.
 * Modernized for application maintenance.
 */
class Admin {

	public array $user_data = [];

	public function __construct(private readonly PDO $db) {
		// Self-healing database updates for 2FA and Roles
		try {
			$this->db->query("SELECT `twofa_secret` FROM `" . _DB_PREFIX_ . "admin` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("ALTER TABLE `" . _DB_PREFIX_ . "admin` ADD COLUMN `twofa_secret` varchar(32) DEFAULT NULL");
			} catch (\Throwable $ex) {}
		}
		try {
			$this->db->query("SELECT `role` FROM `" . _DB_PREFIX_ . "admin` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("ALTER TABLE `" . _DB_PREFIX_ . "admin` ADD COLUMN `role` varchar(20) NOT NULL DEFAULT 'admin'");
				$this->db->exec("UPDATE `" . _DB_PREFIX_ . "admin` SET `role` = 'superadmin' WHERE id = (SELECT min_id FROM (SELECT MIN(id) as min_id FROM `" . _DB_PREFIX_ . "admin`) as tmp)");
			} catch (\Throwable $ex) {}
		}
		try {
			$this->db->query("SELECT `avatar` FROM `" . _DB_PREFIX_ . "admin` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("ALTER TABLE `" . _DB_PREFIX_ . "admin` ADD COLUMN `avatar` varchar(255) DEFAULT NULL");
			} catch (\Throwable $ex) {}
		}
		try {
			$this->db->query("SELECT `permissions` FROM `" . _DB_PREFIX_ . "admin` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("ALTER TABLE `" . _DB_PREFIX_ . "admin` ADD COLUMN `permissions` text DEFAULT NULL");
			} catch (\Throwable $ex) {}
		}
		try {
			$this->db->query("SELECT 1 FROM `" . _DB_PREFIX_ . "admin_activity_log` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "admin_activity_log` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`admin_username` varchar(64) NOT NULL,
					`action` varchar(128) NOT NULL,
					`details` text DEFAULT NULL,
					`ip` varchar(45) NOT NULL,
					`date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					KEY `admin_username` (`admin_username`),
					KEY `date` (`date`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
			} catch (\Throwable $ex) {}
		}

		try {
			$this->db->query("SELECT 1 FROM `" . _DB_PREFIX_ . "admin_notes` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "admin_notes` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`target_type` varchar(32) NOT NULL,
					`target_id` int(11) NOT NULL,
					`admin_username` varchar(64) NOT NULL,
					`note` text NOT NULL,
					`date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					KEY `target` (`target_type`, `target_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
			} catch (\Throwable $ex) {}
		}

		try {
			$sth2fa = $this->db->prepare("SELECT COUNT(1) FROM `" . _DB_PREFIX_ . "settings` WHERE name = 'security_2fa_enabled'");
			$sth2fa->execute();
			if ($sth2fa->fetchColumn() == 0) {
				$this->db->query("INSERT INTO `" . _DB_PREFIX_ . "settings` (name, value) VALUES ('security_2fa_enabled', '0')");
			}
		} catch (\Throwable $e) {}

		// Self-healing default admin user if database is empty
		try {
			$sthAdminCount = $this->db->query("SELECT COUNT(1) FROM `" . _DB_PREFIX_ . "admin`");
			if ($sthAdminCount && (int)$sthAdminCount->fetchColumn() === 0) {
				$defaultPassHash = createPasswordHash('admin');
				$this->db->exec("INSERT INTO `" . _DB_PREFIX_ . "admin` (`id`, `username`, `password`, `role`) VALUES (1, 'admin', " . $this->db->quote($defaultPassHash) . ", 'superadmin')");
			}

			// Ensure default moderator 'tomek' exists
			$sthTomek = $this->db->query("SELECT COUNT(1) FROM `" . _DB_PREFIX_ . "admin` WHERE username='tomek'");
			if ($sthTomek && (int)$sthTomek->fetchColumn() === 0) {
				$tomekPassHash = createPasswordHash('tomek123');
				$this->db->exec("INSERT INTO `" . _DB_PREFIX_ . "admin` (`username`, `password`, `role`) VALUES ('tomek', " . $this->db->quote($tomekPassHash) . ", 'admin')");
			}
		} catch (\Throwable $e) {}

		if (isset($_GET['log_out']) && !empty($_GET['token']) && checkToken('admin_logout', $_GET['token'])) {

			$this->logOut();
			header('Location: index.php');
			die('redirect');

		} elseif (isset($_SESSION['admin']['id']) && isset($_SESSION['admin']['session_code'])) {

			/* Modified: Session hijacking protection */
			if ((isset($_SESSION['admin']['ip']) && $_SESSION['admin']['ip'] !== getClientIp()) ||
				(isset($_SESSION['admin']['user_agent']) && $_SESSION['admin']['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
				$this->logOut();
			} else {
				$sth = $this->db->prepare('SELECT '._DB_PREFIX_.'admin.id, username, COALESCE('._DB_PREFIX_.'admin.role, "admin") as role, avatar, permissions FROM '._DB_PREFIX_.'admin_session, '._DB_PREFIX_.'admin WHERE user_id='._DB_PREFIX_.'admin.id AND '._DB_PREFIX_.'admin.id=:id AND code=:code LIMIT 1');
				$sth->bindValue(':id', $_SESSION['admin']['id'], PDO::PARAM_INT);
				$sth->bindValue(':code', $_SESSION['admin']['session_code'], PDO::PARAM_STR);
				$sth->execute();
				$user_data = $sth->fetch(PDO::FETCH_ASSOC);
				if ($user_data) {
					if ((int)$user_data['id'] === 1) {
						$user_data['role'] = 'superadmin';
					}
					$this->user_data = $user_data;
				} else {
					unset($_SESSION['admin']);
				}
			}
		}
	}

	public function __get(string $value): mixed {
		return $this->user_data[$value] ?? false;
	}

	public function login(array $data): void {
		$sth = $this->db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin_logs WHERE logged=0 AND date > DATE_ADD(NOW(), INTERVAL -15 MINUTE) AND (ip=:ip OR username=:username) LIMIT 5');
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->execute();
		if ($sth->rowCount() < 5) {

			$sth = $this->db->prepare('SELECT a.id, a.username, a.password, a.twofa_secret FROM '._DB_PREFIX_.'admin_session s JOIN '._DB_PREFIX_.'admin a ON a.username=:username WHERE s.code=:code LIMIT 1');
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
			$sth->execute();
			$user_data = $sth->fetch(PDO::FETCH_ASSOC);
			if ($user_data) {
				$storedPassword = $user_data['password'];

				if (verifyPasswordHash($data['password'], $storedPassword)) {
					// Check if 2FA is enabled globally
					$is_2fa_enabled = (\App\Core\App::settings()['security_2fa_enabled'] ?? '0') === '1';
					if ($is_2fa_enabled) {
						$twofa_secret = $user_data['twofa_secret'] ?? '';
						$code_submitted = $data['twofa_code'] ?? '';
						
						if (empty($twofa_secret)) {
							// Pairing mode: user needs to scan QR code
							if (!isset($_SESSION['admin_2fa_pending_secret'])) {
								$_SESSION['admin_2fa_pending_secret'] = TOTPHelper::generateSecret();
							}
							$temp_secret = $_SESSION['admin_2fa_pending_secret'];
							
							if (empty($code_submitted)) {
								throw new Exception('2FA_SETUP_REQUIRED:' . $temp_secret);
							}
							
							if (!TOTPHelper::verifyCode($temp_secret, $code_submitted)) {
								throw new Exception(lang('Invalid 2FA verification code.'));
							}
							
							// Save verified secret to database
							$sth_save = $this->db->prepare('UPDATE '._DB_PREFIX_.'admin SET twofa_secret=:secret WHERE id=:id');
							$sth_save->bindValue(':secret', $temp_secret, PDO::PARAM_STR);
							$sth_save->bindValue(':id', $user_data['id'], PDO::PARAM_INT);
							$sth_save->execute();
							
							unset($_SESSION['admin_2fa_pending_secret']);
						} else {
							// Verification mode
							if (empty($code_submitted)) {
								throw new Exception('2FA_CODE_REQUIRED');
							}
							if (!TOTPHelper::verifyCode($twofa_secret, $code_submitted)) {
								throw new Exception(lang('Invalid 2FA verification code.'));
							}
						}
					}

					regenerateSessionId();
					$_SESSION['admin']['id'] = $user_data['id'];
					$_SESSION['admin']['session_code'] = $data['session_code'];
					$_SESSION['admin']['ip'] = getClientIp();
					$_SESSION['admin']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

					$sth = $this->db->prepare('UPDATE `'._DB_PREFIX_.'admin_session` SET user_id=:id WHERE code=:code');
					$sth->bindValue(':id', $user_data['id'], PDO::PARAM_STR);
					$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
					$sth->execute();

					$this->saveLogs(true, $user_data['username']);
					return;
				}
			}

			$this->removeSessionCode($data['session_code']);
			$this->saveLogs(false, $data['username']);
			throw new Exception(lang('The entered data are incorrect'));
		} else {
			throw new Exception(lang('Exceeded the limit login attempts'));
		}
	}

	public function is_logged(): bool {
		if (!empty($this->user_data['id'])) {
			return true;
		}
		return false;
	}

	public function newSessionCode(): string {
		$this->logOut();
		$session_code = bin2hex(random_bytes(32));
		$sth = $this->db->prepare('INSERT INTO `'._DB_PREFIX_.'admin_session`(`user_id`, `code`, `ip`, `date`) VALUES (0,:code,:ip,NOW())');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();
		return $session_code;
	}

	public function removeSessionCode(string $session_code): void {
		$sth = $this->db->prepare('DELETE FROM `'._DB_PREFIX_.'admin_session` WHERE code=:code');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->execute();
	}

	public function logOut(): void {
		$this->user_data = [];
		unset($_SESSION['admin']);
	}

	public function createPassword(string $password): string {
		return createPasswordHash($password);
	}

	public function saveLogs(bool $logged = false, string $username = ''): void {
		$sth = $this->db->prepare('INSERT INTO `'._DB_PREFIX_.'admin_logs`(`username`, `logged`, `ip`, `date`) VALUES (:username, :logged, :ip, NOW())');
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':logged', $logged, PDO::PARAM_INT);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();
	}

	public function changeUser(array $data, array $files = []): void {
		if ($data['new_username'] != $this->user_data['username']) {
			$sth = $this->db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin WHERE username=:username AND id!=:id LIMIT 1');
			$sth->bindValue(':username', $data['new_username'], PDO::PARAM_STR);
			$sth->bindValue(':id', $this->user_data['id'], PDO::PARAM_INT);
			$sth->execute();
			if ($sth->fetchColumn()) {
				throw new Exception(lang('The selected username is already taken'));
			}
		}

		$updatePassword = false;
		if (!empty($data['new_password'])) {
			if ($data['new_password'] !== ($data['repeat_new_password'] ?? '')) {
				throw new Exception(lang('Entered passwords are different'));
			}
			$updatePassword = true;
		}

		$avatarPath = $this->user_data['avatar'] ?? null;
		$uploadDir = defined('_FOLDER_AVATARS_') ? _FOLDER_AVATARS_ : __DIR__ . '/../../upload/avatars/';

		if (!empty($data['remove_avatar'])) {
			if (!empty($avatarPath) && file_exists($uploadDir . $avatarPath)) {
				@unlink($uploadDir . $avatarPath);
			}
			$avatarPath = null;
		}

		if (isset($files['avatar']) && isset($files['avatar']['tmp_name']) && $files['avatar']['error'] === UPLOAD_ERR_OK && !empty($files['avatar']['tmp_name'])) {
			$ext = strtolower(pathinfo($files['avatar']['name'], PATHINFO_EXTENSION));
			$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
			if (in_array($ext, $allowedExts) && @getimagesize($files['avatar']['tmp_name'])) {
				if (!is_dir($uploadDir)) {
					@mkdir($uploadDir, 0777, true);
				}
				if (!empty($avatarPath) && file_exists($uploadDir . $avatarPath)) {
					@unlink($uploadDir . $avatarPath);
				}
				$newFileName = 'admin_avatar_' . $this->user_data['id'] . '_' . time() . '.' . $ext;
				if (move_uploaded_file($files['avatar']['tmp_name'], $uploadDir . $newFileName)) {
					$avatarPath = $newFileName;
				}
			} else {
				throw new Exception(lang('Dozwolone są tylko pliki graficzne (JPG, PNG, WEBP, GIF).'));
			}
		}

		if ($updatePassword) {
			$sth = $this->db->prepare('UPDATE '._DB_PREFIX_.'admin SET username=:new_username, password=:password, avatar=:avatar WHERE id=:id LIMIT 1');
			$sth->bindValue(':password', $this->createPassword($data['new_password']), PDO::PARAM_STR);
		} else {
			$sth = $this->db->prepare('UPDATE '._DB_PREFIX_.'admin SET username=:new_username, avatar=:avatar WHERE id=:id LIMIT 1');
		}

		$sth->bindValue(':new_username', $data['new_username'], PDO::PARAM_STR);
		$sth->bindValue(':avatar', $avatarPath, $avatarPath === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$sth->bindValue(':id', $this->user_data['id'], PDO::PARAM_INT);
		$sth->execute();

		$this->user_data['username'] = $data['new_username'];
		$this->user_data['avatar'] = $avatarPath;
	}

	public function editUser(array $data, array $files = []): void {
		if (!$this->isSuperAdmin()) {
			throw new Exception(lang('Tylko Główny Administrator może edytować konta administratorów/moderatorów'));
		}

		$id = (int)($data['id'] ?? 0);
		if ($id <= 0) {
			throw new Exception(lang('Nieprawidłowe ID użytkownika'));
		}

		$username = trim($data['username'] ?? '');
		if (empty($username)) {
			throw new Exception(lang('Nazwa użytkownika nie może być pusta'));
		}

		// Check if username is already taken by another admin
		$sth = $this->db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin WHERE username=:username AND id!=:id LIMIT 1');
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		if ($sth->fetchColumn()) {
			throw new Exception(lang('The selected username is already taken'));
		}

		$role = (!empty($data['role']) && in_array($data['role'], ['superadmin', 'admin'])) ? $data['role'] : 'admin';
		if ($id === 1) {
			$role = 'superadmin'; // ID 1 must always remain superadmin
		}

		$updatePassword = false;
		if (!empty($data['password'])) {
			if ($data['password'] !== ($data['repeat_password'] ?? '')) {
				throw new Exception(lang('Entered passwords are different'));
			}
			$updatePassword = true;
		}

		// Fetch existing user data for avatar handling
		$sthAvatar = $this->db->prepare('SELECT avatar FROM '._DB_PREFIX_.'admin WHERE id=:id LIMIT 1');
		$sthAvatar->bindValue(':id', $id, PDO::PARAM_INT);
		$sthAvatar->execute();
		$avatarPath = $sthAvatar->fetchColumn() ?: null;

		$uploadDir = defined('_FOLDER_AVATARS_') ? _FOLDER_AVATARS_ : __DIR__ . '/../../upload/avatars/';

		if (!empty($data['remove_avatar'])) {
			if (!empty($avatarPath) && file_exists($uploadDir . $avatarPath)) {
				@unlink($uploadDir . $avatarPath);
			}
			$avatarPath = null;
		}

		if (isset($files['avatar']) && isset($files['avatar']['tmp_name']) && $files['avatar']['error'] === UPLOAD_ERR_OK && !empty($files['avatar']['tmp_name'])) {
			$ext = strtolower(pathinfo($files['avatar']['name'], PATHINFO_EXTENSION));
			$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
			if (in_array($ext, $allowedExts) && @getimagesize($files['avatar']['tmp_name'])) {
				if (!is_dir($uploadDir)) {
					@mkdir($uploadDir, 0777, true);
				}
				if (!empty($avatarPath) && file_exists($uploadDir . $avatarPath)) {
					@unlink($uploadDir . $avatarPath);
				}
				$newFileName = 'admin_avatar_' . $id . '_' . time() . '.' . $ext;
				if (move_uploaded_file($files['avatar']['tmp_name'], $uploadDir . $newFileName)) {
					$avatarPath = $newFileName;
				}
			} else {
				throw new Exception(lang('Dozwolone są tylko pliki graficzne (JPG, PNG, WEBP, GIF).'));
			}
		}

		$permissionsJson = null;
		if (isset($data['permissions']) && is_array($data['permissions'])) {
			$validPerms = array_keys(self::getAvailablePermissions());
			$selectedPerms = array_values(array_intersect($data['permissions'], $validPerms));
			$permissionsJson = json_encode($selectedPerms);
		}

		if ($updatePassword) {
			$sth = $this->db->prepare('UPDATE '._DB_PREFIX_.'admin SET username=:username, password=:password, role=:role, avatar=:avatar, permissions=:permissions WHERE id=:id LIMIT 1');
			$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
		} else {
			$sth = $this->db->prepare('UPDATE '._DB_PREFIX_.'admin SET username=:username, role=:role, avatar=:avatar, permissions=:permissions WHERE id=:id LIMIT 1');
		}

		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':role', $role, PDO::PARAM_STR);
		$sth->bindValue(':avatar', $avatarPath, $avatarPath === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$sth->bindValue(':permissions', $permissionsJson, $permissionsJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();

		if ($id === $this->user_data['id']) {
			$this->user_data['username'] = $username;
			$this->user_data['role'] = $role;
			$this->user_data['avatar'] = $avatarPath;
			$this->user_data['permissions'] = $permissionsJson;
		}
	}

	public function removeLogs(): void {
		$this->db->query('DELETE FROM '._DB_PREFIX_.'admin_logs');
	}

	/**
     * @return mixed[]
     */
	public function getLogs(): array {
		$limit = 100;
		$admin_logs = [];
		$totalLogs = (int)$this->db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'admin_logs')->fetchColumn();
		$sth = $this->db->query('SELECT * FROM '._DB_PREFIX_.'admin_logs ORDER BY '.sortBy().' LIMIT '.paginationPageFrom($limit).','.$limit.'');
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { $admin_logs[] = $row; }
		generatePagination($limit, $totalLogs);
		return $admin_logs;
	}

	public function isSuperAdmin(): bool {
		return ($this->user_data['role'] ?? '') === 'superadmin' || ((int)($this->user_data['id'] ?? 0) === 1);
	}

	public static function getAvailablePermissions(): array {
		return [
			'categories' => 'Kategorie i podkategorie',
			'offers' => 'Ogłoszenia',
			'articles' => 'Artykuły i Treści (Index, Login, Info, Mails)',
			'payments' => 'Finanse i Płatności (Logs payments)',
			'users' => 'Zarządzanie użytkownikami portalu',
			'communication' => 'Komunikacja (Mailing, Sugestie, Opinie, Czat)',
			'additional_data' => 'Słowniki (Województwa, Typy, Opcje)',
			'logs_and_security' => 'Logi systemowe i Bezpieczeństwo',
			'settings' => 'Ustawienia serwisu (Czarna lista, Dni, Wygląd, Reklamy, Social Media)'
		];
	}

	public function hasPermission(string $perm): bool {
		if ($this->isSuperAdmin()) {
			return true;
		}
		$raw = $this->user_data['permissions'] ?? '';
		if (empty($raw)) {
			// Domyślny zestaw uprawnień dla moderatora (kategorie, ogłoszenia, artykuły, płatności, użytkownicy)
			return in_array($perm, ['categories', 'offers', 'articles', 'payments', 'users']);
		}
		$perms = json_decode($raw, true);
		return is_array($perms) && in_array($perm, $perms);
	}

	/**
     * @return mixed[]
     */
	public function getUsers(): array {
		$admin = [];
		$sth = $this->db->query('SELECT a.id, a.username, COALESCE(a.role, "admin") as role, a.avatar, a.permissions, 
			(SELECT s.date FROM '._DB_PREFIX_.'admin_session s WHERE s.user_id = a.id ORDER BY s.date DESC LIMIT 1) as session_date,
			(SELECT MAX(l.date) FROM '._DB_PREFIX_.'admin_logs l WHERE l.username = a.username AND l.logged = 1) as last_login
			FROM '._DB_PREFIX_.'admin a 
			ORDER BY a.id ASC');
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			if ((int)$row['id'] === 1 && $row['role'] !== 'superadmin') {
				$row['role'] = 'superadmin';
			}
			$row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : ['categories', 'offers', 'articles', 'payments', 'users'];
			$admin[] = $row;
		}
		return $admin;
	}

	public function addUser(array $data): void {
		if (!$this->isSuperAdmin()) {
			throw new Exception(lang('Tylko Główny Administrator może dodawać nowe konta administratorów'));
		}
		if ($data['password'] == $data['repeat_password']) {
			$sth = $this->db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin WHERE username=:username LIMIT 1');
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->execute();
			if (!$sth->fetchColumn()) {
				$role = (!empty($data['role']) && in_array($data['role'], ['superadmin', 'admin'])) ? $data['role'] : 'admin';
				$permissionsJson = null;
				if (isset($data['permissions']) && is_array($data['permissions'])) {
					$validPerms = array_keys(self::getAvailablePermissions());
					$selectedPerms = array_values(array_intersect($data['permissions'], $validPerms));
					$permissionsJson = json_encode($selectedPerms);
				}
				$sth = $this->db->prepare('INSERT INTO '._DB_PREFIX_.'admin (username, password, role, permissions) VALUES(:username, :password, :role, :permissions)');
				$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
				$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
				$sth->bindValue(':role', $role, PDO::PARAM_STR);
				$sth->bindValue(':permissions', $permissionsJson, $permissionsJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$sth->execute();
			} else {
				throw new Exception(lang('The selected username is already taken'));
			}
		} else {
			throw new Exception(lang('Entered passwords are different'));
		}
	}

	public function removeUser(int $id): void {
		if (!$this->isSuperAdmin()) {
			throw new Exception(lang('Tylko Główny Administrator może usuwać konta administratorów'));
		}
		if ($id == $this->user_data['id']) {
			throw new Exception(lang('You can not delete a user who is logged'));
		}

		$sthTarget = $this->db->prepare('SELECT role FROM '._DB_PREFIX_.'admin WHERE id=:id LIMIT 1');
		$sthTarget->bindValue(':id', $id, PDO::PARAM_INT);
		$sthTarget->execute();
		$targetRole = $sthTarget->fetchColumn();

		if ($targetRole === 'superadmin' || $id === 1) {
			throw new Exception(lang('Nie można usunąć Głównego Administratora!'));
		}

		$sth = $this->db->prepare('DELETE FROM '._DB_PREFIX_.'admin WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public function logOutAll(): never {
		$this->db->query('DELETE FROM '._DB_PREFIX_.'admin_session');
		header('Location: index.php');
		die('redirect');
	}

	public function getDashboardStats(int $days = 14): array {
		$days = max(1, $days);
		
		$sthOffers = $this->db->prepare('
			SELECT DATE(date) as stat_date, COUNT(1) as count_offers 
			FROM '._DB_PREFIX_.'offer 
			WHERE date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
			GROUP BY DATE(date)
			ORDER BY stat_date ASC
		');
		$sthOffers->bindValue(':days', $days, PDO::PARAM_INT);
		$sthOffers->execute();
		$offersRaw = $sthOffers->fetchAll(PDO::FETCH_ASSOC);

		$sthUsers = $this->db->prepare('
			SELECT DATE(date) as stat_date, COUNT(1) as count_users 
			FROM '._DB_PREFIX_.'user 
			WHERE date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
			GROUP BY DATE(date)
			ORDER BY stat_date ASC
		');
		$sthUsers->bindValue(':days', $days, PDO::PARAM_INT);
		$sthUsers->execute();
		$usersRaw = $sthUsers->fetchAll(PDO::FETCH_ASSOC);

		$stats = [];
		for ($i = $days - 1; $i >= 0; $i--) {
			$date = date('Y-m-d', strtotime("-$i days"));
			$stats[$date] = [
				'offers' => 0,
				'users' => 0
			];
		}

		foreach ($offersRaw as $row) {
			if (isset($stats[$row['stat_date']])) {
				$stats[$row['stat_date']]['offers'] = (int)$row['count_offers'];
			}
		}

		foreach ($usersRaw as $row) {
			if (isset($stats[$row['stat_date']])) {
				$stats[$row['stat_date']]['users'] = (int)$row['count_users'];
			}
		}

		return [
			'labels' => array_keys($stats),
			'offers' => array_column($stats, 'offers'),
			'users' => array_column($stats, 'users'),
		];
	}

	public function logActivity(string $action, string $details = ''): void {
		try {
			$adminUsername = $this->user_data['username'] ?? 'system';
			$sth = $this->db->prepare("INSERT INTO `" . _DB_PREFIX_ . "admin_activity_log` (`admin_username`, `action`, `details`, `ip`, `date`) VALUES (:username, :action, :details, :ip, NOW())");
			$sth->bindValue(':username', $adminUsername, PDO::PARAM_STR);
			$sth->bindValue(':action', $action, PDO::PARAM_STR);
			$sth->bindValue(':details', $details, PDO::PARAM_STR);
			$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
			$sth->execute();
		} catch (\Throwable $e) {}
	}

	public function getActivityLogs(int $limit = 100): array {
		$logs = [];
		try {
			$total = (int)$this->db->query("SELECT COUNT(1) FROM `" . _DB_PREFIX_ . "admin_activity_log`")->fetchColumn();
			$sth = $this->db->query("SELECT * FROM `" . _DB_PREFIX_ . "admin_activity_log` ORDER BY id DESC LIMIT " . paginationPageFrom($limit) . ", " . $limit);
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
				$logs[] = $row;
			}
			generatePagination($limit, $total);
		} catch (\Throwable $e) {}
		return $logs;
	}

	public function addNote(string $targetType, int $targetId, string $note): void {
		if (empty($note) || $targetId <= 0) {
			return;
		}
		try {
			$adminUsername = $this->user_data['username'] ?? 'system';
			$sth = $this->db->prepare("INSERT INTO `" . _DB_PREFIX_ . "admin_notes` (`target_type`, `target_id`, `admin_username`, `note`, `date`) VALUES (:type, :id, :username, :note, NOW())");
			$sth->bindValue(':type', $targetType, PDO::PARAM_STR);
			$sth->bindValue(':id', $targetId, PDO::PARAM_INT);
			$sth->bindValue(':username', $adminUsername, PDO::PARAM_STR);
			$sth->bindValue(':note', $note, PDO::PARAM_STR);
			$sth->execute();
			$this->logActivity('Dodano notatkę', "Target: $targetType #$targetId");
		} catch (\Throwable $e) {}
	}

	public function getNotes(string $targetType, int $targetId): array {
		$notes = [];
		try {
			$sth = $this->db->prepare("SELECT * FROM `" . _DB_PREFIX_ . "admin_notes` WHERE target_type=:type AND target_id=:id ORDER BY id DESC");
			$sth->bindValue(':type', $targetType, PDO::PARAM_STR);
			$sth->bindValue(':id', $targetId, PDO::PARAM_INT);
			$sth->execute();
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
				$notes[] = $row;
			}
		} catch (\Throwable $e) {}
		return $notes;
	}
}
