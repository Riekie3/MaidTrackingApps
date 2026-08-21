<?php

// Mirrors ClientOtp exactly — see that class for the dev-mode-delivery
// rationale. A freelancer verifies her own email+phone the same way a
// client does, on top of (not instead of) Admin's document review.

class FreelancerOtp
{
    public static function issue(int $freelancerId): string
    {
        $code = (string) random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60);

        $stmt = getDB()->prepare(
            'INSERT INTO freelancer_otps (freelancer_id, channel, code_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$freelancerId, 'email', $hash, $expiresAt]);
        $stmt->execute([$freelancerId, 'phone', $hash, $expiresAt]);

        return $code;
    }

    public static function verify(int $freelancerId, string $code): bool
    {
        $stmt = getDB()->prepare(
            'SELECT * FROM freelancer_otps WHERE freelancer_id = ? AND consumed_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([$freelancerId]);
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
                'UPDATE freelancer_otps SET consumed_at = NOW() WHERE freelancer_id = ? AND consumed_at IS NULL'
            );
            $consume->execute([$freelancerId]);
        }

        return $matched;
    }
}
