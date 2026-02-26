<?php
// api/import_google_news.php

// --- Konfiguracja i Inicjalizacja ---
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
header('Content-Type: application/json; charset=utf-8');

// --- Funkcje pomocnicze ---
function sendResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Wczytanie konfiguracji ---
$googleConfigFile = '/home/opxwpceo/domains/google/config_oauth.php';
$dbConfigFile = '/home/opxwpceo/domains/google/config_db.php';

if (!file_exists($googleConfigFile) || !file_exists($dbConfigFile)) {
    sendResponse(['error' => 'Brak plików konfiguracyjnych na serwerze (config_oauth.php lub config_db.php).']);
}
$googleConfig = require $googleConfigFile;
$dbConfig = require $dbConfigFile;

// --- Krok 1: Odświeżenie tokena dostępu Google ---
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenParams = [
    'client_id'     => $googleConfig['client_id'],
    'client_secret' => $googleConfig['client_secret'],
    'refresh_token' => $googleConfig['refresh_token'],
    'grant_type'    => 'refresh_token',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$tokenResponse = curl_exec($ch);
$tokenData = json_decode($tokenResponse, true);
curl_close($ch);

if (!isset($tokenData['access_token'])) {
    sendResponse(['error' => 'Nie udało się odświeżyć tokena dostępu.', 'details' => $tokenData]);
}
$accessToken = $tokenData['access_token'];

// --- Krok 2: Pobranie postów z Google Business Profile API ---
// UWAGA: Korzystamy z endpointu v4, który oficjalnie jest deprecated, ale wciąż działa dla localPosts
// przy odpowiednich uprawnieniach.
if (!isset($googleConfig['account_id']) || !isset($googleConfig['location_id'])) {
    sendResponse(['error' => 'Brak account_id lub location_id w pliku config_oauth.php na serwerze.']);
}

$accountId = $googleConfig['account_id'];
$locationId = $googleConfig['location_id'];
// Dodajemy prefiksy accounts/ i locations/ jeśli ich nie ma w configu (dla pewności)
$accPrefix = strpos($accountId, 'accounts/') === 0 ? '' : 'accounts/';
$locPrefix = strpos($locationId, 'locations/') === 0 ? '' : 'locations/';

$postsUrl = "https://mybusiness.googleapis.com/v4/{$accPrefix}{$accountId}/{$locPrefix}{$locationId}/localPosts";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $postsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
// Opcjonalnie: ignorowanie SSL (jeśli serwer ma stare certyfikaty CA)
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$postsResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$postsData = json_decode($postsResponse, true);

if ($httpCode !== 200) {
    $errorMsg = "Błąd podczas pobierania postów z Google API (HTTP {$httpCode}).";
    // Sprawdzenie czy API jest wyłączone (częsty błąd 403)
    if (isset($postsData['error']['details'][0]['reason']) && $postsData['error']['details'][0]['reason'] === 'SERVICE_DISABLED') {
        $errorMsg .= " API 'Google My Business API' jest wyłączone w Google Cloud Console. Musisz je włączyć w Bibliotece API.";
    }
    sendResponse(['error' => $errorMsg, 'details' => $postsData]);
}
$localPosts = $postsData['localPosts'] ?? [];

if (empty($localPosts)) {
    sendResponse(['status' => 'success', 'message' => 'Nie znaleziono postów do zaimportowania.']);
}

// --- Krok 3: Połączenie z bazą danych ---
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    sendResponse(['error' => 'Błąd połączenia z bazą danych.', 'details' => $e->getMessage()]);
}

// --- Krok 4: Przetwarzanie i zapisywanie postów do bazy ---
$sql = "INSERT INTO Anette_news_g (google_post_id, `date`, title, content, image, link) 
        VALUES (:google_post_id, :date, :title, :content, :image, :link)
        ON DUPLICATE KEY UPDATE 
            `date`=VALUES(`date`), title=VALUES(title), content=VALUES(content), image=VALUES(image), link=VALUES(link)";
$stmt = $pdo->prepare($sql);

$insertedCount = 0;
$updatedCount = 0;

foreach ($localPosts as $post) {
    // Pobieranie danych z nowej struktury JSON (lub starej, zależnie co zwróci API)
    $googlePostId = $post['name']; // Pełne ID np. accounts/.../localPosts/...
    
    $postDate = new DateTime($post['createTime'] ?? 'now');
    
    // Tytuł: Google rzadko zwraca tytuł, bierzemy go z eventu lub ucinamy treść
    $title = null;
    if (isset($post['topicType']) && $post['topicType'] === 'EVENT' && isset($post['event']['title'])) {
        $title = $post['event']['title'];
    }
    
    $content = $post['summary'] ?? '';
    
    // Zdjęcie: szukamy w media
    $image = null;
    if (isset($post['media']) && is_array($post['media'])) {
        foreach ($post['media'] as $mediaItem) {
            if (isset($mediaItem['mediaFormat']) && $mediaItem['mediaFormat'] === 'PHOTO') {
                $image = $mediaItem['googleUrl'];
                break; 
            }
        }
    }

    $link = $post['callToAction']['url'] ?? null;

    $params = [
        ':google_post_id' => $googlePostId,
        ':date'           => $postDate->format('Y-m-d'),
        ':title'          => $title,
        ':content'        => $content,
        ':image'          => $image,
        ':link'           => $link,
    ];

    $stmt->execute($params);
    $affectedRows = $stmt->rowCount();
    if ($affectedRows === 1) $insertedCount++;
    elseif ($affectedRows === 2) $updatedCount++; // ON DUPLICATE KEY UPDATE zwraca 2 dla aktualizacji
}

sendResponse([
    'status' => 'success', 'message' => 'Import zakończony.',
    'summary' => [
        'posts_found_in_api' => count($localPosts),
        'posts_inserted'     => $insertedCount,
        'posts_updated'      => $updatedCount,
    ]
]);