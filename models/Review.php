<?php

class Review
{
    public static function create(array $data): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO reviews (booking_id, client_id, provider_type, provider_id, rating_reliability, rating_skill, rating_hygiene, rating_communication, comment)
             VALUES (:booking_id, :client_id, :provider_type, :provider_id, :rating_reliability, :rating_skill, :rating_hygiene, :rating_communication, :comment)'
        );
        $stmt->execute($data);
        $id = (int) getDB()->lastInsertId();
        self::recalcProviderRating($data['provider_type'], (int) $data['provider_id']);
        AuditLog::record('client', (int) $data['client_id'], 'review.create', 'review', $id);
        return $id;
    }

    public static function findByBooking(int $bookingId): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM reviews WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }

    public static function listForProvider(string $providerType, int $providerId): array
    {
        $stmt = getDB()->prepare(
            'SELECT r.*, c.name AS client_name FROM reviews r
             JOIN clients c ON c.id = r.client_id
             WHERE r.provider_type = ? AND r.provider_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$providerType, $providerId]);
        return $stmt->fetchAll();
    }

    public static function addAgencyResponse(int $reviewId, int $agencyId, string $response): bool
    {
        $stmt = getDB()->prepare(
            "UPDATE reviews r
             JOIN housemaids h ON h.id = r.provider_id AND r.provider_type = 'housemaid'
             SET r.agency_response = ?
             WHERE r.id = ? AND h.agency_id = ?"
        );
        $stmt->execute([$response, $reviewId, $agencyId]);
        return $stmt->rowCount() > 0;
    }

    public static function recalcProviderRating(string $providerType, int $providerId): void
    {
        $stmt = getDB()->prepare(
            'SELECT AVG((rating_reliability + rating_skill + rating_hygiene + rating_communication) / 4) AS avg_rating, COUNT(*) AS n
             FROM reviews WHERE provider_type = ? AND provider_id = ?'
        );
        $stmt->execute([$providerType, $providerId]);
        $row = $stmt->fetch();
        $avg = $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : null;
        $count = (int) $row['n'];

        $table = $providerType === 'freelancer' ? 'freelancers' : 'housemaids';
        getDB()->prepare("UPDATE $table SET avg_rating = ?, ratings_count = ? WHERE id = ?")
            ->execute([$avg, $count, $providerId]);
    }

    // Per-category breakdown for the due-diligence report.
    public static function categoryAverages(string $providerType, int $providerId): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT AVG(rating_reliability) AS reliability, AVG(rating_skill) AS skill,
                    AVG(rating_hygiene) AS hygiene, AVG(rating_communication) AS communication,
                    COUNT(*) AS n
             FROM reviews WHERE provider_type = ? AND provider_id = ?'
        );
        $stmt->execute([$providerType, $providerId]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['n'] === 0) {
            return null;
        }
        return [
            'reliability' => round((float) $row['reliability'], 1),
            'skill' => round((float) $row['skill'], 1),
            'hygiene' => round((float) $row['hygiene'], 1),
            'communication' => round((float) $row['communication'], 1),
            'n' => (int) $row['n'],
        ];
    }
}
