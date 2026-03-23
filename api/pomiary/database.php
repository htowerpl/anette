<?php
// Baza danych SQLite dla Aplikacji Pomiary 2.0
$db_file = __DIR__ . '/db/pomiary.sqlite';
$is_new_db = !file_exists($db_file);

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Zawsze sprawdzaj, czy tabele istnieją. Jeśli ktoś np. utworzył pusty plik "pomiary.sqlite" (0 bajtów), to file_exists zwracało true, a baza gubiła tabele.
    $db->exec("
        CREATE TABLE IF NOT EXISTS protokoly (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            obiekt_nazwa VARCHAR(255) NOT NULL,
            adres VARCHAR(255),
            data_pomiaru DATE NOT NULL,
            uklad_sieci VARCHAR(50) NOT NULL,
            napiecie_u0 INTEGER DEFAULT 230,
            inzynier_e VARCHAR(255),
            inzynier_d VARCHAR(255),
            uprawnienia_e VARCHAR(100),
            uprawnienia_d VARCHAR(100),
            pogoda VARCHAR(255),
            miernik_nazwa VARCHAR(255) DEFAULT '',
            miernik_wzorcowanie VARCHAR(255) DEFAULT '',
            data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Skrypt migracyjny dla starszych wersji bazy (dodanie kolumn, jeśli nie istnieją)
    try {
        $db->exec("ALTER TABLE protokoly ADD COLUMN miernik_nazwa VARCHAR(255) DEFAULT ''");
    } catch (PDOException $e) { }

    try {
        $db->exec("ALTER TABLE protokoly ADD COLUMN miernik_wzorcowanie VARCHAR(255) DEFAULT ''");
    } catch (PDOException $e) { }

    try {
        // Starsze wersje aplikacji mogły nie mieć data_utworzenia
        $db->exec("ALTER TABLE protokoly ADD COLUMN data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP");
    } catch (PDOException $e) { }

    $db->exec("
        CREATE TABLE IF NOT EXISTS pomiary_linie (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            protokol_id INTEGER NOT NULL,
            kategoria VARCHAR(50) NOT NULL, -- 'OGLEDZINY', 'RISO', 'SWZ', 'RCD'
            nr_formularza INTEGER,
            dane_json TEXT NOT NULL,
            FOREIGN KEY (protokol_id) REFERENCES protokoly(id) ON DELETE CASCADE
        )
    ");

    // Zabezpieczenie pliku bazy
    chmod($db_file, 0644);

}
catch (PDOException $e) {
    error_log('Pomiary DB - błąd połączenia: ' . $e->getMessage());
    die("Błąd połączenia z bazą danych.");
}
?>