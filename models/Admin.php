<?php

class Admin
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM admins WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
}
