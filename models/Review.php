<?php

class Review
{
    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO reviews (booking_id, client_id, housemaid_id, rating_reliability, rating_skill, rating_hygiene, rating_communication, comment)
             VALUES (:booking_id, :client_id, :housemaid_id, :rating_reliability, :rating_skill, :rating_hygiene, :rating_communication, :comment)'
        );
        $stmt->execute($data);
        $id = (int) getDB()->lastInsertId();
        self::recalcHousemaidRating((int) $data['housemaid_id']);
        AuditLog::record('client', (int) $data['client_id'], 'review.create', 'review', $id);
        return $id;
    }

    public static function findByBooking(int $bookingId): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM reviews WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }

    public static function listForHousemaid(int $housemaidId): array
    {
        $stmt = getDB()->prepare(
            'SELECT r.*, c.name AS client_name FROM reviews r
             JOIN clients c ON c.id = r.client_id
             WHERE r.housemaid_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$housemaidId]);
        return $stmt->fetchAll();
    }

    public static function addAgencyResponse(int $reviewId, int $agencyId, string $response): bool
    {
        $stmt = getDB()->prepare(
            'UPDATE reviews r
             JOIN housemaids h ON h.id = r.housemaid_id
             SET r.agency_response = ?
             WHERE r.id = ? AND h.agency_id = ?'
        );
        $stmt->execute([$response, $reviewId, $agencyId]);
        return $stmt->rowCount() > 0;
    }

    public static function recalcHousemaidRating(int $housemaidId): void
    {
        $stmt = getDB()->prepare(
            'SELECT AVG((rating_reliability + rating_skill + rating_hygiene + rating_communication) / 4) AS avg_rating, COUNT(*) AS n
             FROM reviews WHERE housemaid_id = ?'
        );
        $stmt->execute([$housemaidId]);
        $row = $stmt->fetch();
        $avg = $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : null;
        $count = (int) $row['n'];

        getDB()->prepare('UPDATE housemaids SET avg_rating = ?, ratings_count = ? WHERE id = ?')
            ->execute([$avg, $count, $housemaidId]);
    }
}
