<?php
// api/find_google_ids.php
// Narzędzie do znajdowania Account ID i Location ID

ini_set('display_errors', 1);
error_reporting(E_ALL);

$configFile = '/home/opxwpceo/domains/google/config_oauth.php';

if (!file_exists($configFile)) {
    die("Błąd: Nie znaleziono pliku konfiguracji na serwerze: $configFile");
}
$config = require $configFile;

// --- Funkcje pomocnicze ---
function getAccessToken($config) {
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenParams = [
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $config['refresh_token'],
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
        echo "<h2>Błąd odświeżania tokena</h2><pre>" . htmlspecialchars(print_r($tokenData, true)) . "</pre>";
        return null;
    }
    return $tokenData['access_token'];
}

function renderResponse($title, $data) {
    echo "<h2>" . htmlspecialchars($title) . "</h2>";
    if (empty($data)) {
        echo "<p>Brak danych.</p>";
    } else {
        echo "<pre style='background:#f0f0f0; padding:15px; border:1px solid #ccc; white-space:pre-wrap; word-wrap:break-word;'>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    }
}

$accessToken = getAccessToken($config);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wyszukiwarka ID Google</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 900px; margin: 0 auto; padding: 20px; }
        .step { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        h1, h2 { margin-top: 0; }
        code { background: #e0e0e0; padding: 2px 5px; border-radius: 3px; }
        strong { color: #c00; }
    </style>
</head>
<body>
    <h1>Wyszukiwarka ID Konta i Lokalizacji Google</h1>
    <p>Ten skrypt pomoże Ci znaleźć brakujące <code>account_id</code> i <code>location_id</code>.</p>
    <p style="color:red; font-weight:bold;">Po zakończeniu, usuń ten plik z serwera!</p>

    <?php if ($accessToken): ?>
        <div class="step">
            <h2>Krok 1: Znajdź `account_id`</h2>
            <p>Poniżej znajduje się lista kont Google powiązanych z Twoim tokenem. Znajdź swoje konto (np. "Anette Beauty") i skopiuj <strong>tylko ciąg cyfr</strong> z pola <code>name</code> (np. <code>accounts/<strong>116...</strong></code>).</p>
            <?php
                $accountsUrl = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
                $ch = curl_init($accountsUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
                $response = curl_exec($ch);
                curl_close($ch);
                renderResponse("Odpowiedź z Google (Konta):", json_decode($response, true));
            ?>
            <p>Wklej skopiowany <code>account_id</code> do paska adresu i wciśnij Enter, np. <code>.../find_google_ids.php?account_id=<strong>TUTAJ_WKLEJ_ID</strong></code></p>
        </div>

        <?php if (!empty($_GET['account_id'])):
            $accountId = htmlspecialchars(trim($_GET['account_id']));
        ?>
            <div class="step">
                <h2>Krok 2: Znajdź `location_id` dla konta `<?php echo $accountId; ?>`</h2>
                <p>Poniżej znajduje się lista Twoich wizytówek (lokalizacji). Znajdź właściwą i skopiuj <strong>tylko ciąg cyfr</strong> z pola <code>name</code> (np. <code>locations/<strong>123...</strong></code>). To jest Twoje <code>location_id</code>.</p>
                <?php
                    $locationsUrl = "https://mybusinessbusinessinformation.googleapis.com/v1/accounts/{$accountId}/locations";
                    $ch = curl_init($locationsUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    renderResponse("Odpowiedź z Google (Lokalizacje):", json_decode($response, true));
                ?>
            </div>

            <div class="step">
                <h2>Krok 3: Zaktualizuj plik konfiguracyjny</h2>
                <p>Zaloguj się na serwer (FTP/SSH) i edytuj plik: <code><?php echo $configFile; ?></code></p>
                <p>Dodaj na końcu (wewnątrz tablicy) dwie brakujące linie:</p>
<pre style='background:#f0f0f0; padding:15px; border:1px solid #ccc;'>
'account_id'  => '<strong>TWOJE_ACCOUNT_ID_Z_KROKU_1</strong>',
'location_id' => '<strong>TWOJE_LOCATION_ID_Z_KROKU_2</strong>',
</pre>
                <p>Po zapisaniu pliku, spróbuj ponownie uruchomić skrypt importujący: <a href="import_google_news.php" target="_blank">import_google_news.php</a></p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="step" style="border-color: red;">
            <h2>Błąd Krytyczny</h2>
            <p>Nie udało się uzyskać tokena dostępu. Możliwe, że Twój <code>refresh_token</code> również wygasł. Spróbuj najpierw go odnowić za pomocą skryptu <code>run_token_setup.php</code>.</p>
        </div>
    <?php endif; ?>

</body>
</html>

```

### Instrukcja działania

1.  **Wgraj plik**: Umieść nowy plik `find_google_ids.php` w katalogu `api/` na swoim serwerze.
2.  **Uruchom Krok 1**: Wejdź w przeglądarce na adres:
    `https://anette.beauty/api/find_google_ids.php`
3.  **Skopiuj `account_id`**: W sekcji "Krok 1" zobaczysz odpowiedź z Google. Skopiuj sam numer z pola `name` (np. `116...`).
4.  **Uruchom Krok 2**: Wklej skopiowany numer do paska adresu przeglądarki, tak aby URL wyglądał następująco i wciśnij Enter:
    `https://anette.beauty/api/find_google_ids.php?account_id=TUTAJ_WKLEJ_ID`
5.  **Skopiuj `location_id`**: W nowo wyświetlonej sekcji "Krok 2" znajdź swoją wizytówkę i skopiuj numer z jej pola `name` (np. `123...`).
6.  **Zaktualizuj konfigurację**: Zaloguj się na serwer i edytuj plik `/home/opxwpceo/domains/google/config_oauth.php`. Dodaj na końcu (przed zamykającym `];`) dwie linie z odnalezionymi ID.
7.  **Posprzątaj**: **Usuń plik `find_google_ids.php` z serwera.**

Po wykonaniu tych kroków skrypt `import_google_news.php` powinien zadziałać poprawnie.

<!--
[PROMPT_SUGGESTION]Udało się! Import działa. Przejdźmy do formularza kontaktowego.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Jak ustawić zadanie CRON na serwerze, aby import uruchamiał się samoczynnie?[/PROMPT_SUGGESTION]
-->