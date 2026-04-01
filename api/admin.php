<?php
// api/admin.php - CMS do aktualności (Logowanie Google OAuth)
session_start();

// --- KONFIGURACJA ---
$oauthConfigPath = '/home/opxwpceo/domains/google/config_oauth.php';
$emailsConfigPath = '/home/opxwpceo/domains/google/config_emails.php';

if (!file_exists($emailsConfigPath)) {
    die("Błąd: Brak pliku konfiguracji e-maili na serwerze.");
}
$ALLOWED_EMAILS = require $emailsConfigPath;

// --- LOGIKA ---

require_once 'db_connect.php'; // Połączenie z bazą ($conn)

// Wylogowanie
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /api/admin.php');
    exit;
}

// Logowanie przez Google (OAuth)
if (empty($_SESSION['admin_logged_in'])) {
    if (!file_exists($oauthConfigPath)) {
        die("Błąd: Brak pliku konfiguracji OAuth na serwerze.");
    }
    $oauthConfig = require $oauthConfigPath;
    
    // Dynamiczne ustalenie Redirect URI (musi być DOKŁADNIE TAKI SAM dodany w Google Cloud Console)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $redirectUri = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    // KROK 2: Obsługa powrotu z Google (wymiana kodu na token)
    if (isset($_GET['code'])) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $params = [
            'code' => $_GET['code'],
            'client_id' => $oauthConfig['client_id'],
            'client_secret' => $oauthConfig['client_secret'],
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (!empty($data['access_token'])) {
            // Pobierz dane użytkownika (email) przez cURL
            $userUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $data['access_token'];
            $ch2 = curl_init($userUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
            $userInfo = json_decode(curl_exec($ch2), true);
            curl_close($ch2);
            
            if (!empty($userInfo['email']) && in_array($userInfo['email'], $ALLOWED_EMAILS)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $userInfo['email'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generuj token CSRF
                header('Location: ' . strtok($redirectUri, '?')); // Przekieruj na czysty URL (bez ?code=...)
                exit;
            } else {
                $loginError = "Brak dostępu dla adresu: " . htmlspecialchars($userInfo['email'] ?? 'nieznany');
            }
        } else {
            $loginError = "Błąd logowania Google (brak tokena).";
        }
    }

    // KROK 1: Generowanie linku do logowania
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $oauthConfig['client_id'],
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online'
    ]);
}

