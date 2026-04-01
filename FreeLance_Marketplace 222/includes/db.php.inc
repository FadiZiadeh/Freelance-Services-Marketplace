<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$DB_HOST = "localhost";
$DB_NAME = "web1232120_FreeLance_MarketPlace";
$DB_USER = "web1232120_dbuser";
$DB_PASS = "dChJ*VQu74"; // change if needed

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // In production you would log this; for university project it's fine to show a simple message.
    die("Database connection failed.");
}
