<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class RateLimiter {
    /**
     * Check if the rate limit is exceeded for a given IP and action.
     * Increment attempts if not exceeded.
     *
     * @param string $ip
     * @param string $action
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return bool
     */
    public static function check(string $ip, string $action, int $maxAttempts = 5, int $decaySeconds = 60): bool {
        $db = \App\Core\App::db();
        $now = time();
        $cutoff = $now - $decaySeconds;

        // Clean up old entries to keep the table small
        try {
            $sth = $db->prepare('DELETE FROM `' . _DB_PREFIX_ . 'rate_limit` WHERE created_at < :cutoff');
            $sth->bindValue(':cutoff', $cutoff, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Throwable $e) {
            // Silence if table is not created yet
        }

        // Count attempts in the decay window
        try {
            $sth = $db->prepare('SELECT COUNT(1) FROM `' . _DB_PREFIX_ . 'rate_limit` WHERE ip = :ip AND action = :action AND created_at >= :cutoff');
            $sth->bindValue(':ip', $ip, PDO::PARAM_STR);
            $sth->bindValue(':action', $action, PDO::PARAM_STR);
            $sth->bindValue(':cutoff', $cutoff, PDO::PARAM_INT);
            $sth->execute();
            $attempts = (int)$sth->fetchColumn();

            if ($attempts >= $maxAttempts) {
                return false;
            }

            // Log this attempt
            $sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'rate_limit` (ip, action, created_at) VALUES (:ip, :action, :created_at)');
            $sth->bindValue(':ip', $ip, PDO::PARAM_STR);
            $sth->bindValue(':action', $action, PDO::PARAM_STR);
            $sth->bindValue(':created_at', $now, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Throwable $e) {
            error_log("RateLimiter error: " . $e->getMessage());
            return true; // Fallback to allow requests in case of database issue
        }

        return true;
    }
}
