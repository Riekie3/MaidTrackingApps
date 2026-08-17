<?php

class Agency
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM agencies WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM agencies WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO agencies (company_name, registration_number, license_document_path, contact_person, email, phone, password_hash, address, bio)
             VALUES (:company_name, :registration_number, :license_document_path, :contact_person, :email, :phone, :password_hash, :address, :bio)'
        );
        $stmt->execute($data);
        return (int) getDB()->lastInsertId();
    }

    public static function listByStatus(string $status): array
    {
        $stmt = getDB()->prepare('SELECT * FROM agencies WHERE approval_status = ? ORDER BY created_at ASC');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public static function countsByStatus(): array
    {
        $rows = getDB()->query('SELECT approval_status, COUNT(*) AS c FROM agencies GROUP BY approval_status')->fetchAll();
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $counts[$row['approval_status']] = (int) $row['c'];
        }
        return $counts;
    }

    public static function approve(int $id, int $adminId): void
    {
        $stmt = getDB()->prepare(
            "UPDATE agencies SET approval_status = 'approved', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?"
        );
        $stmt->execute([$adminId, $id]);
        AuditLog::record('admin', $adminId, 'agency.approve', 'agency', $id);
    }

    public static function reject(int $id, int $adminId, string $reason): void
    {
        $stmt = getDB()->prepare(
            "UPDATE agencies SET approval_status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?"
        );
        $stmt->execute([$adminId, $reason, $id]);
        AuditLog::record('admin', $adminId, 'agency.reject', 'agency', $id, ['reason' => $reason]);
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = getDB()->prepare(
            'UPDATE agencies SET company_name = :company_name, contact_person = :contact_person, phone = :phone,
             address = :address, bio = :bio WHERE id = :id'
        );
        $data['id'] = $id;
        $stmt->execute($data);
    }

    public static function updateLogo(int $id, string $logoPath): void
    {
        $stmt = getDB()->prepare('UPDATE agencies SET logo_path = ? WHERE id = ?');
        $stmt->execute([$logoPath, $id]);
    }
}
