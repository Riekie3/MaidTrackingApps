<?php
// Every page requires this first. Centralizes the require chain so
// individual pages don't have to know the load order.

require_once __DIR__ . '/../config/config.php';

if (!file_exists(__DIR__ . '/../config/database.php')) {
    die('Missing config/database.php — copy config/database.example.php and fill in your local MySQL credentials.');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

foreach (glob(__DIR__ . '/../models/*.php') as $modelFile) {
    require_once $modelFile;
}
