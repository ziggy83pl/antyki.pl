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
		// Self-healing database updates for 2FA
		try {
			$this->db->query("SELECT `twofa_secret` FROM `" . _DB_PREFIX_ . "admin` LIMIT 1");
		} catch (\Throwable $e) {
			try {
				$this->db->exec("ALTER TABLE `" . _DB_PREFIX_ . "admin` ADD COLUMN `twofa_secret` varchar(32) DEFAULT NULL");
			} catch (\Throwable $ex) {}
		}
		try {
			$sth2fa = $this->db->prepare("SELECT COUNT(1) FROM `" . _DB_PREFIX_ . "settings` WHERE name = 'security_2fa_enabled'");
			$sth2fa->execute();
			if ($sth2fa->fetchColumn() == 0) {
				$this->db->query("INSERT INTO `" . _DB_PREFIX_ . "settings` (name, value) VALUES ('security_2fa_enabled', '0')");
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
				$sth = $this->db->prepare('SELECT '._DB_PREFIX_.'admin.id, username FROM '._DB_PREFIX_.'admin_session, '._DB_PREFIX_.'admin WHERE user_id='._DB_PREFIX_.'admin.id AND '._DB_PREFIX_.'admin.id=:id AND code=:code LIMIT 1');
				$sth->bindValue(':id', $_SESSION['admin']['id'], PDO::PARAM_INT);
				$sth->bindValue(':code', $_SESSION['admin']['session_code'], PDO::PARAM_STR);
				$sth->execute();
				$user_data = $sth->fetch(PDO::FETCH_ASSOC);
				if ($user_data) {
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

	public function changeUser(array $data): void {
		if ($data['new_password'] == $data['repeat_new_password']) {
			if ($data['new_username'] != $this->user_data['username']) {
				$sth = $this->db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin WHERE username=:username AND id!=:id LIMIT 1');
				$sth->bindValue(':username', $data['new_username'], PDO::PARAM_STR);
				$sth->bindValue(':id', $this->user_data['id'], PDO::PARAM_INT);
				$sth->execute();
				if ($sth->fetchColumn()) {
					throw new Exception(lang('The selected username is already taken'));
				}
			}

			$sth = $this->db->prepare('UPDATE '._DB_PREFIX_.'admin SET username=:new_username, password=:password WHERE id=:id LIMIT 1');
			$sth->bindValue(':new_username', $data['new_username'], PDO::PARAM_STR);
			$sth->bindValue(':password', $this->createPassword($data['new_password']), PDO::PARAM_STR);
			$sth->bindValue(':id', $this->user_data['id'], PDO::PARAM_INT);
			$sth->execute();

			$this->user_data['username'] = $data['new_username'];
		} else {
			throw new Exception(lang('Entered passwords are different'));
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

	/**
     * @return mixed[]
     */
	public function getUsers(): array {
		$admin = [];
		$sth = $this->db->query('SELECT a.id, a.username, 
			(SELECT s.date FROM '._DB_PREFIX_.'admin_session s WHERE s.user_id = a.id ORDER BY s.date DESC LIMIT 1) as session_date,
			(SELECT MAX(l.date) FROM '._DB_PREFIX_.'admin_logs l WHERE l.username = a.username AND l.logged = 1) as last_login
			FROM '._DB_PREFIX_.'admin a 
			ORDER BY a.username');
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { $admin[] = $row; }
		return $admin;
	}

	public function addUser(array $data): void {
		if ($data['password'] == $data['repeat_password']) {
			$sth = $this->db->prepare('SELECT 1 FROM '._DB_PREFIX_.'admin WHERE username=:username LIMIT 1');
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->execute();
			if (!$sth->fetchColumn()) {
				$sth = $this->db->prepare('INSERT INTO '._DB_PREFIX_.'admin (username, password) VALUES(:username, :password)');
				$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
				$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
				$sth->execute();
			} else {
				throw new Exception(lang('The selected username is already taken'));
			}
		} else {
			throw new Exception(lang('Entered passwords are different'));
		}
	}

	public function removeUser(int $id): void {
		if ($id != $this->user_data['id']) {
			$sth = $this->db->prepare('DELETE FROM '._DB_PREFIX_.'admin WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
		} else {
			throw new Exception(lang('You can not delete a user who is logged'));
		}
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
}
