<?php
// config/db.php
// Database configuration for the Association Management System

if (ob_get_level() == 0) {
    ob_start();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'association_management_db';

try {
    // 1. Attempt connection directly to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // 2. If connection failed, try connecting to the server to create it automatically
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Reconnect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Import schema.sql
        $schemaPath = dirname(__DIR__) . '/schema.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            // Execute the schema to create tables and insert seed data
            $pdo->exec($sql);
        }
    } catch (PDOException $ex) {
        die("<div style='padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; font-family: sans-serif; border-radius: 8px; margin: 20px auto; max-width: 600px;'>
            <h3 style='margin-top:0;'>Database Connection Error</h3>
            <p>Could not connect to or auto-initialize the database. Please ensure your local MySQL server (XAMPP/WAMP) is running.</p>
            <small><strong>Error Detail:</strong> " . htmlspecialchars($ex->getMessage()) . "</small>
        </div>");
    }
}
?>
