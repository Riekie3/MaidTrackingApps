<?php

class Client
{
    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO clients (name, email, phone, password_hash, address, consent_given_at)
             VALUES (:name, :email, :phone, :password_hash, :address, NOW())'
        );
        $stmt->execute($data);
        return (int) getDB()->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM clients WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM clients WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function markVerified(int $id): void
    {
        $stmt = getDB()->prepare(
            "UPDATE clients SET status = 'verified', email_verified_at = NOW(), phone_verified_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
