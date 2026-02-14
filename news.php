<?php
header('Content-Type: application/json; charset=utf-8');

// Konfiguracja połączenia z bazą danych
$config = require '/home/opxwpceo/domains/google/config_db.php';

$host = $config['host'];
$db   = $config['name'];
$user = $config['user'];
$pass = $config['pass'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd połączenia z bazą danych']);
    exit;
}

try {
    // Pobieramy dane mapując je na strukturę oczekiwaną przez frontend.
    // Używamy backticks `Columns`, ponieważ to słowo kluczowe w SQL.
    // Wybieramy tylko te kolumny, które są wykorzystywane przez JS.
    $sql = "SELECT date, title, content, image, link FROM `Columns` ORDER BY date DESC LIMIT 20";
    $stmt = $pdo->query($sql);
    $news = $stmt->fetchAll();

    echo json_encode($news);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd pobierania danych: ' . $e->getMessage()]);
}
?>