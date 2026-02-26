<?php
// api/run_token_setup.php
// Ten plik służy jako bezpieczny "uruchamiacz" dla skryptu generującego token.
// Po użyciu, ten plik (run_token_setup.php) powinien zostać usunięty z serwera.

// Ścieżka do właściwego skryptu w bezpiecznym katalogu
$setupScriptPath = '/home/opxwpceo/domains/google/setup_google_token.php';

if (file_exists($setupScriptPath)) {
    // Uruchom skrypt z bezpiecznej lokalizacji
    require $setupScriptPath;
} else {
    // Wyświetl błąd, jeśli skrypt nie został znaleziony
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die("Krytyczny błąd: Nie można zlokalizować skryptu `setup_google_token.php` w bezpiecznym katalogu.");
}