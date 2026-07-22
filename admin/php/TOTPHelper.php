<?php

declare(strict_types=1);

namespace App\Admin;

use Exception;

/**
 * Self-contained helper for TOTP (Time-Based One-Time Password) RFC 6238.
 * Decodes Base32, generates secrets, and verifies codes.
 */
class TOTPHelper {

    /**
     * Generates a random 16-character Base32 secret.
     */
    public static function generateSecret(int $length = 16): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Decodes a Base32 string into binary data.
     */
    public static function base32Decode(string $base32): string {
        $base32 = strtoupper($base32);
        if (!preg_match('/^[A-Z2-7=]+$/', $base32)) {
            throw new Exception('Invalid base32 string');
        }
        $base32 = str_replace('=', '', $base32);
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($base32) as $char) {
            $val = strpos($chars, $char);
            if ($val === false) {
                throw new Exception('Invalid character in base32 string');
            }
            $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int)bindec($byte));
            }
        }
        return $bytes;
    }

    /**
     * Verifies a 6-digit TOTP code against a secret.
     * Allows for a discrepancy of $discrepancy (default 1 = +/- 30 seconds).
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool {
        $code = str_replace(' ', '', $code);
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        try {
            $secretKey = self::base32Decode($secret);
        } catch (Exception $e) {
            return false;
        }

        $timeSlice = (int)floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $slice = $timeSlice + $i;
            // Pack time into 8-byte binary string
            $time = pack('N*', 0) . pack('N*', $slice);
            
            $hmac = hash_hmac('sha1', $time, $secretKey, true);
            $offset = ord(substr($hmac, -1)) & 0x0F;
            $hashpart = substr($hmac, $offset, 4);
            $value = unpack('N', $hashpart)[1] & 0x7FFFFFFF;
            
            $calculatedCode = str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
            
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates a QR code URL for Google Authenticator.
     */
    public static function getQRUrl(string $user, string $secret, string $issuer = 'GieldaBudowlana'): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode("otpauth://totp/{$issuer}:{$user}?secret={$secret}&issuer={$issuer}");
    }
}
