<?php
// /home/opxwpceo/domains/anette.beauty/public_html/api/db_connect.php

// Wczytujemy konfigurację z bezpiecznego katalogu
// Używamy bezwzględnej ścieżki do pliku ze zdjęcia
$config = require '/home/opxwpceo/domains/google/config_db.php';

try {
    $dsn = "mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $conn = new PDO($dsn, $config['user'], $config['pass'], $options);

} catch (PDOException $e) {
    // W razie błędu zwracamy JSON, ale bez ujawniania szczegółów
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(500);
    echo json_encode(["error" => "Database connection error"]);
    exit;
}
?>