<?php
// ../db/config.php

// Set timezone to Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Load environment variables
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove quotes if present
        $value = trim($value, '"\'');

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
    return true;
}

// Load .env file from project root
$envLoaded = loadEnv(__DIR__ . '/../../.env');

function getDbConnection()
{
    // Use environment variables if loaded, otherwise fallback to defaults
    $host = $_ENV['DB_HOST'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $db = $_ENV['DB_NAME'] ?? '';

    // Gunakan objek MySQLi
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        // Log error, tidak ditampilkan langsung ke pengguna akhir
        error_log("Koneksi database gagal: " . $conn->connect_error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal. Silakan coba lagi nanti.']);
        exit;
    }

    $conn->set_charset("utf8mb4");

    // Set MySQL timezone to Asia/Jakarta
    $conn->query("SET time_zone = '+07:00'");

    return $conn;
}
