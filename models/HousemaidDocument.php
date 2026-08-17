<?php

class HousemaidDocument
{
    public static function create(int $housemaidId, string $docType, string $filePath, ?string $title = null, ?string $issuedDate = null, ?string $expiryDate = null): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO housemaid_documents (housemaid_id, doc_type, title, file_path, issued_date, expiry_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$housemaidId, $docType, $title, $filePath, $issuedDate ?: null, $expiryDate ?: null]);
        return (int) getDB()->lastInsertId();
    }

    public static function listForHousemaid(int $housemaidId): array
    {
        $stmt = getDB()->prepare('SELECT * FROM housemaid_documents WHERE housemaid_id = ? ORDER BY doc_type');
        $stmt->execute([$housemaidId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM housemaid_documents WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
