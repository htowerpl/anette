<?php
// /home/opxwpceo/domains/anette.beauty/public_html/api/get_reviews.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db_connect.php';

try {
    // Pobierz 10 najnowszych opinii z oceną 4 lub 5
    // Sortowanie po dacie (review_date)
    $sql = "SELECT author, rating, text, review_date 
            FROM Anette_reviews 
            WHERE rating >= 4 
            ORDER BY review_date DESC 
            LIMIT 10";
            
    $stmt = $conn->query($sql);
    $reviews = $stmt->fetchAll();
    
    echo json_encode($reviews);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch reviews"]);
}
?>