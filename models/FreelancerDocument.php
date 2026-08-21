<?php

// Mirrors HousemaidDocument exactly.

class FreelancerDocument
{
    public static function create(int $freelancerId, string $docType, string $filePath, ?string $title = null, ?string $issuedDate = null, ?string $expiryDate = null): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO freelancer_documents (freelancer_id, doc_type, title, file_path, issued_date, expiry_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$freelancerId, $docType, $title, $filePath, $issuedDate ?: null, $expiryDate ?: null]);
        return (int) getDB()->lastInsertId();
    }

    public static function listForFreelancer(int $freelancerId): array
    {
        $stmt = getDB()->prepare('SELECT * FROM freelancer_documents WHERE freelancer_id = ? ORDER BY doc_type');
        $stmt->execute([$freelancerId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM freelancer_documents WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
