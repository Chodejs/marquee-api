<?php
// marquee-api/config.php

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'marquee');
define('DB_USER', 'root');
define('DB_PASS', 'mysql');


try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Return arrays indexed by column name
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Emma says: Database connection failed. " . $e->getMessage());
}

// --- SECURITY HELPER ---
function requireAuth($data) {
    // If no user_id is provided, kick them out.
    if (!isset($data->user_id) || empty($data->user_id)) {
        http_response_code(401); // Unauthorized
        echo json_encode(["message" => "Emma says: You must be logged in to make changes."]);
        exit();
    }
}
?>