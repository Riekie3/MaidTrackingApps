<?php
require_once __DIR__ . '/includes/bootstrap.php';
logout();
redirect(rtrim(APP_URL, '/') . '/login.php');
