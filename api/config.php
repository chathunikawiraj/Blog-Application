<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Load environment variables from .env file 
$env = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($env as $line) {
    // Only process lines that actually contain an equals sign
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'blog_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>