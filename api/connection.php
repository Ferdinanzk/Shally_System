<?php
// Vercel Environment: Use environment variables
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];
$port = 3306; // Standard MySQL port

// Create connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Set character set
$conn->set_charset("utf8mb4");

// The connection check is handled by mysqli_report, which will throw an exception on failure.
?>