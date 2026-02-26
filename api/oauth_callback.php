<?php
// oauth_callback.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Konfiguracja
$configFile = '/home/opxwpceo/domains/google/config_oauth.php';
if (!file_exists($configFile)) die("Błąd: Brak pliku konfiguracyjnego na serwerze.");
$config = require $configFile;

// LISTA DOZWOLONYCH E-MAILI (Administratorów)
// Zmień poniższy adres na swój e-mail Google!
$allowedEmails = [
    'aneta.szachniewicz@gmail.com', 
    'opxwpceo@gmail.com'
];

if (isset($_GET['code'])) {
    // 2. Wymiana kodu na token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri'  => $config['redirect_uri'],
        'grant_type'    => 'authorization_code'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['access_token'])) {
        // 3. Pobranie danych użytkownika (e-mail)
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userInfo = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $userEmail = $userInfo['email'] ?? '';

        // 4. Weryfikacja uprawnień
        if (in_array($userEmail, $allowedEmails)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $userEmail;
            header('Location: /api/admin.php');
            exit;
        } else {
            die("Błąd: Brak uprawnień dla adresu: " . htmlspecialchars($userEmail));
        }
    } else {
        die("Błąd logowania Google: " . htmlspecialchars(print_r($response, true)));
    }
} else {
    header('Location: /api/admin.php');
}