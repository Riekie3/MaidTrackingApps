<?php
// Copy this file to config/database.php and adjust for your local XAMPP
// install or production cPanel database. config/database.php is
// gitignored — never commit real credentials.

define('DB_HOST', 'localhost');
define('DB_NAME', 'maidtrack');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Database connection failed. Check config/database.php credentials and that MySQL is running. (' . $e->getMessage() . ')');
        }
    }

    return $pdo;
}
