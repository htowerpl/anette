<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

function sendError($message) {
    echo json_encode(['error' => $message]);
    exit;
}

// Konfiguracja połączenia z bazą danych
$configFile = '/home/opxwpceo/domains/google/config_db.php';

if (!file_exists($configFile)) {
    sendError('Brak pliku konfiguracyjnego bazy danych');
}

$config = require $configFile;

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
    sendError('Błąd połączenia z bazą danych');
}

try {
    // Pobieramy dane mapując je na strukturę oczekiwaną przez frontend.
    // Używamy backticks `Columns`, ponieważ to słowo kluczowe w SQL.
    // Wybieramy tylko te kolumny, które są wykorzystywane przez JS.
    // Dodajemy backticks również do nazw kolumn (szczególnie `date`).
    $sql = "SELECT `date`, `title`, `content`, `image`, `link` FROM `Columns` ORDER BY `date` DESC LIMIT 20";
    $stmt = $pdo->query($sql);
    $news = $stmt->fetchAll();

    if (!is_array($news)) {
        $news = [];
    }

    echo json_encode($news);
} catch (\PDOException $e) {
    sendError('Błąd pobierania danych: ' . $e->getMessage());
}
?>