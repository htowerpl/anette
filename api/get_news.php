<?php
// /home/opxwpceo/domains/anette.beauty/public_html/api/get_news.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Dołączamy nasz plik łączący (który wczytuje bezpieczny config)
require_once 'db_connect.php';

try {
    // Pobierz 6 najnowszych aktualności
    // Wybieramy tylko potrzebne kolumny, żeby nie przesyłać śmieci
    $sql = "SELECT date, title, content, image, link 
            FROM Anette_news 
            ORDER BY date DESC 
            LIMIT 6";
            
    $stmt = $conn->query($sql);
    $news = $stmt->fetchAll();
    
    echo json_encode($news);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch news"]);
}
?>