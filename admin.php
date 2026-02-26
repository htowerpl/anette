<?php
// admin.php - Prosty CMS do aktualności
session_start();

// Wylogowanie
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Konfiguracja OAuth do generowania linku logowania
$configFile = '/home/opxwpceo/domains/google/config_oauth.php';
$config = file_exists($configFile) ? require $configFile : [];

// --- 1. EKRAN LOGOWANIA ---
if (empty($_SESSION['admin_logged_in'])) {
    if (empty($config)) die("Błąd: Brak konfiguracji OAuth na serwerze.");
    
    $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id'     => $config['client_id'],
        'redirect_uri'  => $config['redirect_uri'],
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online'
    ]);
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <title>Logowanie | Anette Admin</title>
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
            .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
            .btn { display: inline-block; padding: 10px 20px; background: #4285F4; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
            .btn:hover { background: #357ae8; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Panel Administratora</h2>
            <p>Zaloguj się, aby zarządzać aktualnościami.</p>
            <a href="<?php echo $authUrl; ?>" class="btn">Zaloguj się przez Google</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- 2. OBSŁUGA FORMULARZA (DODAWANIE) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConfig = require '/home/opxwpceo/domains/google/config_db.php';
    try {
        $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4", $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO Anette_news (`date`, `title`, `content`, `image`, `link`) VALUES (:date, :title, :content, :image, :link)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':date'    => $_POST['date'],
            ':title'   => $_POST['title'],
            ':content' => $_POST['content'],
            ':image'   => $_POST['image'] ?: null,
            ':link'    => $_POST['link'] ?: null
        ]);
        $message = "<div class='success'>Aktualność dodana pomyślnie!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>Błąd bazy danych: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admina | Anette</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        h1 { margin: 0; font-size: 1.5rem; }
        .logout { color: #d9534f; text-decoration: none; font-size: 0.9rem; }
        form { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: grid; gap: 1rem; }
        label { font-weight: bold; display: block; margin-bottom: 0.3rem; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { min-height: 150px; }
        button { background: #28a745; color: white; border: none; padding: 12px; font-size: 1rem; cursor: pointer; border-radius: 4px; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
        .hint { font-size: 0.85rem; color: #666; margin-top: -0.5rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Anette Beauty - Admin</h1>
            <small>Zalogowano jako: <?php echo htmlspecialchars($_SESSION['admin_email']); ?></small>
        </div>
        <a href="?logout=1" class="logout">Wyloguj się</a>
    </header>

    <?php echo $message; ?>

    <form method="post">
        <h2>Dodaj nową aktualność</h2>
        
        <div>
            <label for="date">Data publikacji</label>
            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div>
            <label for="title">Tytuł (opcjonalnie)</label>
            <input type="text" id="title" name="title" placeholder="Np. Nowy zabieg w ofercie">
        </div>

        <div>
            <label for="content">Treść</label>
            <textarea id="content" name="content" required placeholder="Treść aktualności..."></textarea>
        </div>

        <div>
            <label for="image">Link do zdjęcia (URL)</label>
            <input type="url" id="image" name="image" placeholder="https://...">
            <p class="hint">Możesz wkleić link do zdjęcia z Facebooka/Instagrama lub zostawić puste.</p>
        </div>

        <div>
            <label for="link">Link przycisku (opcjonalnie)</label>
            <input type="text" id="link" name="link" placeholder="Np. tel:123456789 lub https://...">
            <p class="hint">Wpisz numer telefonu (np. 123456789) aby dodać przycisk "Zadzwoń".</p>
        </div>

        <button type="submit">Opublikuj</button>
    </form>

    <p style="text-align: center; margin-top: 2rem;">
        <a href="/" target="_blank">Wróć na stronę główną</a>
    </p>
</body>
</html>