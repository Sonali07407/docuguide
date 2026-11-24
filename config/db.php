<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'docuguide_db';
$db_port = intval(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit("Service temporarily unavailable. Please try again later.");
}

$conn->set_charset("utf8mb4");