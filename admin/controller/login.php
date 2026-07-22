<?php
/************************************************************************

 * 
 * *********************************************************************
 * THIS SOFTWARE IS LICENSED - YOU CAN MODIFY THESE FILES
 * BUT YOU CAN NOT REMOVE OF ORIGINAL COMMENTS!
 * ACCORDING TO THE LICENSE YOU CAN USE THE SCRIPT ON ONE DOMAIN. DETECTION
 * COPY SCRIPT WILL RESULT IN A HIGH FINANSIAL PENALTY AND WITHDRAWAL
 * LICENSE THE SCRIPT
 * *********************************************************************/

if(!isset(\App\Core\App::settings()['base_url'])){
	die('Access denied! Settings: ' . var_export(\App\Core\App::settings() ?? 'NOT_SET', true));
}

/* ============================================================
 * [4] NAGŁÓWKI BEZPIECZEŃSTWA HTTP
 * Blokada clickjacking, sniffing, referrer leak
 * ============================================================ */
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-XSS-Protection: 1; mode=block');

/* ============================================================
 * KONFIGURACJA Z BAZY DANYCH (FALLBACK)
 * ============================================================ */
$SECURITY_ALERT_EMAIL    = \App\Core\App::settings()['security_alert_email'] ?? 'twoj@email.pl';
$SECURITY_ALERT_FROM     = \App\Core\App::settings()['security_alert_from'] ?? 'panel@twojadomena.pl';
$BLOCK_AFTER_ATTEMPTS    = isset(\App\Core\App::settings()['security_block_attempts']) ? (int)\App\Core\App::settings()['security_block_attempts'] : 10;
$BLOCK_DURATION_MINUTES  = isset(\App\Core\App::settings()['security_block_minutes']) ? (int)\App\Core\App::settings()['security_block_minutes'] : 30;
$CAPTCHA_AFTER_ATTEMPTS  = isset(\App\Core\App::settings()['security_captcha_attempts']) ? (int)\App\Core\App::settings()['security_captcha_attempts'] : 3;
$ALERT_EMAIL_AFTER       = isset(\App\Core\App::settings()['security_alert_after']) ? (int)\App\Core\App::settings()['security_alert_after'] : 3;
$SESSION_TIMEOUT_MINUTES = isset(\App\Core\App::settings()['security_session_timeout']) ? (int)\App\Core\App::settings()['security_session_timeout'] : 30;
$LOGIN_FAIL_DELAY_SEC    = isset(\App\Core\App::settings()['security_fail_delay']) ? (int)\App\Core\App::settings()['security_fail_delay'] : 1;

/* ============================================================
 * [9] FUNKCJA WYSYŁANIA ALERTU EMAIL
 * ============================================================ */
function sendSecurityAlert(string $type, string $ip, string $userAgent, string $username = ''): void {
	global $SECURITY_ALERT_EMAIL, $SECURITY_ALERT_FROM;
	$to      = $SECURITY_ALERT_EMAIL;
	$subject = '[PANEL ADMIN] Alert bezpieczeństwa: ' . $type;

	$body  = "=== ALERT BEZPIECZENSTWA PANELU ADMIN ===\n\n";
	$body .= "Typ zdarzenia : " . $type . "\n";
	$body .= "Data i czas   : " . date('Y-m-d H:i:s') . "\n";
	$body .= "Adres IP      : " . $ip . "\n";
	$body .= "User-Agent    : " . $userAgent . "\n";
	if (!empty($username)) {
		$body .= "Login (próba) : " . $username . "\n";
	}
	$body .= "\nJeśli to nie byłeś Ty — zablokuj IP natychmiast.\n";

	$headers  = "From: " . $SECURITY_ALERT_FROM . "\r\n";
	$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
	$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

	@mail($to, $subject, $body, $headers);
}

/* ============================================================
 * DANE KLIENTA
 * ============================================================ */
$clientIp        = getClientIp();
$clientUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 300) : 'unknown';

/* ============================================================
 * [1] BLOKADA IP — sprawdź czy IP jest zablokowane
 * ============================================================ */
$block_check_sth = $db->prepare(
	'SELECT COUNT(1) FROM `' . _DB_PREFIX_ . 'admin_logs`
	 WHERE logged=0
	   AND date > DATE_ADD(NOW(), INTERVAL -:block_minutes MINUTE)
	   AND ip=:ip'
);
$block_check_sth->bindValue(':block_minutes', $BLOCK_DURATION_MINUTES, PDO::PARAM_INT);
$block_check_sth->bindValue(':ip', $clientIp, PDO::PARAM_STR);
$block_check_sth->execute();
$total_recent_attempts = $block_check_sth->fetchColumn();

