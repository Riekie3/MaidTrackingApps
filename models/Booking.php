<?php

class Booking
{
    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO bookings (client_id, housemaid_id, agency_id, start_date, end_date, notes, status)
             VALUES (:client_id, :housemaid_id, :agency_id, :start_date, :end_date, :notes, \'requested\')'
        );
        $stmt->execute($data);
        $id = (int) getDB()->lastInsertId();
        AuditLog::record('client', $data['client_id'], 'booking.request', 'booking', $id);
        return $id;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT b.*, h.full_name AS housemaid_name, h.photo_path AS housemaid_photo,
                    a.company_name AS agency_name, c.name AS client_name, c.email AS client_email
             FROM bookings b
             JOIN housemaids h ON h.id = b.housemaid_id
             JOIN agencies a ON a.id = b.agency_id
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
        return ($row && (int) $row['agency_id'] === $agencyId) ? $row : null;
    }

    public static function listByClient(int $clientId): array
    {
        $stmt = getDB()->prepare(
            'SELECT b.*, h.full_name AS housemaid_name, h.photo_path AS housemaid_photo, a.company_name AS agency_name
             FROM bookings b
             JOIN housemaids h ON h.id = b.housemaid_id
             JOIN agencies a ON a.id = b.agency_id
             WHERE b.client_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function listByAgency(int $agencyId, ?string $status = null): array
    {
        $sql = 'SELECT b.*, h.full_name AS housemaid_name, c.name AS client_name, c.phone AS client_phone
                FROM bookings b
                JOIN housemaids h ON h.id = b.housemaid_id
                JOIN clients c ON c.id = b.client_id
                WHERE b.agency_id = ?';
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

    public static function accept(int $id): void
    {
        $booking = self::findById($id);
        getDB()->prepare("UPDATE bookings SET status = 'accepted' WHERE id = ?")->execute([$id]);
        if ($booking) {
            Housemaid::setAvailability((int) $booking['housemaid_id'], 'placed');
        }
        AuditLog::record('agency', $booking['agency_id'] ?? null, 'booking.accept', 'booking', $id);
    }

    public static function decline(int $id): void
    {
        getDB()->prepare("UPDATE bookings SET status = 'declined' WHERE id = ?")->execute([$id]);
        $booking = self::findById($id);
        AuditLog::record('agency', $booking['agency_id'] ?? null, 'booking.decline', 'booking', $id);
    }

    public static function complete(int $id): void
    {
        $booking = self::findById($id);
        getDB()->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$id]);
        if ($booking) {
            Housemaid::setAvailability((int) $booking['housemaid_id'], 'available');
        }
        AuditLog::record('agency', $booking['agency_id'] ?? null, 'booking.complete', 'booking', $id);
    }

    public static function cancel(int $id, string $actorType, ?int $actorId): void
    {
        $booking = self::findById($id);
        getDB()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        if ($booking && $booking['status'] === 'accepted') {
            Housemaid::setAvailability((int) $booking['housemaid_id'], 'available');
        }
        AuditLog::record($actorType, $actorId, 'booking.cancel', 'booking', $id);
    }

    // A client may only see full (unmasked) documents, and may only
    // review, once they've had a real placement — accepted, active, or
    // completed — with this specific housemaid.
    public static function hasQualifyingBooking(int $clientId, int $housemaidId): bool
    {
        $stmt = getDB()->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE client_id = ? AND housemaid_id = ? AND status IN ('accepted','active','completed')"
        );
        $stmt->execute([$clientId, $housemaidId]);
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
