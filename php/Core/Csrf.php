<?php
declare(strict_types=1);

namespace App\Core;

class Csrf {
    /**
     * Get the CSRF token from the session, generate one if it doesn't exist.
     * 
     * @return string
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the provided token matches the one stored in the session.
     * 
     * @param string|null $token
     * @return bool
     */
    public static function verify(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