if($total_recent_attempts >= $BLOCK_AFTER_ATTEMPTS){
	// IP zablokowane — wyślij alert jeśli to dokładnie N próba (nie przy każdym żądaniu)
	if($total_recent_attempts == $BLOCK_AFTER_ATTEMPTS){
		sendSecurityAlert('BLOKADA IP — przekroczono limit prób logowania', $clientIp, $clientUserAgent);
	}
	die('<div style="font-family:sans-serif;text-align:center;padding:4rem;color:#ef4444;">
		<h2>&#128274; Dostęp tymczasowo zablokowany</h2>
		<p>Zbyt wiele nieudanych prób logowania z Twojego adresu IP.<br>
		Spróbuj ponownie za <strong>' . $BLOCK_DURATION_MINUTES . ' minut</strong>.</p>
	</div>');
}

/* ============================================================
 * LICZNIK NIEUDANYCH PRÓB (ostatnie 15 minut)
 * ============================================================ */
$failed_count_sth = $db->prepare(
	'SELECT COUNT(1) FROM `' . _DB_PREFIX_ . 'admin_logs`
	 WHERE logged=0
	   AND date > DATE_ADD(NOW(), INTERVAL -15 MINUTE)
	   AND ip=:ip'
);
$failed_count_sth->bindValue(':ip', $clientIp, PDO::PARAM_STR);
$failed_count_sth->execute();
$failed_attempts = $failed_count_sth->fetchColumn();

$render_variables['failed_attempts'] = $failed_attempts;

/* ============================================================
 * [6] LEPSZA CAPTCHA — trudniejsze działanie
 * Operacje: dodawanie, odejmowanie, mnożenie małych liczb
 * ============================================================ */
function generateCaptcha(): array {
	$operations = ['+', '-', '*'];
	$op = $operations[array_rand($operations)];

	if($op === '*'){
		$num1 = rand(2, 9);
		$num2 = rand(2, 9);
	} elseif($op === '-'){
		$num1 = rand(5, 20);
		$num2 = rand(1, $num1);
	} else {
		$num1 = rand(5, 25);
		$num2 = rand(5, 25);
	}

	$answer = match($op) {
		'+' => $num1 + $num2,
		'-' => $num1 - $num2,
		'*' => $num1 * $num2,
	};

	return [
		'challenge' => "$num1 $op $num2 = ?",
		'answer'    => $answer,
	];
}

if($failed_attempts >= $CAPTCHA_AFTER_ATTEMPTS){
	if(!isset($_SESSION['admin_captcha_challenge']) || empty($_SESSION['admin_captcha_challenge'])){
		$captcha = generateCaptcha();
		$_SESSION['admin_captcha_challenge'] = $captcha['challenge'];
		$_SESSION['admin_captcha_answer']    = $captcha['answer'];
	}
	$render_variables['captcha_challenge'] = $_SESSION['admin_captcha_challenge'];
} else {
	unset($_SESSION['admin_captcha_challenge']);
	unset($_SESSION['admin_captcha_answer']);
}

/* ============================================================
 * [3] TIMEOUT SESJI ADMINA — wyloguj po nieaktywności
 * ============================================================ */
if(isset($_SESSION['admin_last_activity'])){
	$inactive = time() - $_SESSION['admin_last_activity'];
	if($inactive > $SESSION_TIMEOUT_MINUTES * 60){
		session_unset();
		session_destroy();
		session_start();
		$render_variables['alert_danger'][] = 'Sesja wygasła z powodu braku aktywności. Zaloguj się ponownie.';
	}
}
if($admin->is_logged()){
	$_SESSION['admin_last_activity'] = time();
}

/* ============================================================
 * OBSŁUGA FORMULARZA LOGOWANIA
 * ============================================================ */
if (!isset($_POST['action']) || $_POST['action'] !== 'login') {
	unset($_SESSION['admin_2fa_pending']);
}

if(isset($_POST['action']) && $_POST['action'] == 'login' && !empty($_POST['session_code'])) {
	if (isset($_SESSION['admin_2fa_pending'])) {
		$_POST['username'] = $_SESSION['admin_2fa_pending']['username'];
		$_POST['password'] = $_SESSION['admin_2fa_pending']['password'];
	}

	if (!empty($_POST['username']) && !empty($_POST['password'])) {
		/* [7] HONEYPOT — bot wypełni to pole, człowiek nie */
		if(!empty($_POST['website'])){
			// Cicho odrzuć — nie zdradzamy że to pułapka
			sleep($LOGIN_FAIL_DELAY_SEC);
			$render_variables['alert_danger'][] = lang('Invalid username or password.');
			// Loguj próbę bota
			sendSecurityAlert('HONEYPOT — wykryto bota', $clientIp, $clientUserAgent, $_POST['username'] ?? '');
		} else {

			try {
				/* [6] Weryfikacja CAPTCHA */
				if($failed_attempts >= $CAPTCHA_AFTER_ATTEMPTS){
					if(!isset($_POST['captcha'])
						|| trim($_POST['captcha']) === ''
						|| intval($_POST['captcha']) !== intval($_SESSION['admin_captcha_answer'])
					){
						$captcha = generateCaptcha();
						$_SESSION['admin_captcha_challenge'] = $captcha['challenge'];
						$_SESSION['admin_captcha_answer']    = $captcha['answer'];
						$render_variables['captcha_challenge'] = $_SESSION['admin_captcha_challenge'];
						throw new Exception(lang('Invalid captcha code.'));
					}
				}

				$admin->login($_POST);

				// Wyczyść 2FA sesję po sukcesie
				unset($_SESSION['admin_2fa_pending']);

				/* [2] REGENERACJA ID SESJI po zalogowaniu — ochrona przed session fixation */
				session_regenerate_id(true);
				$_SESSION['admin_last_activity'] = time();
				$_SESSION['admin_ip']            = $clientIp;
				$_SESSION['admin_ua']            = $clientUserAgent;

				/* [5] Loguj szczegóły udanego logowania */
				error_log('[ADMIN LOGIN OK] IP=' . $clientIp . ' UA=' . $clientUserAgent . ' user=' . ($_POST['username'] ?? '') . ' time=' . date('Y-m-d H:i:s'));

				// Wyczyść CAPTCHA po sukcesie
				unset($_SESSION['admin_captcha_challenge']);
				unset($_SESSION['admin_captcha_answer']);

				header('Location: ' . $_SERVER['REQUEST_URI']);
				die('redirect');

			} catch(Exception $e) {
				$msg = $e->getMessage();

				if (str_starts_with($msg, '2FA_SETUP_REQUIRED:')) {
					$secret = substr($msg, 19);
					$_SESSION['admin_2fa_pending'] = [
						'username' => $_POST['username'],
						'password' => $_POST['password']
					];
					$render_variables['twofa_pending'] = true;
					$render_variables['twofa_setup'] = true;
					$render_variables['twofa_secret'] = $secret;
					$render_variables['twofa_qr_url'] = \App\Admin\TOTPHelper::getQRUrl($_POST['username'], $secret);
				} elseif ($msg === '2FA_CODE_REQUIRED') {
					$_SESSION['admin_2fa_pending'] = [
						'username' => $_POST['username'],
						'password' => $_POST['password']
					];
					$render_variables['twofa_pending'] = true;
				} else {
					/* [8] OPÓŹNIENIE — spowalnia brute-force */
					sleep($LOGIN_FAIL_DELAY_SEC);

					$render_variables['alert_danger'][] = $msg;

					// Przelicz próby po błędzie
					$failed_count_sth->execute();
					$failed_attempts = $failed_count_sth->fetchColumn();
					$render_variables['failed_attempts'] = $failed_attempts;

					/* [5] Loguj szczegóły nieudanej próby */
					error_log('[ADMIN LOGIN FAIL] IP=' . $clientIp . ' UA=' . $clientUserAgent . ' user=' . ($_POST['username'] ?? '') . ' attempts=' . $failed_attempts . ' time=' . date('Y-m-d H:i:s'));

					/* [9] ALERT EMAIL po przekroczeniu progu prób */
					if($failed_attempts == $ALERT_EMAIL_AFTER){
						sendSecurityAlert(
							'Podejrzane próby logowania (' . $failed_attempts . ' nieudanych)',
							$clientIp,
							$clientUserAgent,
							$_POST['username'] ?? ''
						);
					}

					/* [6] Nowa CAPTCHA po błędzie */
					if($failed_attempts >= $CAPTCHA_AFTER_ATTEMPTS
						&& (!isset($_SESSION['admin_captcha_challenge']) || empty($_SESSION['admin_captcha_challenge']))
					){
						$captcha = generateCaptcha();
						$_SESSION['admin_captcha_challenge'] = $captcha['challenge'];
						$_SESSION['admin_captcha_answer']    = $captcha['answer'];
						$render_variables['captcha_challenge'] = $_SESSION['admin_captcha_challenge'];
					}
				}
			}

		} // end honeypot check
	}
}

if(!$admin->is_logged()){
	$render_variables['session_code'] = $admin->newSessionCode();
}
