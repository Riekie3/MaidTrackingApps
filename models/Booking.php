<?php

class Booking
{
    // Shared SELECT fragment: resolves provider_name/provider_photo from
    // whichever of housemaids/freelancers provider_type points at. LEFT
    // JOIN both since only one will ever match per row.
    private const PROVIDER_JOIN = "
        LEFT JOIN housemaids h ON b.provider_type = 'housemaid' AND h.id = b.provider_id
        LEFT JOIN freelancers f ON b.provider_type = 'freelancer' AND f.id = b.provider_id
        LEFT JOIN services sv ON sv.id = b.service_id";
    private const PROVIDER_COLS = "
        COALESCE(h.full_name, f.full_name) AS provider_name,
        COALESCE(h.photo_path, f.photo_path) AS provider_photo,
        sv.name AS service_name";

    public static function create(array $data): int
    {
        $data['provider_type'] = $data['provider_type'] ?? 'housemaid';
        $data['agency_id'] = $data['agency_id'] ?? null;
        $data['service_id'] = $data['service_id'] ?? null;
        $stmt = getDB()->prepare(
            'INSERT INTO bookings (client_id, provider_type, provider_id, agency_id, service_id, start_date, end_date, notes, status)
             VALUES (:client_id, :provider_type, :provider_id, :agency_id, :service_id, :start_date, :end_date, :notes, \'requested\')'
        );
        $stmt->execute($data);
        $id = (int) getDB()->lastInsertId();
        AuditLog::record('client', $data['client_id'], 'booking.request', 'booking', $id);
        return $id;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT b.*, ' . self::PROVIDER_COLS . ',
                    a.company_name AS agency_name, c.name AS client_name, c.email AS client_email
             FROM bookings b' . self::PROVIDER_JOIN . '
             LEFT JOIN agencies a ON a.id = b.agency_id
             JOIN clients c ON c.id = b.client_id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdForClient(int $id, int $clientId): ?array
    {
        $row = self::findById($id);
        return ($row && (int) $row['client_id'] === $clientId) ? $row : null;
    }

    public static function findByIdForAgency(int $id, int $agencyId): ?array
    {
        $row = self::findById($id);
        return ($row && $row['provider_type'] === 'housemaid' && (int) $row['agency_id'] === $agencyId) ? $row : null;
    }

    public static function findByIdForFreelancer(int $id, int $freelancerId): ?array
    {
        $row = self::findById($id);
        return ($row && $row['provider_type'] === 'freelancer' && (int) $row['provider_id'] === $freelancerId) ? $row : null;
    }

    public static function listByClient(int $clientId): array
    {
        $stmt = getDB()->prepare(
            'SELECT b.*, ' . self::PROVIDER_COLS . ', a.company_name AS agency_name
             FROM bookings b' . self::PROVIDER_JOIN . '
             LEFT JOIN agencies a ON a.id = b.agency_id
             WHERE b.client_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function listByAgency(int $agencyId, ?string $status = null): array
    {
        $sql = "SELECT b.*, h.full_name AS provider_name, c.name AS client_name, c.phone AS client_phone
                FROM bookings b
                JOIN housemaids h ON h.id = b.provider_id AND b.provider_type = 'housemaid'
                JOIN clients c ON c.id = b.client_id
                WHERE b.agency_id = ?";
        $params = [$agencyId];
        if ($status) {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY b.created_at DESC';
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function listByFreelancer(int $freelancerId, ?string $status = null): array
    {
        $sql = "SELECT b.*, sv.name AS service_name, c.name AS client_name, c.phone AS client_phone
                FROM bookings b
                JOIN services sv ON sv.id = b.service_id
                JOIN clients c ON c.id = b.client_id
                WHERE b.provider_id = ? AND b.provider_type = 'freelancer'";
        $params = [$freelancerId];
        if ($status) {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY b.created_at DESC';
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function accept(int $id): void
    {
        $booking = self::findById($id);
        getDB()->prepare("UPDATE bookings SET status = 'accepted' WHERE id = ?")->execute([$id]);
        // Freelancers take date-ranged bookings, potentially several at
        // once — unlike a housemaid's single continuous placement, one
        // accepted booking doesn't mean she's unavailable for others.
        if ($booking && $booking['provider_type'] === 'housemaid') {
            Housemaid::setAvailability((int) $booking['provider_id'], 'placed');
        }
        $actorType = $booking['provider_type'] === 'freelancer' ? 'freelancer' : 'agency';
        AuditLog::record($actorType, $booking['agency_id'] ?? $booking['provider_id'] ?? null, 'booking.accept', 'booking', $id);
    }

    public static function decline(int $id): void
    {
        getDB()->prepare("UPDATE bookings SET status = 'declined' WHERE id = ?")->execute([$id]);
        $booking = self::findById($id);
        $actorType = $booking['provider_type'] === 'freelancer' ? 'freelancer' : 'agency';
        AuditLog::record($actorType, $booking['agency_id'] ?? $booking['provider_id'] ?? null, 'booking.decline', 'booking', $id);
    }

    public static function complete(int $id): void
    {
        $booking = self::findById($id);
        getDB()->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$id]);
        if ($booking && $booking['provider_type'] === 'housemaid') {
            Housemaid::setAvailability((int) $booking['provider_id'], 'available');
        }
        $actorType = $booking['provider_type'] === 'freelancer' ? 'freelancer' : 'agency';
        AuditLog::record($actorType, $booking['agency_id'] ?? $booking['provider_id'] ?? null, 'booking.complete', 'booking', $id);
    }

    public static function cancel(int $id, string $actorType, ?int $actorId): void
    {
        $booking = self::findById($id);
        getDB()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        if ($booking && $booking['status'] === 'accepted' && $booking['provider_type'] === 'housemaid') {
            Housemaid::setAvailability((int) $booking['provider_id'], 'available');
        }
        AuditLog::record($actorType, $actorId, 'booking.cancel', 'booking', $id);
    }

    // A client may only see full (unmasked) documents, and may only
    // review or report an incident, once they've had a real booking —
    // accepted, active, or completed — with this specific provider.
    public static function hasQualifyingBooking(int $clientId, string $providerType, int $providerId): bool
    {
        $stmt = getDB()->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE client_id = ? AND provider_type = ? AND provider_id = ? AND status IN ('accepted','active','completed')"
        );
        $stmt->execute([$clientId, $providerType, $providerId]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function completedBookingsAwaitingReview(int $clientId): array
    {
        $stmt = getDB()->prepare(
            "SELECT b.* FROM bookings b
             LEFT JOIN reviews r ON r.booking_id = b.id
             WHERE b.client_id = ? AND b.status = 'completed' AND r.id IS NULL"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    // --- Reports (Phase 3) -------------------------------------------------
    // Agency-scoped reports only ever see provider_type='housemaid' rows
    // naturally, since agency_id is NULL on every freelancer booking.

    public static function countsByAgency(int $agencyId): array
    {
        $stmt = getDB()->prepare('SELECT status, COUNT(*) AS c FROM bookings WHERE agency_id = ? GROUP BY status');
        $stmt->execute([$agencyId]);
        $counts = ['requested' => 0, 'accepted' => 0, 'completed' => 0, 'declined' => 0, 'cancelled' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $counts;
    }

    // % of this agency's clients who have booked more than once — a
    // simple repeat-business signal for the performance report.
    public static function repeatClientRate(int $agencyId): ?float
    {
        $stmt = getDB()->prepare(
            "SELECT COUNT(*) AS total_clients, SUM(bookings_per_client > 1) AS repeat_clients FROM (
                SELECT client_id, COUNT(*) AS bookings_per_client FROM bookings
                WHERE agency_id = ? AND status IN ('accepted','completed')
                GROUP BY client_id
             ) t"
        );
        $stmt->execute([$agencyId]);
        $row = $stmt->fetch();
        $total = (int) ($row['total_clients'] ?? 0);
        if ($total === 0) {
            return null;
        }
        return round((((int) ($row['repeat_clients'] ?? 0)) / $total) * 100, 1);
    }

    // Accepted+completed placements per month, most recent $months first.
    public static function monthlyPlacements(int $agencyId, int $months = 6): array
    {
        $stmt = getDB()->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
             FROM bookings
             WHERE agency_id = ? AND status IN ('accepted','completed')
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY ym ORDER BY ym ASC"
        );
        $stmt->execute([$agencyId, $months]);
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

    public static function globalCountsByStatus(): array
    {
        $stmt = getDB()->query('SELECT status, COUNT(*) AS c FROM bookings GROUP BY status');
        $counts = ['requested' => 0, 'accepted' => 0, 'completed' => 0, 'declined' => 0, 'cancelled' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $counts;
    }
}
