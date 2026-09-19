<?php
// Configuration parameters for PostgreSQL connection
$host = "localhost";
$port = "5432";
$dbname = "bookloop_db";
$user = "postgres";
$password = "postgres"; // Replace with your PostgreSQL password

try {
    // Construct PostgreSQL Connection DSN (Data Source Name)
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    
    // Instantiate PDO instance
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Output database connection error as JSON response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
?>