// Ekran logowania
if (empty($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="utf-8">
        <title>Logowanie | Anette Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; margin: 0; }
            .login-box { background: white; padding: 3rem 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 400px; }
            .google-btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; background: #fff; color: #757575; border: 1px solid #ddd; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: 500; font-family: Roboto, sans-serif; transition: background 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .google-btn:hover { background: #f8f8f8; box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
            .google-icon { width: 18px; height: 18px; }
            .error { color: red; font-size: 0.9rem; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2 style="margin-top:0; margin-bottom: 2rem;">Anette Beauty Admin</h2>
            
            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="google-btn">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="" class="google-icon">
                Zaloguj przez Google
            </a>

            <?php if(isset($loginError)) echo "<div class='error'>$loginError</div>"; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- PANEL CMS ---

$message = '';

// Obsługa formularza (Zapis / Usuwanie)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<div class='error'>Błąd bezpieczeństwa (CSRF). Odśwież stronę i spróbuj ponownie.</div>");
    }
    
    try {
        if (isset($_POST['delete_id'])) {
            // Usuwanie
            $stmt = $conn->prepare("DELETE FROM Anette_news WHERE id = :id");
            $stmt->execute([':id' => $_POST['delete_id']]);
            $message = "<div class='success'>Wpis usunięty.</div>";
        } else {
            // Zapis (Dodawanie lub Edycja)
            $id = !empty($_POST['id']) ? $_POST['id'] : null;
            $date = $_POST['date'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            $image = !empty($_POST['image']) ? $_POST['image'] : null;
            $link = !empty($_POST['link']) ? $_POST['link'] : null;

            if ($id) {
                // Aktualizacja
                $sql = "UPDATE Anette_news SET `date`=:date, `title`=:title, `content`=:content, `image`=:image, `link`=:link WHERE id=:id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':date' => $date, ':title' => $title, ':content' => $content, ':image' => $image, ':link' => $link, ':id' => $id]);
                $message = "<div class='success'>Wpis zaktualizowany!</div>";
            } else {
                // Nowy wpis
                // Generujemy unikalny hash (wymagany przez strukturę tabeli, np. md5 z treści i czasu)
                $post_hash = md5($content . microtime());
                $sql = "INSERT INTO Anette_news (`post_hash`, `date`, `title`, `content`, `image`, `link`) VALUES (:post_hash, :date, :title, :content, :image, :link)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':post_hash' => $post_hash, ':date' => $date, ':title' => $title, ':content' => $content, ':image' => $image, ':link' => $link]);
                $message = "<div class='success'>Nowy wpis dodany!</div>";
            }
        }
    } catch (PDOException $e) {
        error_log('Admin CMS - błąd zapisu: ' . $e->getMessage());
        $message = "<div class='error'>Wystąpił błąd podczas zapisu do bazy danych.</div>";
    }
}

// Pobranie listy wpisów
$newsList = [];
try {
    $stmt = $conn->query("SELECT * FROM Anette_news ORDER BY date DESC, id DESC");
    $newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Admin CMS - błąd odczytu: ' . $e->getMessage());
    $message = "<div class='error'>Nie można pobrać listy wpisów.</div>";
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admina | Anette</title>
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        h1 { margin: 0; font-size: 1.5rem; }
        .logout { color: #d9534f; text-decoration: none; font-size: 0.9rem; }
        .user-info { font-size: 0.85rem; color: #666; margin-right: 1rem; }
        .container { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
        @media (max-width: 800px) { .container { grid-template-columns: 1fr; } .list-panel { order: 2; } }

        form { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: grid; gap: 1rem; }
        label { font-weight: bold; display: block; margin-bottom: 0.3rem; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { min-height: 150px; }
        button { background: #28a745; color: white; border: none; padding: 12px; font-size: 1rem; cursor: pointer; border-radius: 4px; }
        button:hover { background: #218838; }
        button.reset { background: #6c757d; margin-top: 10px; }
        button.delete { background: #dc3545; margin-top: 20px; }
        
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
        .hint { font-size: 0.85rem; color: #666; margin-top: -0.5rem; margin-bottom: 0.5rem; }

        .list-panel { background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); max-height: 800px; overflow-y: auto; }
        .news-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; display: flex; gap: 10px; align-items: flex-start; }
        .news-item:hover { background: #f0f8ff; }
        .news-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; background: #f0f0f0; flex-shrink: 0; }
        .news-item div { flex: 1; min-width: 0; }
        .news-item small { color: #888; display: block; }
        .news-item strong { display: block; font-size: 0.95rem; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Anette Beauty - Admin</h1>
        </div>
        <div>
            <span class="user-info"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?></span>
            <a href="?logout=1" class="logout">Wyloguj się</a>
        </div>
    </header>

    <?php echo $message; ?>

    <div class="container">
        <!-- FORMULARZ -->
        <div class="form-panel">
            <form method="post" id="newsForm">
                <h2 id="formTitle">Dodaj nową aktualność</h2>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="id" id="id">
                
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

                <button type="submit" id="submitBtn">Opublikuj</button>
                <button type="button" class="reset" onclick="resetForm()">Wyczyść / Nowy wpis</button>
            </form>

            <form method="post" id="deleteForm" style="display:none; margin-top:0; padding:0; box-shadow:none;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="delete_id" id="delete_id">
                <button type="submit" class="delete" onclick="return confirm('Czy na pewno usunąć ten wpis?')">Usuń ten wpis</button>
            </form>
        </div>

        <!-- LISTA WPISÓW -->
        <div class="list-panel">
            <h3>Lista wpisów (kliknij aby edytować)</h3>
            <?php foreach ($newsList as $news): ?>
                <div class="news-item" onclick='loadNews(<?php echo json_encode($news, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                    <?php if (!empty($news['image'])): ?>
                        <img src="<?php echo htmlspecialchars($news['image']); ?>" alt="">
                    <?php endif; ?>
                    <div>
                        <small><?php echo $news['date']; ?></small>
                        <strong><?php echo htmlspecialchars($news['title'] ?: '(Bez tytułu)'); ?></strong>
                        <span style="font-size:0.8rem; color:#666;"><?php echo mb_substr(strip_tags($news['content']), 0, 50) . '...'; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function loadNews(data) {
            document.getElementById('id').value = data.id;
            document.getElementById('date').value = data.date;
            document.getElementById('title').value = data.title || '';
            document.getElementById('content').value = data.content;
            document.getElementById('image').value = data.image || '';
            document.getElementById('link').value = data.link || '';
            
            document.getElementById('formTitle').innerText = "Edytuj wpis (ID: " + data.id + ")";
            document.getElementById('submitBtn').innerText = "Zapisz zmiany";
            
            // Pokaż przycisk usuwania
            document.getElementById('deleteForm').style.display = 'block';
            document.getElementById('delete_id').value = data.id;
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('newsForm').reset();
            document.getElementById('id').value = '';
            document.getElementById('date').value = new Date().toISOString().split('T')[0];
            document.getElementById('formTitle').innerText = "Dodaj nową aktualność";
            document.getElementById('submitBtn').innerText = "Opublikuj";
            document.getElementById('deleteForm').style.display = 'none';
        }
    </script>

    <p style="text-align: center; margin-top: 2rem;">
        <a href="/" target="_blank">Wróć na stronę główną</a>
    </p>
</body>
</html>