<?php

// Real email/SMS delivery is out of scope for local dev — this issues a
// 6-digit code for both channels at once and hands the plaintext back to
// the caller to *display* on the verify screen (clearly labeled as a dev
// shortcut). Swapping in a real mailer/SMS gateway later only means
// replacing what the caller does with the returned code, not this class.

class ClientOtp
{
    public static function issue(int $clientId): string
    {
        $code = (string) random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60);

        $stmt = getDB()->prepare(
            'INSERT INTO client_otps (client_id, channel, code_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$clientId, 'email', $hash, $expiresAt]);
        $stmt->execute([$clientId, 'phone', $hash, $expiresAt]);

        return $code;
    }

    public static function verify(int $clientId, string $code): bool
    {
        $stmt = getDB()->prepare(
            'SELECT * FROM client_otps WHERE client_id = ? AND consumed_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([$clientId]);
        $rows = $stmt->fetchAll();

        $matched = false;
        foreach ($rows as $row) {
            if (password_verify($code, $row['code_hash'])) {
                $matched = true;
                break;
            }
        }

        if ($matched) {
            $consume = getDB()->prepare(
                'UPDATE client_otps SET consumed_at = NOW() WHERE client_id = ? AND consumed_at IS NULL'
            );
            $consume->execute([$clientId]);
        }

        return $matched;
    }
}
