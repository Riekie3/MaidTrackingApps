<?php

class Incident
{
    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO incidents (housemaid_id, reported_by_type, reported_by_id, incident_type, description, evidence_path)
             VALUES (:housemaid_id, :reported_by_type, :reported_by_id, :incident_type, :description, :evidence_path)'
        );
        $stmt->execute($data);
        $id = (int) getDB()->lastInsertId();
        AuditLog::record($data['reported_by_type'], (int) $data['reported_by_id'], 'incident.report', 'incident', $id);
        return $id;
    }

    public static function find(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT i.*, h.full_name AS housemaid_name, h.agency_id
             FROM incidents i JOIN housemaids h ON h.id = i.housemaid_id
             WHERE i.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Admin's queue — anything not yet at a final state.
    public static function listOpen(): array
    {
        $stmt = getDB()->query(
            "SELECT i.*, h.full_name AS housemaid_name, a.company_name AS agency_name
             FROM incidents i
             JOIN housemaids h ON h.id = i.housemaid_id
             JOIN agencies a ON a.id = h.agency_id
             WHERE i.status IN ('reported','under_review')
             ORDER BY i.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public static function listForHousemaid(int $housemaidId): array
    {
        $stmt = getDB()->prepare('SELECT * FROM incidents WHERE housemaid_id = ? ORDER BY created_at DESC');
        $stmt->execute([$housemaidId]);
        return $stmt->fetchAll();
    }

    public static function listVerifiedForHousemaid(int $housemaidId): array
    {
        $stmt = getDB()->prepare("SELECT * FROM incidents WHERE housemaid_id = ? AND status = 'verified' ORDER BY created_at DESC");
        $stmt->execute([$housemaidId]);
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
