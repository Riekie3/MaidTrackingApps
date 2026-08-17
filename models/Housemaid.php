<?php

class Housemaid
{
    public static function create(array $core): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO housemaids
                (agency_id, full_name, photo_path, date_of_birth, gender, nationality_country_id,
                 marital_status, religion, passport_number, passport_expiry, work_permit_number,
                 work_permit_expiry, national_id_number, home_address, emergency_contact_name,
                 emergency_contact_phone, current_staying_address, years_experience,
                 approval_status, consent_given_at, submitted_at)
             VALUES
                (:agency_id, :full_name, :photo_path, :date_of_birth, :gender, :nationality_country_id,
                 :marital_status, :religion, :passport_number, :passport_expiry, :work_permit_number,
                 :work_permit_expiry, :national_id_number, :home_address, :emergency_contact_name,
                 :emergency_contact_phone, :current_staying_address, :years_experience,
                 \'pending\', NOW(), NOW())'
        );
        $stmt->execute($core);
        return (int) getDB()->lastInsertId();
    }

    public static function attachSkills(int $id, array $skillIds): void
    {
        $stmt = getDB()->prepare('INSERT IGNORE INTO housemaid_skills (housemaid_id, skill_id) VALUES (?, ?)');
        foreach ($skillIds as $skillId) {
            $stmt->execute([$id, (int) $skillId]);
        }
    }

    public static function attachLanguages(int $id, array $languageIds): void
    {
        $stmt = getDB()->prepare('INSERT IGNORE INTO housemaid_languages (housemaid_id, language_id) VALUES (?, ?)');
        foreach ($languageIds as $languageId) {
            $stmt->execute([$id, (int) $languageId]);
        }
    }

    public static function attachWorkCountries(int $id, array $countryIds): void
    {
        $stmt = getDB()->prepare('INSERT IGNORE INTO housemaid_work_countries (housemaid_id, country_id) VALUES (?, ?)');
        foreach ($countryIds as $countryId) {
            $stmt->execute([$id, (int) $countryId]);
        }
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT h.*, c.name AS nationality_name, a.company_name AS agency_name
             FROM housemaids h
             LEFT JOIN countries c ON c.id = h.nationality_country_id
             JOIN agencies a ON a.id = h.agency_id
             WHERE h.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdForAgency(int $id, int $agencyId): ?array
    {
        $row = self::findById($id);
        return ($row && (int) $row['agency_id'] === $agencyId) ? $row : null;
    }

    public static function getSkillIds(int $id): array
    {
        $stmt = getDB()->prepare('SELECT skill_id FROM housemaid_skills WHERE housemaid_id = ?');
        $stmt->execute([$id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function getSkillNames(int $id): array
    {
        $stmt = getDB()->prepare(
            'SELECT s.name FROM housemaid_skills hs JOIN skills s ON s.id = hs.skill_id WHERE hs.housemaid_id = ? ORDER BY s.name'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getLanguageIds(int $id): array
    {
        $stmt = getDB()->prepare('SELECT language_id FROM housemaid_languages WHERE housemaid_id = ?');
        $stmt->execute([$id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function getLanguageNames(int $id): array
    {
        $stmt = getDB()->prepare(
            'SELECT l.name FROM housemaid_languages hl JOIN languages l ON l.id = hl.language_id WHERE hl.housemaid_id = ? ORDER BY l.name'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getWorkCountryIds(int $id): array
    {
        $stmt = getDB()->prepare('SELECT country_id FROM housemaid_work_countries WHERE housemaid_id = ?');
        $stmt->execute([$id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function listByAgency(int $agencyId, ?string $status = null): array
    {
        $sql = 'SELECT h.*, c.name AS nationality_name FROM housemaids h
                LEFT JOIN countries c ON c.id = h.nationality_country_id
                WHERE h.agency_id = ?';
        $params = [$agencyId];
        if ($status) {
            $sql .= ' AND h.approval_status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY h.created_at DESC';
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countsByAgency(int $agencyId): array
    {
        $stmt = getDB()->prepare('SELECT approval_status, COUNT(*) AS c FROM housemaids WHERE agency_id = ? GROUP BY approval_status');
        $stmt->execute([$agencyId]);
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['approval_status']] = (int) $row['c'];
        }
        return $counts;
    }

    // Agencies with at least one pending housemaid, for the Admin queue's
    // first drill-down level.
    public static function pendingAgencySummaries(): array
    {
        return getDB()->query(
            "SELECT a.id AS agency_id, a.company_name, COUNT(h.id) AS pending_count, MIN(h.submitted_at) AS oldest_submitted_at
             FROM housemaids h
             JOIN agencies a ON a.id = h.agency_id
             WHERE h.approval_status = 'pending'
             GROUP BY a.id, a.company_name
             ORDER BY oldest_submitted_at ASC"
        )->fetchAll();
    }

    public static function listPendingByAgency(int $agencyId): array
    {
        $stmt = getDB()->prepare(
            "SELECT h.*, c.name AS nationality_name FROM housemaids h
             LEFT JOIN countries c ON c.id = h.nationality_country_id
             WHERE h.agency_id = ? AND h.approval_status = 'pending'
             ORDER BY h.submitted_at ASC"
        );
        $stmt->execute([$agencyId]);
        return $stmt->fetchAll();
    }

    public static function approve(int $id, int $adminId): void
    {
        $stmt = getDB()->prepare(
            "UPDATE housemaids SET approval_status = 'approved', availability_status = 'available',
             reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?"
        );
        $stmt->execute([$adminId, $id]);
        AuditLog::record('admin', $adminId, 'housemaid.approve', 'housemaid', $id);
    }

    public static function reject(int $id, int $adminId, string $reason): void
    {
        $stmt = getDB()->prepare(
            "UPDATE housemaids SET approval_status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?"
        );
        $stmt->execute([$adminId, $reason, $id]);
        AuditLog::record('admin', $adminId, 'housemaid.reject', 'housemaid', $id, ['reason' => $reason]);
    }

    public static function bulkApprove(array $ids, int $adminId): void
    {
        foreach ($ids as $id) {
            self::approve((int) $id, $adminId);
        }
    }

    public static function bulkReject(array $ids, int $adminId, string $reason): void
    {
        foreach ($ids as $id) {
            self::reject((int) $id, $adminId, $reason);
        }
    }

    public static function updateAvailability(int $id, int $agencyId, string $status): bool
    {
        $stmt = getDB()->prepare(
            "UPDATE housemaids SET availability_status = ? WHERE id = ? AND agency_id = ? AND approval_status = 'approved'"
        );
        $stmt->execute([$status, $id, $agencyId]);
        return $stmt->rowCount() > 0;
    }

    public static function globalCounts(): array
    {
        $row = getDB()->query(
            "SELECT
                SUM(approval_status = 'pending') AS pending,
                SUM(approval_status = 'approved') AS approved,
                SUM(approval_status = 'rejected') AS rejected,
                COUNT(*) AS total
             FROM housemaids"
        )->fetch();
        return [
            'pending'  => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'total'    => (int) ($row['total'] ?? 0),
        ];
    }

    // System-triggered status change (booking accepted/completed), not
    // gated by agency ownership the way the agency's own toggle is.
    public static function setAvailability(int $id, string $status): void
    {
        getDB()->prepare('UPDATE housemaids SET availability_status = ? WHERE id = ?')->execute([$status, $id]);
    }

    // --- Client-facing browse (Phase 2) -----------------------------------
    // Only approved housemaids from approved agencies are ever eligible —
    // nothing pending or rejected is reachable from these queries.

    public static function browse(array $filters, int $page, int $perPage): array
    {
        $where = ["h.approval_status = 'approved'", "a.approval_status = 'approved'"];
        $params = [];

        if (!empty($filters['skill_id'])) {
            $where[] = 'h.id IN (SELECT housemaid_id FROM housemaid_skills WHERE skill_id = :skill_id)';
            $params['skill_id'] = (int) $filters['skill_id'];
        }
        if (!empty($filters['nationality_country_id'])) {
            $where[] = 'h.nationality_country_id = :nationality_country_id';
            $params['nationality_country_id'] = (int) $filters['nationality_country_id'];
        }
        if (!empty($filters['min_experience'])) {
            $where[] = 'h.years_experience >= :min_experience';
            $params['min_experience'] = (int) $filters['min_experience'];
        }
        if (!empty($filters['availability_status'])) {
            $where[] = 'h.availability_status = :availability_status';
            $params['availability_status'] = $filters['availability_status'];
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = getDB()->prepare(
            "SELECT COUNT(*) FROM housemaids h JOIN agencies a ON a.id = h.agency_id WHERE $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT h.*, c.name AS nationality_name, a.company_name AS agency_name, a.logo_path AS agency_logo
                FROM housemaids h
                LEFT JOIN countries c ON c.id = h.nationality_country_id
                JOIN agencies a ON a.id = h.agency_id
                WHERE $whereSql
                ORDER BY h.avg_rating DESC, h.created_at DESC
                LIMIT $perPage OFFSET $offset";
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    // --- Reports (Phase 3) -------------------------------------------------

    public static function monthlySubmissions(int $months = 6): array
    {
        $stmt = getDB()->prepare(
            "SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS ym, COUNT(*) AS c
             FROM housemaids WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY ym ORDER BY ym ASC"
        );
        $stmt->execute([$months]);
        $byMonth = [];
        foreach ($stmt->fetchAll() as $row) {
            $byMonth[$row['ym']] = (int) $row['c'];
        }
        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $series[$ym] = $byMonth[$ym] ?? 0;
        }
        return $series;
    }

    // Approved-only lookup for anything client-facing — a pending or
    // rejected housemaid is never reachable by ID from the client side.
    public static function publicFindById(int $id): ?array
    {
        $row = self::findById($id);
        if (!$row || $row['approval_status'] !== 'approved') {
            return null;
        }
        return $row;
    }
}
