<?php

require_once('../config/config.php');

define("P24_VERSION", "v1");

// Check IP whitelist function for callbacks
function isPrzelewy24Ip($ip): bool {
	$ips = [
		'91.216.191.181', '91.216.191.182', '91.216.191.183', '91.216.191.184', '91.216.191.185',
		'5.252.202.254', '5.252.202.255', '20.215.81.124'
	];
	if (in_array($ip, $ips)) {
		return true;
	}
	
	// Check CIDR ranges
	$cidrs = [
		'193.178.213.0/24',
		'91.220.177.0/24',
		'20.215.183.48/28',
		'134.112.88.8/29'
	];
	
	foreach ($cidrs as $cidr) {
		[$subnet, $bits] = explode('/', $cidr);
		$ip_long = ip2long($ip);
		$subnet_long = ip2long($subnet);
		$mask = ~((1 << (32 - (int)$bits)) - 1);
		
		if (($ip_long & $mask) == ($subnet_long & $mask)) {
			return true;
		}
	}
	
	return false;
}

if (isset($_POST['action']) && $_POST['action'] == 'new_payment' && isset($_POST['item_id']) && $_POST['item_id'] > 0 && !empty($_POST['type'])) {
	
	$payment_data = new_payment('p24', $_POST['item_id'], $_POST['type']);
	if ($payment_data) {
		
		// Insert into payment_p24
		$sth = $db->prepare('INSERT INTO '._DB_PREFIX_.'payment_p24 (payment_id, status, amount, sandbox, date) VALUES (:payment_id, "new", :amount, :sandbox, NOW())');
		$sth->bindValue(':payment_id', $payment_data['id'], PDO::PARAM_INT);
		$sth->bindValue(':amount', $payment_data['amount'], PDO::PARAM_STR);
		$sth->bindValue(':sandbox', $settings['p24_sandbox'], PDO::PARAM_STR);
		$sth->execute();
		
		// 1. Prepare signature
		$amount_in_grosze = (int)round($payment_data['amount'] * 100);
		$signData = [
			"sessionId" => (string)$payment_data['id'],
			"merchantId" => (int)$settings['p24_merchant_id'],
			"amount" => $amount_in_grosze,
			"currency" => "PLN",
			"crc" => $settings['p24_crc']
		];
		$sign = hash('sha384', json_encode($signData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		
		// 2. Prepare registration payload
		$registerData = [
			"merchantId" => (int)$settings['p24_merchant_id'],
			"posId" => (int)$settings['p24_pos_id'],
			"sessionId" => (string)$payment_data['id'],
			"amount" => $amount_in_grosze,
			"currency" => "PLN",
			"description" => $payment_data['description'],
			"email" => $payment_data['email'],
			"country" => "PL",
			"language" => "pl",
			"urlReturn" => $payment_data['url'] . '&status=OK',
			"urlStatus" => $settings['base_url'] . '/php/payment_p24.php',
			"sign" => $sign
		];
		
		$registerPayload = json_encode($registerData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		// Determine endpoints
		$host = $settings['p24_sandbox'] ? 'https://sandbox.przelewy24.pl' : 'https://secure.przelewy24.pl';
		$url = $host . '/api/v1/transaction/register';
		
		// cURL POST
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $registerPayload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($registerPayload)
		]);
		curl_setopt($ch, CURLOPT_USERPWD, $settings['p24_pos_id'] . ":" . $settings['p24_api_key']);
		
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$response = json_decode($result, true);
		
		if ($httpCode == 200 && isset($response['data']['token'])) {
			$token = $response['data']['token'];
			$redirectUrl = $host . '/trnRequest/' . $token;
			header("Location: " . $redirectUrl);
			exit;
		} else {
			$errorMsg = $response['description'] ?? 'HTTP Code ' . $httpCode;
			if (isset($response['code'])) {
				$errorMsg .= ' (Code: ' . $response['code'] . ')';
			}
			
			// Save error message to DB
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment_p24 SET errors=:errors, status="failed" WHERE payment_id=:payment_id LIMIT 1');
			$sth->bindValue(':errors', $errorMsg, PDO::PARAM_STR);
			$sth->bindValue(':payment_id', $payment_data['id'], PDO::PARAM_INT);
			$sth->execute();
			
			die('Przelewy24 transaction registration failed: ' . htmlspecialchars((string) $errorMsg));
		}
	}
	
} elseif (isPrzelewy24Ip($_SERVER['REMOTE_ADDR']) && !empty($_POST)) { 

	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'payment_p24 WHERE payment_id=:payment_id AND status="new" LIMIT 1');
	$sth->bindValue(':payment_id', $_POST['p24_session_id'], PDO::PARAM_STR);
	$sth->execute();
	$payment_p24 = $sth->fetch(PDO::FETCH_ASSOC);

	if ($payment_p24) {
		
		$amount_in_grosze = (int)round($payment_p24['amount'] * 100);
		
		// 1. Calculate verification signature
		$verifySignData = [
			"sessionId" => $_POST['p24_session_id'],
			"orderId" => (int)$_POST['p24_order_id'],
			"amount" => $amount_in_grosze,
			"currency" => "PLN",
			"crc" => $settings['p24_crc']
		];
		$verifySign = hash('sha384', json_encode($verifySignData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		
		// 2. Prepare payload
		$verifyData = [
			"merchantId" => (int)$settings['p24_merchant_id'],
			"posId" => (int)$settings['p24_pos_id'],
			"sessionId" => $_POST['p24_session_id'],
			"amount" => $amount_in_grosze,
			"currency" => "PLN",
			"orderId" => (int)$_POST['p24_order_id'],
			"sign" => $verifySign
		];
		
		$verifyPayload = json_encode($verifyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		$host = $settings['p24_sandbox'] ? 'https://sandbox.przelewy24.pl' : 'https://secure.przelewy24.pl';
		$url = $host . '/api/v1/transaction/verify';
		
		// cURL PUT
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $verifyPayload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($verifyPayload)
		]);
		curl_setopt($ch, CURLOPT_USERPWD, $settings['p24_pos_id'] . ":" . $settings['p24_api_key']);
		
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$response = json_decode($result, true);

		if ($httpCode == 200 && isset($response['responseCode']) && $response['responseCode'] === 0) {
	
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment_p24 SET p24_order_id=:p24_order_id, status="completed" WHERE id=:id LIMIT 1');
			$sth->bindValue(':p24_order_id', $_POST['p24_order_id'], PDO::PARAM_STR);
			$sth->bindValue(':id', $payment_p24['id'], PDO::PARAM_INT);
			$sth->execute();

			check_payment($payment_p24['payment_id'], ($_POST['p24_amount'] / 100));
			echo "OK";
		
		} else {
			$errorMsg = $response['description'] ?? 'HTTP Code ' . $httpCode;
			if (isset($response['code'])) {
				$errorMsg .= ' (Code: ' . $response['code'] . ')';
			}

			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment_p24 SET errors=:error, status="failed" WHERE id=:id LIMIT 1');
			$sth->bindValue(':error', $errorMsg, PDO::PARAM_STR);
			$sth->bindValue(':id', $payment_p24['id'], PDO::PARAM_INT);
			$sth->execute();
			
			header("HTTP/1.1 400 Bad Request");
			echo "Verification failed: " . $errorMsg;
		}
	} else {
		header("HTTP/1.1 404 Not Found");
		echo "Payment session not found";
	}
} else {
	header("HTTP/1.1 403 Forbidden");
	echo "Access denied";
}
