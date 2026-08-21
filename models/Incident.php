<?php

class Incident
{
    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO incidents (provider_type, provider_id, reported_by_type, reported_by_id, incident_type, description, evidence_path)
             VALUES (:provider_type, :provider_id, :reported_by_type, :reported_by_id, :incident_type, :description, :evidence_path)'
        );
        $stmt->execute($data);
        $id = (int) getDB()->lastInsertId();
        AuditLog::record($data['reported_by_type'], (int) $data['reported_by_id'], 'incident.report', 'incident', $id);
        return $id;
    }

    public static function find(int $id): ?array
    {
        $stmt = getDB()->prepare(
            "SELECT i.*, COALESCE(h.full_name, f.full_name) AS provider_name, h.agency_id
             FROM incidents i
             LEFT JOIN housemaids h ON i.provider_type = 'housemaid' AND h.id = i.provider_id
             LEFT JOIN freelancers f ON i.provider_type = 'freelancer' AND f.id = i.provider_id
             WHERE i.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Admin's queue — anything not yet at a final state, across both
    // provider types.
    public static function listOpen(): array
    {
        $stmt = getDB()->query(
            "SELECT i.*, COALESCE(h.full_name, f.full_name) AS provider_name,
                    COALESCE(a.company_name, 'Independent freelancer') AS agency_name
             FROM incidents i
             LEFT JOIN housemaids h ON i.provider_type = 'housemaid' AND h.id = i.provider_id
             LEFT JOIN agencies a ON a.id = h.agency_id
             LEFT JOIN freelancers f ON i.provider_type = 'freelancer' AND f.id = i.provider_id
             WHERE i.status IN ('reported','under_review')
             ORDER BY i.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public static function listForProvider(string $providerType, int $providerId): array
    {
        $stmt = getDB()->prepare('SELECT * FROM incidents WHERE provider_type = ? AND provider_id = ? ORDER BY created_at DESC');
        $stmt->execute([$providerType, $providerId]);
        return $stmt->fetchAll();
    }

    public static function listVerifiedForProvider(string $providerType, int $providerId): array
    {
        $stmt = getDB()->prepare(
            "SELECT * FROM incidents WHERE provider_type = ? AND provider_id = ? AND status = 'verified' ORDER BY created_at DESC"
        );
        $stmt->execute([$providerType, $providerId]);
        return $stmt->fetchAll();
    }

    public static function markUnderReview(int $id, int $adminId): void
    {
        getDB()->prepare("UPDATE incidents SET status = 'under_review', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$adminId, $id]);
        AuditLog::record('admin', $adminId, 'incident.under_review', 'incident', $id);
    }

    public static function verify(int $id, int $adminId): void
    {
        getDB()->prepare("UPDATE incidents SET status = 'verified', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$adminId, $id]);
        AuditLog::record('admin', $adminId, 'incident.verify', 'incident', $id);
    }

    public static function dismiss(int $id, int $adminId): void
    {
        getDB()->prepare("UPDATE incidents SET status = 'dismissed', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$adminId, $id]);
        AuditLog::record('admin', $adminId, 'incident.dismiss', 'incident', $id);
    }
}
