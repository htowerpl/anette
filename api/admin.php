<?php
// /home/opxwpceo/domains/anette.beauty/public_html/api/admin.php
session_start();

// --- KONFIGURACJA ---
// 1. Hasło administratora (ZMIEŃ JE NA SILNE!)
$PASSWORD = 'Anette2026!'; 

// 2. Wylogowanie
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php"); // Przekierowanie na ten sam plik
    exit;
}

// 3. Logowanie
if (isset($_POST['login'])) {
    if ($_POST['pass'] === $PASSWORD) {
        $_SESSION['api_admin_logged'] = true; // Unikalna nazwa sesji
        // Odśwież stronę, by wyczyścić POST
        header("Location: admin.php");
        exit;
    } else {
        $error = "Błędne hasło";
    }
}

// 4. Formularz logowania (jeśli nie zalogowany)
if (!isset($_SESSION['api_admin_logged'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="utf-8">
        <title>API Admin Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow"> <!-- Ważne: Nie indeksuj w Google -->
    </head>
    <body style="font-family:-apple-system, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; background:#f3f4f6; margin:0;">
        <form method="post" style="background:white; padding:40px; border-radius:12px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); width:100%; max-width:320px;">
            <div style="text-align:center; margin-bottom:20px;">
                <h2 style="margin:0; color:#111827;">Zarządzanie Treścią</h2>
                <p style="color:#6b7280; font-size:14px; margin-top:5px;">Anette Beauty API</p>
            </div>
            
            <div style="margin-bottom:15px;">
                <input type="password" name="pass" required placeholder="Hasło dostępu" autofocus
                       style="width:100%; padding:12px; border:1px solid #d1d5db; border-radius:6px; box-sizing:border-box; font-size:16px;">
            </div>
            
            <button type="submit" name="login" 
                    style="width:100%; padding:12px; background:#000; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-size:16px;">
                Zaloguj się
            </button>
            
            <?php if(isset($error)) echo "<p style='color:#ef4444; text-align:center; margin-top:15px; font-size:14px;'>$error</p>"; ?>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// --- CZĘŚĆ ADMINISTRACYJNA (DOSTĘPNA PO ZALOGOWANIU) ---

// Dołączamy połączenie z bazą (plik jest w tym samym folderze)
require_once 'db_connect.php'; 

// Obsługa formularza NEWS
if (isset($_POST['add_news'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO Anette_news (post_hash, date, title, content, image, link) VALUES (:hash, :date, :title, :content, :image, :link)");
        // Hash generujemy z treści i czasu, żeby był unikalny
        $stmt->execute([
            ':hash' => md5($_POST['content'] . time()),
            ':date' => $_POST['date'],
            ':title' => !empty($_POST['title']) ? $_POST['title'] : 'Aktualność',
            ':content' => $_POST['content'],
            ':image' => $_POST['image'],
            ':link' => $_POST['link']
        ]);
        $msg_success = "Pomyślnie dodano aktualność!";
    } catch(Exception $e) { $msg_error = "Błąd bazy: " . $e->getMessage(); }
}

// Obsługa formularza OPINII
if (isset($_POST['add_review'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO Anette_reviews (author, rating, text, review_date, source) VALUES (:author, :rating, :text, :date, 'google')");
        $stmt->execute([
            ':author' => $_POST['author'],
            ':rating' => $_POST['rating'],
            ':text' => $_POST['text'],
            ':date' => $_POST['date']
        ]);
        $msg_success = "Pomyślnie dodano opinię!";
    } catch(Exception $e) { $msg_error = "Błąd bazy: " . $e->getMessage(); }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel Anette (API)</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color:#1f2937; }
        .container { max-width: 900px; margin: 0 auto; }
        .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom:20px; border-bottom:1px solid #e5e7eb; }
        .logout-btn { color: #dc2626; text-decoration: none; font-weight: 500; font-size: 14px; padding: 8px 16px; border: 1px solid #fee2e2; border-radius: 6px; background: white; transition: all 0.2s; }
        .logout-btn:hover { background: #fee2e2; }
        
        .grid { display: grid; grid-template-columns: 1fr; gap: 30px; }
        @media (min-width: 768px) { .grid { grid-template-columns: 1fr 1fr; } }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
        h2 { margin-top: 0; font-size: 18px; color: #111827; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: #374151; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: border-color 0.2s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #000; ring: 1px solid #000; }
        
        textarea { resize: vertical; min-height: 100px; }
        
        button.submit-btn { width: 100%; padding: 12px; background: #111827; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        button.submit-btn:hover { background: #000; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .json-link { text-decoration: none; color: inherit; font-weight: 600; opacity: 0.8; }
        .json-link:hover { text-decoration: underline; opacity: 1; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav-bar">
        <div>
            <h1 style="margin:0; font-size:24px;">Panel Zarządzania</h1>
            <span style="color:#6b7280; font-size:14px;">Zalogowany jako Administrator</span>
        </div>
        <a href="?logout" class="logout-btn">Wyloguj</a>
    </div>

    <?php if(isset($msg_success)): ?>
        <div class="alert alert-success">
            <span>✅ <?php echo $msg_success; ?></span>
            <a href="get_news.php" target="_blank" class="json-link">Sprawdź JSON →</a>
        </div>
    <?php endif; ?>
    
    <?php if(isset($msg_error)): ?>
        <div class="alert alert-error">❌ <?php echo $msg_error; ?></div>
    <?php endif; ?>

    <div class="grid">
        <!-- SEKCJA AKTUALNOŚCI -->
        <div class="card">
            <h2>📢 Dodaj Aktualność</h2>
            <form method="post">
                <div class="form-group">
                    <label>Treść posta (Required)</label>
                    <textarea name="content" required placeholder="Wklej tutaj tekst..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Tytuł (Opcjonalny)</label>
                    <input type="text" name="title" placeholder="np. Promocja Walentynkowa">
                </div>

                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label>Data</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label>Link przycisku</label>
                        <input type="text" name="link" placeholder="https://...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Zdjęcie (URL)</label>
                    <input type="text" name="image" placeholder="Prawy klik na zdjęcie w Google -> Kopiuj adres grafiki">
                </div>

                <button type="submit" name="add_news" class="submit-btn">Opublikuj</button>
            </form>
        </div>

        <!-- SEKCJA OPINII -->
        <div class="card">
            <h2>⭐ Dodaj Opinię</h2>
            <form method="post">
                <div class="form-group">
                    <label>Treść opinii</label>
                    <textarea name="text" required placeholder="Treść recenzji..."></textarea>
                </div>

                <div class="form-group">
                    <label>Autor</label>
                    <input type="text" name="author" required placeholder="Imię i nazwisko">
                </div>

                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label>Ocena</label>
                        <select name="rating">
                            <option value="5" selected>★★★★★ (5)</option>
                            <option value="4">★★★★ (4)</option>
                            <option value="3">★★★ (3)</option>
                        </select>
                    </div>
                    <div>
                        <label>Data</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <button type="submit" name="add_review" class="submit-btn" style="background:#4b5563;">Zapisz Opinię</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>