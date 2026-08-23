<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'pickleball_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Failed: " . $e->getMessage());
            die("Database Connection Error. Please try again later.");
        }
    }
    return $pdo;
}

// Utility CSRF helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && $token && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}