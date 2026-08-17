<?php

class AuditLog
{
    public static function record(string $actorType, ?int $actorId, string $action, string $entityType, ?int $entityId = null, ?array $meta = null): void
    {
        $stmt = getDB()->prepare(
            'INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, meta)
             VALUES (:actor_type, :actor_id, :action, :entity_type, :entity_id, :meta)'
        );
        $stmt->execute([
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'meta'        => $meta ? json_encode($meta) : null,
        ]);
    }

    public static function recent(int $limit = 100): array
    {
        $stmt = getDB()->prepare('SELECT * FROM audit_log ORDER BY created_at DESC, id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
