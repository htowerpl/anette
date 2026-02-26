<?php
// /home/opxwpceo/domains/anette.beauty/public_html/api/sync_google.php
// Wersja: 3.0 (Wymuszanie Desktop + Nagłówki Chrome)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Dołączamy połączenie z bazą
if (!file_exists('db_connect.php')) {
    die("Błąd krytyczny: Brak pliku db_connect.php w folderze api/");
}
require_once 'db_connect.php';

// --- KONFIGURACJA ---
$fid = "5925660583659059698"; // Twój identyfikator Google
$debug_mode = true; 

// --- START ---
echo "<html><body style='font-family:sans-serif; padding:20px; line-height:1.6;'>";
echo "<h1>Importer Google (Tryb Desktop)</h1>";

// 1. POBIERANIE HTML (Mocna emulacja Chrome)
// Używamy search?q=...&ludocid=... co często wymusza panel boczny z postami
$url = "https://www.google.com/search?q=Gabinet+kosmetyczny+Anette+Gliwce&ludocid=" . $fid . "&hl=pl&gl=pl";

echo "<p>Cel: <strong>$url</strong></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Udajemy najnowszego Chroma na Windows (Sec-Ch-Ua nagłówki są kluczowe w 2025/2026)
$headers = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language: pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: max-age=0',
    'Sec-Ch-Ua: "Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
    'Sec-Ch-Ua-Mobile: ?0',     // <-- To mówi Google'owi: "NIE jestem telefonem"
    'Sec-Ch-Ua-Platform: "Windows"',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
    'Upgrade-Insecure-Requests: 1'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Ciasteczka (wirtualne, w pamięci)
curl_setopt($ch, CURLOPT_COOKIEFILE, ""); 

curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if (!$html || $http_code != 200) {
    echo "<p style='color:red;'><strong>BŁĄD POBIERANIA:</strong> HTTP $http_code</p>";
    if ($curl_error) echo "<p>Curl: $curl_error</p>";
    die("</body></html>");
}

echo "<p style='color:green;'>Pobrano HTML (" . strlen($html) . " bajtów). Analizuję...</p>";

// 2. PARSOWANIE
$dom = new DOMDocument();
libxml_use_internal_errors(true);
// Triki na kodowanie znaków (UTF-8)
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
$dom->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// Lista selektorów - od najbardziej specyficznych desktopowych
$queries = [
    "//div[contains(@data-attrid, 'kc:/local:updates')]//div[contains(@role, 'listitem')]", // Klasyczny Desktop
    "//div[contains(@class, 'W4EwCb')]", // Częsty kontener
    "//div[contains(@class, 'd7L4fc')]", 
    "//div[contains(@class, 'm6QErb')]//div[contains(@role, 'article')]" // Mobile/Maps fallback
];

$found_nodes = null;
foreach ($queries as $q) {
    $nodes = $xpath->query($q);
    if ($nodes->length > 0) {
        $found_nodes = $nodes;
        if ($debug_mode) echo "<p style='color:blue'>Dopasowano wzorzec: " . htmlspecialchars($q) . " (Liczba elementów: " . $nodes->length . ")</p>";
        break;
    }
}

$added_count = 0;

if ($found_nodes && $found_nodes->length > 0) {
    echo "<ul>";
    
    foreach ($found_nodes as $node) {
        // --- EKSTRAKCJA TEKSTU ---
        $text = "";
        
        // Szukamy tekstu w 'wiI7pd' (klasa recenzji/postów)
        $text_div = $xpath->query(".//div[contains(@class, 'wiI7pd')]", $node)->item(0);
        if ($text_div) {
            $text = trim($text_div->nodeValue);
        } else {
            // Fallback: Szukamy najdłuższego tekstu wewnątrz
            $divs = $xpath->query(".//div", $node);
            foreach ($divs as $d) {
                $t = trim($d->nodeValue);
                if (strlen($t) > 20 && strlen($t) < 1500) {
                    $text = $t;
                    break; 
                }
            }
        }

        if (empty($text)) continue;

        // --- EKSTRAKCJA ZDJĘCIA ---
        $image = "";
        // Metoda 1: img tag
        $imgs = $xpath->query(".//img", $node);
        if ($imgs->length > 0) {
            foreach ($imgs as $img) {
                $src = $img->getAttribute('data-src') ?: $img->getAttribute('src');
                if (strpos($src, 'http') === 0 && strlen($src) > 60) {
                    $image = $src;
                    break;
                }
            }
        }
        // Metoda 2: background-image (częste w Google)
        if (empty($image)) {
            $bg_divs = $xpath->query(".//div[contains(@style, 'background-image')]", $node);
            if ($bg_divs->length > 0) {
                $style = $bg_divs->item(0)->getAttribute('style');
                if (preg_match('/url\((.*?)\)/', $style, $m)) {
                    $image = trim($m[1], '"\'');
                }
            }
        }

        // --- ZAPIS ---
        $date = date('Y-m-d');
        $hash = md5($text); 

        try {
            $sql = "INSERT IGNORE INTO Anette_news (post_hash, date, title, content, image, link) 
                    VALUES (:hash, :date, :title, :content, :image, :link)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':hash' => $hash,
                ':date' => $date,
                ':title' => 'Aktualność',
                ':content' => $text,
                ':image' => $image,
                ':link' => "https://www.google.com/maps?cid=" . $fid
            ]);

            if ($stmt->rowCount() > 0) {
                echo "<li style='color:green'><strong>DODANO:</strong> " . substr($text, 0, 60) . "...</li>";
                $added_count++;
            } else {
                // if ($debug_mode) echo "<li style='color:#999'>Duplikat: " . substr($text, 0, 30) . "...</li>";
            }

        } catch (PDOException $e) {
            echo "<li style='color:red'>Błąd SQL: " . $e->getMessage() . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='background:#fff3cd; padding:15px; border:1px solid #ffeeba;'><strong>Brak wyników parsowania.</strong><br>Google prawdopodobnie zaserwowało stronę renderowaną JavaScriptem (pustą dla PHP).<br>Sugeruję użyć ręcznego dodawania danych przez SQL/Admin Panel.</p>";
}

if ($added_count > 0) {
    echo "<h3>SUKCES! Dodano $added_count postów.</h3>";
    echo "<a href='/api/get_news.php' target='_blank'>[Sprawdź JSON]</a>";
}

echo "</body></html>";
?>