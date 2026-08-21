<?php

class MasterData
{
    public static function skills(): array
    {
        return getDB()->query('SELECT * FROM skills ORDER BY category, name')->fetchAll();
    }

    public static function languages(): array
    {
        return getDB()->query('SELECT * FROM languages ORDER BY name')->fetchAll();
    }

    public static function countries(): array
    {
        return getDB()->query('SELECT * FROM countries ORDER BY name')->fetchAll();
    }

    public static function services(): array
    {
        return getDB()->query('SELECT * FROM services ORDER BY name')->fetchAll();
    }

    // Grouped by state — the actual picker UI needs this, not a flat
    // 76-item list. See Proposal §10's UX note.
    public static function locationsByState(): array
    {
        $rows = getDB()->query('SELECT * FROM locations ORDER BY state, name')->fetchAll();
        $byState = [];
        foreach ($rows as $row) {
            $byState[$row['state']][] = $row;
        }
        return $byState;
    }

    public static function locations(): array
    {
        return getDB()->query('SELECT * FROM locations ORDER BY state, name')->fetchAll();
    }

    public static function addSkill(string $name, ?string $category): void
    {
        $stmt = getDB()->prepare('INSERT INTO skills (name, category) VALUES (?, ?)');
        $stmt->execute([$name, $category ?: null]);
    }

    public static function addLanguage(string $name): void
    {
        $stmt = getDB()->prepare('INSERT INTO languages (name) VALUES (?)');
        $stmt->execute([$name]);
    }

    public static function addCountry(string $name, string $isoCode): void
    {
        $stmt = getDB()->prepare('INSERT INTO countries (name, iso_code) VALUES (?, ?)');
        $stmt->execute([$name, strtoupper($isoCode)]);
    }

    public static function addService(string $name): void
    {
        $stmt = getDB()->prepare('INSERT INTO services (name) VALUES (?)');
        $stmt->execute([$name]);
    }

    public static function addLocation(string $name, string $state): void
    {
        $stmt = getDB()->prepare('INSERT INTO locations (name, state) VALUES (?, ?)');
        $stmt->execute([$name, $state]);
    }

    public static function deleteSkill(int $id): void
    {
        getDB()->prepare('DELETE FROM skills WHERE id = ?')->execute([$id]);
    }

    public static function deleteLanguage(int $id): void
    {
        getDB()->prepare('DELETE FROM languages WHERE id = ?')->execute([$id]);
    }

    public static function deleteCountry(int $id): void
    {
        getDB()->prepare('DELETE FROM countries WHERE id = ?')->execute([$id]);
    }

    public static function deleteService(int $id): void
    {
        getDB()->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
    }

    public static function deleteLocation(int $id): void
    {
        getDB()->prepare('DELETE FROM locations WHERE id = ?')->execute([$id]);
    }
}
