<?php

class Freelancer
{
    public static function create(array $core): int
    {
        $core['passport_number'] = encrypt_field($core['passport_number'] ?? null);
        $core['national_id_number'] = encrypt_field($core['national_id_number'] ?? null);
        $core['bank_account_number'] = encrypt_field($core['bank_account_number'] ?? null);

        $stmt = getDB()->prepare(
            'INSERT INTO freelancers
                (email, phone, password_hash, full_name, photo_path, date_of_birth, gender,
                 nationality_country_id, marital_status, religion, passport_number, passport_expiry,
                 work_permit_number, work_permit_expiry, national_id_number, home_address,
                 emergency_contact_name, emergency_contact_phone, current_staying_address,
                 years_experience, bank_name, bank_account_holder, bank_account_number,
                 approval_status, consent_given_at, submitted_at)
             VALUES
                (:email, :phone, :password_hash, :full_name, :photo_path, :date_of_birth, :gender,
                 :nationality_country_id, :marital_status, :religion, :passport_number, :passport_expiry,
                 :work_permit_number, :work_permit_expiry, :national_id_number, :home_address,
                 :emergency_contact_name, :emergency_contact_phone, :current_staying_address,
                 :years_experience, :bank_name, :bank_account_holder, :bank_account_number,
                 \'pending\', NOW(), NOW())'
        );
        $stmt->execute($core);
        return (int) getDB()->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM freelancers WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT f.*, c.name AS nationality_name FROM freelancers f
             LEFT JOIN countries c ON c.id = f.nationality_country_id
             WHERE f.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['passport_number'] = decrypt_field($row['passport_number']);
        $row['national_id_number'] = decrypt_field($row['national_id_number']);
        $row['bank_account_number'] = decrypt_field($row['bank_account_number']);
        return $row;
    }

    public static function markEmailPhoneVerified(int $id): void
    {
        getDB()->prepare('UPDATE freelancers SET email_verified_at = NOW(), phone_verified_at = NOW() WHERE id = ?')
            ->execute([$id]);
    }

    public static function isVerified(array $freelancer): bool
    {
        return $freelancer['email_verified_at'] !== null && $freelancer['phone_verified_at'] !== null;
    }

    public static function updateProfile(int $id, array $core): void
    {
        $core['passport_number'] = encrypt_field($core['passport_number'] ?? null);
        $core['national_id_number'] = encrypt_field($core['national_id_number'] ?? null);
        $core['bank_account_number'] = encrypt_field($core['bank_account_number'] ?? null);
        $core['id'] = $id;

        $stmt = getDB()->prepare(
            'UPDATE freelancers SET
                full_name = :full_name, date_of_birth = :date_of_birth, gender = :gender,
                nationality_country_id = :nationality_country_id, marital_status = :marital_status,
                religion = :religion, passport_number = :passport_number, passport_expiry = :passport_expiry,
                work_permit_number = :work_permit_number, work_permit_expiry = :work_permit_expiry,
                national_id_number = :national_id_number, home_address = :home_address,
                emergency_contact_name = :emergency_contact_name, emergency_contact_phone = :emergency_contact_phone,
                current_staying_address = :current_staying_address, years_experience = :years_experience,
                bank_name = :bank_name, bank_account_holder = :bank_account_holder,
                bank_account_number = :bank_account_number
             WHERE id = :id'
        );
        $stmt->execute($core);
    }

    public static function updatePhoto(int $id, string $photoPath): void
    {
        getDB()->prepare('UPDATE freelancers SET photo_path = ? WHERE id = ?')->execute([$photoPath, $id]);
    }

    // --- Services & locations ------------------------------------------

    public static function attachServices(int $id, array $services): void
    {
        // $services: [ ['service_id' => int, 'price' => float, 'price_unit' => string], ... ]
        getDB()->prepare('DELETE FROM freelancer_services WHERE freelancer_id = ?')->execute([$id]);
        $stmt = getDB()->prepare(
            'INSERT INTO freelancer_services (freelancer_id, service_id, price, price_unit) VALUES (?, ?, ?, ?)'
        );
        foreach ($services as $s) {
            $stmt->execute([$id, (int) $s['service_id'], (float) $s['price'], $s['price_unit']]);
        }
    }

    public static function getServices(int $id): array
    {
        $stmt = getDB()->prepare(
            'SELECT fs.*, s.name AS service_name FROM freelancer_services fs
             JOIN services s ON s.id = fs.service_id WHERE fs.freelancer_id = ? ORDER BY s.name'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public static function attachLocations(int $id, array $locationIds): void
    {
        getDB()->prepare('DELETE FROM freelancer_locations WHERE freelancer_id = ?')->execute([$id]);
        $stmt = getDB()->prepare('INSERT IGNORE INTO freelancer_locations (freelancer_id, location_id) VALUES (?, ?)');
        foreach ($locationIds as $locId) {
            $stmt->execute([$id, (int) $locId]);
        }
    }

    public static function getLocationIds(int $id): array
    {
        $stmt = getDB()->prepare('SELECT location_id FROM freelancer_locations WHERE freelancer_id = ?');
        $stmt->execute([$id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function getLocationNames(int $id): array
    {
        $stmt = getDB()->prepare(
            'SELECT l.name FROM freelancer_locations fl JOIN locations l ON l.id = fl.location_id
             WHERE fl.freelancer_id = ? ORDER BY l.name'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // --- Admin approval --------------------------------------------------

    public static function listPending(): array
    {
        return getDB()->query(
            "SELECT f.*, c.name AS nationality_name FROM freelancers f
             LEFT JOIN countries c ON c.id = f.nationality_country_id
             WHERE f.approval_status = 'pending'
             ORDER BY f.submitted_at ASC"
        )->fetchAll();
    }

    public static function approve(int $id, int $adminId): void
    {
        $stmt = getDB()->prepare(
            "UPDATE freelancers SET approval_status = 'approved', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?"
        );
        $stmt->execute([$adminId, $id]);
        AuditLog::record('admin', $adminId, 'freelancer.approve', 'freelancer', $id);
    }

    public static function reject(int $id, int $adminId, string $reason): void
    {
        $stmt = getDB()->prepare(
            "UPDATE freelancers SET approval_status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?"
        );
        $stmt->execute([$adminId, $reason, $id]);
        AuditLog::record('admin', $adminId, 'freelancer.reject', 'freelancer', $id, ['reason' => $reason]);
    }

    public static function globalCounts(): array
    {
        $row = getDB()->query(
            "SELECT SUM(approval_status = 'pending') AS pending, SUM(approval_status = 'approved') AS approved,
                    SUM(approval_status = 'rejected') AS rejected, COUNT(*) AS total
             FROM freelancers"
        )->fetch();
        return [
            'pending'  => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'total'    => (int) ($row['total'] ?? 0),
        ];
    }

    // --- Client-facing browse -------------------------------------------
    // Approved AND verified (own email/phone) — see the Proposal §10 note
    // on why freelancers need this extra layer Admin approval alone gives
    // an agency housemaid.

    public static function browse(array $filters, int $page, int $perPage): array
    {
        $where = ["f.approval_status = 'approved'", "f.email_verified_at IS NOT NULL", "f.phone_verified_at IS NOT NULL"];
        $params = [];

        if (!empty($filters['service_id'])) {
            $where[] = 'f.id IN (SELECT freelancer_id FROM freelancer_services WHERE service_id = :service_id)';
            $params['service_id'] = (int) $filters['service_id'];
        }
        if (!empty($filters['location_id'])) {
            $where[] = 'f.id IN (SELECT freelancer_id FROM freelancer_locations WHERE location_id = :location_id)';
            $params['location_id'] = (int) $filters['location_id'];
        }
        if (!empty($filters['nationality_country_id'])) {
            $where[] = 'f.nationality_country_id = :nationality_country_id';
            $params['nationality_country_id'] = (int) $filters['nationality_country_id'];
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = getDB()->prepare("SELECT COUNT(*) FROM freelancers f WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT f.*, c.name AS nationality_name
                FROM freelancers f
                LEFT JOIN countries c ON c.id = f.nationality_country_id
                WHERE $whereSql
                ORDER BY f.avg_rating DESC, f.created_at DESC
                LIMIT $perPage OFFSET $offset";
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function publicFindById(int $id): ?array
    {
        $row = self::findById($id);
        if (!$row || $row['approval_status'] !== 'approved' || !self::isVerified($row)) {
            return null;
        }
        return $row;
    }
}
