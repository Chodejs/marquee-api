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
    // In production, log this, don't echo it. But for us...
    die("Emma says: Database connection failed. " . $e->getMessage());
}
?>