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

    // --- Client-facing (Phase 2) -------------------------------------------

    public static function publicFindById(int $id): ?array
    {
        $row = self::findById($id);
        return ($row && $row['approval_status'] === 'approved') ? $row : null;
    }

    public static function rosterStats(int $id): array
    {
        $row = getDB()->prepare(
            "SELECT COUNT(*) AS roster_count, AVG(NULLIF(avg_rating, 0)) AS agency_rating
             FROM housemaids WHERE agency_id = ? AND approval_status = 'approved'"
        );
        $row->execute([$id]);
        $result = $row->fetch();
        return [
            'roster_count'  => (int) ($result['roster_count'] ?? 0),
            'agency_rating' => $result['agency_rating'] !== null ? round((float) $result['agency_rating'], 2) : null,
        ];
    }

    public static function publicHousemaids(int $id): array
    {
        $stmt = getDB()->prepare(
            "SELECT h.*, c.name AS nationality_name FROM housemaids h
             LEFT JOIN countries c ON c.id = h.nationality_country_id
             WHERE h.agency_id = ? AND h.approval_status = 'approved'
             ORDER BY h.avg_rating DESC"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
}
