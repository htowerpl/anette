<?php
// Aplikacja: Pomiary 2.0 (Monolit)
// Przechowywanie w lokalnej bazie SQLite
require_once __DIR__ . '/database.php';

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$success_msg = '';

if ($is_submitted) {
    $edycja_id = (int)($_POST['edycja_id'] ?? 0);
    // Odczyt Danych Nagłówkowych
    $obiekt_nazwa = htmlspecialchars($_POST['obiekt_nazwa'] ?? '');
    $adres = htmlspecialchars($_POST['adres'] ?? '');
    $uklad_sieci = htmlspecialchars($_POST['uklad_sieci'] ?? 'TN-C-S');
    $napiecie_u0 = (int)($_POST['napiecie_u0'] ?? 230);
    $data_pomiaru = htmlspecialchars($_POST['data_pomiaru'] ?? date('Y-m-d'));

    $inzynier_e = htmlspecialchars($_POST['inzynier_e'] ?? '');
    $uprawnienia_e = htmlspecialchars($_POST['uprawnienia_e'] ?? '');
    $inzynier_d = htmlspecialchars($_POST['inzynier_d'] ?? '');
    $uprawnienia_d = htmlspecialchars($_POST['uprawnienia_d'] ?? '');
    $pogoda = htmlspecialchars($_POST['pogoda'] ?? '');

    // Mierniki
    $mierniki_json = $_POST['mierniki_data'] ?? '[]';

    // Oględziny
    $ogledziny_json = $_POST['ogledziny_data'] ?? '[]';
    // Rezystancja Izolacji
    $riso_json = $_POST['riso_data'] ?? '[]';
    // SWZ (Pętla zwarcia)
    $swz_json = $_POST['swz_data'] ?? '[]';
    // RCD
    $rcd_json = $_POST['rcd_data'] ?? '[]';

    // Transaction begin
    $db->beginTransaction();
    try {
        if ($edycja_id > 0) {
            $stmt = $db->prepare("
                UPDATE protokoly SET 
                obiekt_nazwa=?, adres=?, data_pomiaru=?, uklad_sieci=?, napiecie_u0=?, 
                inzynier_e=?, uprawnienia_e=?, inzynier_d=?, uprawnienia_d=?, pogoda=?,
                miernik_nazwa=?, miernik_wzorcowanie=?
                WHERE id=?
            ");
            $stmt->execute([
                $obiekt_nazwa, $adres, $data_pomiaru, $uklad_sieci, $napiecie_u0,
                $inzynier_e, $uprawnienia_e, $inzynier_d, $uprawnienia_d, $pogoda,
                $miernik_nazwa, $miernik_wzorcowanie, $edycja_id
            ]);
            $protokol_id = $edycja_id;

            // Delete old lines
            $db->prepare("DELETE FROM pomiary_linie WHERE protokol_id = ?")->execute([$protokol_id]);
            $success_msg = "Pomyślnie zaktualizowano Protokół ID: $protokol_id w bazie danych!";
        }
        else {
            $stmt = $db->prepare("
                INSERT INTO protokoly 
                (obiekt_nazwa, adres, data_pomiaru, uklad_sieci, napiecie_u0, inzynier_e, uprawnienia_e, inzynier_d, uprawnienia_d, pogoda, miernik_nazwa, miernik_wzorcowanie) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $obiekt_nazwa, $adres, $data_pomiaru, $uklad_sieci, $napiecie_u0,
                $inzynier_e, $uprawnienia_e, $inzynier_d, $uprawnienia_d, $pogoda,
                $miernik_nazwa, $miernik_wzorcowanie
            ]);
            $protokol_id = $db->lastInsertId();
            $success_msg = "Pomyślnie utworzono i zapisano Protokół ID: $protokol_id w bazie danych!";
        }

        $insert_linie = $db->prepare("INSERT INTO pomiary_linie (protokol_id, kategoria, dane_json) VALUES (?, ?, ?)");

        // Oględziny
        $insert_linie->execute([$protokol_id, 'OGLEDZINY', $ogledziny_json]);
        // RISO
        $insert_linie->execute([$protokol_id, 'RISO', $riso_json]);
        // SWZ
        $insert_linie->execute([$protokol_id, 'SWZ', $swz_json]);
        // Mierniki
        $insert_linie->execute([$protokol_id, 'MIERNIKI', $mierniki_json]);
        // RCD
        $insert_linie->execute([$protokol_id, 'RCD', $rcd_json]);

        $db->commit();

        // Pozostaw w trybie edycji po zapisie (nie ładuj od razu widoku podglądu)
        $edit_protokol_id = $protokol_id;

    }
    catch (Exception $e) {
        $db->rollBack();
        die("Błąd zapisu do bazy: " . $e->getMessage());
    }
}
else {
    // Sprawdzanie czy ładujemy stary (opcjonalnie kiedys ?view=ID)
    $render_protokol_id = $_GET['view'] ?? null;
}

// Obsługa Kasowania Rekordów z Archiwum
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM pomiary_linie WHERE protokol_id = ?")->execute([$delete_id]);
        $db->prepare("DELETE FROM protokoly WHERE id = ?")->execute([$delete_id]);
        $db->commit();
        $success_msg = "Protokół ID: $delete_id został trwale usunięty z bazy.";
        // Reset id żeby powrócił do trybu startowego po restarcie
        $edit_protokol_id = null;
        $render_protokol_id = null;
    } catch (Exception $e) {
        $db->rollBack();
        die("Błąd usuwania rekordu: " . $e->getMessage());
    }
}

// Sprawdzanie czy wczytujemy dane do edycji
$edit_protokol_id = $_GET['edit'] ?? ($edit_protokol_id ?? null);
$loaded_data = null;

function getLines($db, $pid, $cat)
{
    $s = $db->prepare("SELECT dane_json FROM pomiary_linie WHERE protokol_id = ? AND kategoria = ?");
    $s->execute([$pid, $cat]);
    $res = $s->fetch();
    return $res ? json_decode($res['dane_json'], true) : [];
}

if ($edit_protokol_id) {
    $stmt = $db->prepare("SELECT * FROM protokoly WHERE id = ?");
    $stmt->execute([$edit_protokol_id]);
    $prot = $stmt->fetch();
    if ($prot) {
        $loaded_data = [
            'naglowek' => $prot,
            'ogledziny' => getLines($db, $edit_protokol_id, 'OGLEDZINY'),
            'riso' => getLines($db, $edit_protokol_id, 'RISO'),
            'swz' => getLines($db, $edit_protokol_id, 'SWZ'),
            'rcd' => getLines($db, $edit_protokol_id, 'RCD'),
            'mierniki' => getLines($db, $edit_protokol_id, 'MIERNIKI')
        ];
    }
}

// Jeśli wczytujemy podgląd ze świeżego zapisu lub GET, ładujemy z BD z pięknym widokiem
if ($render_protokol_id) {
    // Renderowanie PDFa (Backend-view)
    $stmt = $db->prepare("SELECT * FROM protokoly WHERE id = ?");
    $stmt->execute([$render_protokol_id]);
    $protokol = $stmt->fetch();
    if (!$protokol) {
        die("Nie znaleziono protokołu");
    }

    $ogledziny_lines = getLines($db, $render_protokol_id, 'OGLEDZINY');
    $riso_lines = getLines($db, $render_protokol_id, 'RISO');
    $swz_lines = getLines($db, $render_protokol_id, 'SWZ');
    $rcd_lines = getLines($db, $render_protokol_id, 'RCD');
    $mierniki_lines = getLines($db, $render_protokol_id, 'MIERNIKI');

    echo "<!DOCTYPE html><html lang='pl'><head><meta charset='UTF-8'><title>Protokół #{$render_protokol_id}</title>";
    echo "<style>
            body { font-family: 'Times New Roman', serif; line-height: 1.4; margin: 40px; color: #333; font-size: 12pt; }
            h1, h2, h3 { text-align: center; }
            .header-info { margin-bottom: 20px; padding: 15px; border: 1px solid #000; background-color: #f9f9f9; }
            .header-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; page-break-inside: auto; }
            th, td { border: 1px solid #000; padding: 5px; text-align: center; }
            th { background-color: #ddd; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            .pozytywny { color: green; font-weight: bold; }
            .negatywny { color: red; font-weight: bold; }
            .sekcja { page-break-before: always; }
            .sekcja:first-of-type { page-break-before: auto; }
            .wzor { font-family: 'Courier New', monospace; font-size: 0.85em; background: #eee; padding: 2px 5px; border-radius: 3px; display: inline-block;}
            .signatures { margin-top: 20px; margin-bottom: 30px; display: flex; justify-content: space-around; page-break-inside: avoid;}
            .sig-box { border-top: 1px solid #000; width: 40%; text-align: center; padding-top: 5px; font-size: 10pt; }
            @media print {
                .no-print { display: none; }
                @page { size: A4; margin: 15mm; }
                body { margin: 0; padding: 0; }
            }
          </style></head><body>";

    echo "<button class='no-print' onclick='window.print()' style='padding: 10px 20px; margin-bottom: 20px; cursor:pointer; background:#2ecc71; color:white; border:none; border-radius:3px;'>Drukuj Protokół</button>";
    echo "<a href='index.php?edit={$render_protokol_id}' class='no-print' style='margin-left: 15px; padding: 10px 20px; background: #3498db; color:white; border:none; border-radius:3px; text-decoration:none;'>🡄 Powrót do edycji</a>";
    echo "<a href='index.php' class='no-print' style='margin-left: 15px; padding: 10px 20px; background: #eee; border: 1px solid #ccc; text-decoration:none; color:#000;'>Nowy Dokument</a>";

    if ($success_msg) {
        echo "<div class='no-print' style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; font-weight: bold;'>$success_msg</div>";
    }

    // Szablon Podpisów E/D doczepiany pod tabelami
    $html_podpisy = "<div class='signatures'>";
    if (trim($protokol['inzynier_e']) === trim($protokol['inzynier_d']) && !empty($protokol['inzynier_e'])) {
        $html_podpisy .= "<div style='width: 40%;'></div>"; // Wypełniacz po lewej, spychanie na prawo
        $html_podpisy .= "<div class='sig-box'>Osoba wykonująca i zatwierdzająca pomiary<br><strong>" . htmlspecialchars($protokol['inzynier_e']) . "</strong><br>Uprawnienia:<br>" . htmlspecialchars($protokol['uprawnienia_e']) . "<br>" . htmlspecialchars($protokol['uprawnienia_d']) . "<br><br><br></div>";
    } else {
        $html_podpisy .= "<div class='sig-box'>Osoba wykonująca pomiary<br><strong>" . htmlspecialchars($protokol['inzynier_e']) . "</strong><br>Uprawnienia:<br>" . htmlspecialchars($protokol['uprawnienia_e']) . "<br><br><br></div>
              <div class='sig-box'>Osoba sprawdzająca/zatwierdzająca<br><strong>" . htmlspecialchars($protokol['inzynier_d']) . "</strong><br>Uprawnienia:<br>" . htmlspecialchars($protokol['uprawnienia_d']) . "<br><br><br></div>";
    }
    $html_podpisy .= "</div>";

    // NAGŁÓWEK PROTOKOŁU 
    echo "<h1>PROTOKÓŁ Z POMIARÓW ELEKTRYCZNYCH</h1>";
    echo "<div class='header-info'>";
    echo "<div class='header-grid'>";
    echo "<div><strong>Obiekt:</strong> {$protokol['obiekt_nazwa']}<br><strong>Adres:</strong> {$protokol['adres']}<br><strong>Data pomiaru:</strong> {$protokol['data_pomiaru']}<br><strong>Warunki środowiskowe:</strong> {$protokol['pogoda']}</div>";
    echo "<div><strong>Układ sieciowy zasilający:</strong> {$protokol['uklad_sieci']}<br><strong>Napięcie nominalne fazowe U<sub>0</sub>:</strong> {$protokol['napiecie_u0']} V<br></div>";
    echo "</div>";
    echo "<hr style='margin:10px 0;'>";
    if (trim($protokol['inzynier_e']) === trim($protokol['inzynier_d']) && !empty($protokol['inzynier_e'])) {
        echo "<div><strong>Wykonał i Sprawdził (E+D):</strong> {$protokol['inzynier_e']}<br>Uprawnienia E: {$protokol['uprawnienia_e']}<br>Uprawnienia D: {$protokol['uprawnienia_d']}</div>";
    } else {
        echo "<div><strong>Wykonał pomiary (E):</strong> {$protokol['inzynier_e']}<br>Uprawnienia: {$protokol['uprawnienia_e']}<br><br><strong>Sprawdził (D):</strong> {$protokol['inzynier_d']}<br>Uprawnienia: {$protokol['uprawnienia_d']}</div>";
    }
    echo "<div><strong>Aparatura Miernicza:</strong><br>";
    if (count($mierniki_lines) > 0) {
        foreach ($mierniki_lines as $m) {
            echo "- " . htmlspecialchars($m['nazwa']) . " (Wzorcowane: " . htmlspecialchars($m['data_wzorc']) . ", Ważne do: " . htmlspecialchars($m['data_waznosc']) . ")<br>";
        }
    } else if (!empty($protokol['miernik_nazwa'])) {
        echo "- " . htmlspecialchars($protokol['miernik_nazwa']) . " (Świadectwo: " . htmlspecialchars($protokol['miernik_wzorcowanie']) . ")<br>";
    } else {
        echo "- Brak danych o sprzęcie -<br>";
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // 1. Oględziny
    if (count($ogledziny_lines) > 0) {
        echo "<div class='sekcja'><h2>1. Oględziny Instalacji</h2>";
        echo "<table><thead><tr><th>Element Sprawdzany</th><th>Wynik</th></tr></thead><tbody>";
        foreach ($ogledziny_lines as $ogl) {
            $wynik = htmlspecialchars($ogl['wynik']);
            $klasa = ($wynik == 'P') ? 'pozytywny' : (($wynik == 'N') ? 'negatywny' : '');
            echo "<tr><td style='text-align:left;'>" . htmlspecialchars($ogl['nazwa']) . "</td><td class='$klasa'>$wynik</td></tr>";
        }
        echo "</tbody></table>";
        echo $html_podpisy;
        echo "</div>";
    }

    // 2. Rezystancja Izolacji
    if (count($riso_lines) > 0) {
        echo "<div class='sekcja'><h2>2. Badanie Rezystancji Izolacji</h2>";
        echo "<p>Wzór weryfikujący normy PN-HD 60364-6: <span class='wzor'>R<sub>zmierzona L+N_PE</sub> &ge; R<sub>wymagane</sub></span></p>";
        echo "<table><thead><tr><th>Lp.</th><th>Obwód / Urządzenie</th><th>U probiercze [V DC]</th><th>Wymagane [MΩ]</th><th>Zmierzone L+N_PE [MΩ]</th><th>Wynik</th></tr></thead><tbody>";
        foreach ($riso_lines as $idx => $row) {
            $lp = $idx + 1;
            $klasa = ($row['wynik'] == 'POZYTYWNY') ? 'pozytywny' : 'negatywny';
            echo "<tr><td>$lp</td><td style='text-align:left;'>" . htmlspecialchars($row['nazwa']) . "</td><td>" . htmlspecialchars($row['u_prob']) . "</td>";
            echo "<td>" . htmlspecialchars($row['wymagane']) . "</td><td>" . htmlspecialchars($row['zmierzone']) . "</td><td class='$klasa'>" . htmlspecialchars($row['wynik']) . "</td></tr>";
        }
        echo "</tbody></table>";
        echo $html_podpisy;
        echo "</div>";
    }

    // 3. Pętla Zwarcia SWZ
    if (count($swz_lines) > 0) {
        echo "<div class='sekcja'><h2>3. Skuteczność Samoczynnego Wyłączenia Zasilania (SWZ)</h2>";
        echo "<p>Zastosowano wzory z PN-HD 60364-6 z kryterium temperaturowym:<br> 
              <span class='wzor'>k = wskaźnik</span>, <span class='wzor'>I<sub>a</sub> = I<sub>n</sub> &times; k</span>, 
              <span class='wzor'>Z<sub>dop</sub> = U<sub>0</sub> / I<sub>a</sub></span>, 
              <span class='wzor'>Z<sub>dop_ciepły</sub> = 2/3 &times; Z<sub>dop</sub></span></p>";

        echo "<table><thead><tr><th>Lp.</th><th>Obwód / Urządzenie</th><th>Układ</th><th>Typ/In [A]</th><th>Współcz.</th><th>I<sub>a</sub> [A]</th><th>Zmierzone Z<sub>s</sub>/R<sub>a</sub> [Ω]</th><th>Dopuszczalne Z<sub>dop_skor</sub> [Ω]</th><th>Wynik</th></tr></thead><tbody>";
        foreach ($swz_lines as $idx => $row) {
            $lp = $idx + 1;
            $klasa = ($row['wynik'] == 'POZYTYWNY') ? 'pozytywny' : 'negatywny';
            echo "<tr><td>$lp</td><td style='text-align:left;'>" . htmlspecialchars($row['nazwa']) . "</td>";
            echo "<td>" . (isset($row['net_type']) ? htmlspecialchars($row['net_type']) : 'TN') . "</td>";
            echo "<td>" . htmlspecialchars($row['typ']) . " " . htmlspecialchars($row['in']) . "A</td>";
            echo "<td>" . (isset($row['temp_wsp']) ? htmlspecialchars($row['temp_wsp']) : '0.66') . "</td>";
            echo "<td>" . htmlspecialchars($row['ia']) . "</td><td>" . htmlspecialchars($row['zs_zm']) . "</td><td>" . htmlspecialchars($row['zs_dop_skor']) . "</td><td class='$klasa'>" . htmlspecialchars($row['wynik']) . "</td></tr>";
        }
        echo "</tbody></table>";
        echo $html_podpisy;
        echo "</div>";
    }

    // 4. RCD
    if (count($rcd_lines) > 0) {
        echo "<div class='sekcja'><h2>4. Badanie Wyłączników Różnicowoprądowych (RCD)</h2>";
        echo "<p>Wzory dla pomiarów czasu testowego przy $1\times I_{\Delta n}$ z normy PN-EN 61557-6.</p>";

        echo "<table><thead><tr><th>Lp.</th><th>Typ RCD / Oznacz.</th><th>Typ Prądu (A/AC/B)</th><th>Test</th><th>I<sub>&Delta;n</sub> [mA]</th><th>I<sub>&Delta;</sub> Zmierzone [mA]</th><th>t<sub>A</sub> Zmierzone [ms]</th><th>Przycisk TEST</th><th>Wynik</th></tr></thead><tbody>";
        foreach ($rcd_lines as $idx => $row) {
            $lp = $idx + 1;
            $klasa = ($row['wynik'] == 'POZYTYWNY') ? 'pozytywny' : 'negatywny';
            echo "<tr><td>$lp</td><td style='text-align:left;'>" . htmlspecialchars($row['nazwa']) . "</td><td>" . htmlspecialchars($row['typ_rcd']) . "</td>";
            echo "<td>" . (isset($row['test_mode']) ? htmlspecialchars($row['test_mode']) : '1x') . "</td>";
            echo "<td>" . htmlspecialchars($row['i_dn']) . "</td><td>" . htmlspecialchars($row['i_zm']) . "</td><td>" . htmlspecialchars($row['ta_zm']) . "</td><td>" . htmlspecialchars($row['test_btn']) . "</td><td class='$klasa'>" . htmlspecialchars($row['wynik']) . "</td></tr>";
        }
        echo "</tbody></table>";
        echo $html_podpisy;
        echo "</div>";
    }

    echo "</body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>Aplikacja Pomiary 2.0 - Centralny Kreator</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eef2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-top: 30px;
        }

        h1 {
            text-align: center;
        }

        .section-box {
            border: 1px solid #bdc3c7;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
            background: #fafafa;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #34495e;
            color: white;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-add {
            background-color: #27ae60;
            margin-bottom: 10px;
        }

        .btn-submit {
            background-color: #e74c3c;
            width: 100%;
            font-size: 18px;
            padding: 15px;
            margin-top: 30px;
        }

        .btn-add:hover {
            background-color: #219653;
        }

        .btn-submit:hover {
            background-color: #c0392b;
        }

        input.error {
            border-color: red;
            background-color: #ffe6e6;
        }

        .dynamic-row input,
        .dynamic-row select {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
        }

        .formula-info {
            font-size: 0.85em;
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            border-left: 5px solid #ffeeba;
            margin-top: -10px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Kreator Monolityczny Pomiary 2.0</h1>
        <p style="text-align: center; color: #7f8c8d;">Jedna strona - jeden kompletny protokół do bazy danych.</p>

        <div class="section-box" style="background:#eaf2f8; border-color:#b4ccde;">
            <h2 style="margin-top:0; border-bottom: 2px solid #2980b9; color:#2980b9;">Wcześniej zapisane protokoły
                (Archiwum)</h2>
            <?php
$archiwa = [];
try {
    $stmt_arch = $db->query("SELECT id, obiekt_nazwa, data_pomiaru, inzynier_e, data_utworzenia FROM protokoly ORDER BY id DESC LIMIT 10");
    if ($stmt_arch) {
        $archiwa = $stmt_arch->fetchAll();
    }
}
catch (PDOException $e) {
    echo "<p style='color:red;'>Błąd pobierania archiwum z bazy danych SQLite: " . $e->getMessage() . "</p>";
}
if (count($archiwa) > 0) {
    echo "<table><thead><tr><th>ID</th><th>Data Pomiaru</th><th>Obiekt</th><th>Inżynier (E)</th><th>Utworzono</th><th>Akcja</th></tr></thead><tbody>";
    foreach ($archiwa as $a) {
        echo "<tr>
                        <td>" . htmlspecialchars($a['id']) . "</td>
                        <td>" . htmlspecialchars($a['data_pomiaru']) . "</td>
                        <td>" . htmlspecialchars($a['obiekt_nazwa']) . "</td>
                        <td>" . htmlspecialchars($a['inzynier_e']) . "</td>
                        <td>" . htmlspecialchars($a['data_utworzenia']) . "</td>
                        <td>
                            <a href='index.php?view=" . htmlspecialchars($a['id']) . "' class='btn' style='background:#f39c12; padding: 5px 10px; font-size:0.8em;'>Pokaż/Drukuj</a>
                            <a href='index.php?edit=" . htmlspecialchars($a['id']) . "' class='btn' style='background:#3498db; padding: 5px 10px; font-size:0.8em; margin-left:5px;'>Edytuj</a>
                            <a href='index.php?delete=" . htmlspecialchars($a['id']) . "' class='btn' style='background:#e74c3c; padding: 5px 10px; font-size:0.8em; margin-left:5px;' onclick='return confirm(\"Czy na pewno chcesz usunąć protokół ID: " . htmlspecialchars($a['id']) . "?\");'>Usuń</a>
                        </td>
                      </tr>";
    }
    echo "</tbody></table>";
}
else {
    echo "<p>Brak zapisanych protokołów w bazie danych.</p>";
}
?>
        </div>

        <form id="monoForm" method="POST" action="index.php">
            <?php if ($success_msg): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; border: 1px solid #c3e6cb;">
                <?php echo $success_msg; ?>
            </div>
            <?php endif; ?>

            <?php if ($edit_protokol_id): ?>
            <input type="hidden" name="edycja_id" value="<?php echo htmlspecialchars($edit_protokol_id); ?>">
            <div
                style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; font-size: 1.1em; border: 1px solid #ffeeba;">
                Ostrzeżenie: Tryb Edycji Protokołu ID:
                <?php echo htmlspecialchars($edit_protokol_id); ?>. Zapis nadpisze poprzednie dane dla tego numeru ID!
                <a href="index.php" style="float:right; color:#856404;">Anuluj Edycję (Nowy)</a>
            </div>
            <?php
endif; ?>

            <!-- NAGŁÓWEK DO BAZY (Wspólny do wszystkiego) -->
            <div class="section-box">
                <h2>Dane Nagłówkowe Protokołu</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nazwa Obiektu Budowlanego:</label>
                        <input type="text" name="obiekt_nazwa" id="obiekt_nazwa" required
                            placeholder="np. Instalacja Zwykła PPHU">
                    </div>
                    <div class="form-group">
                        <label>Adres Obiektu:</label>
                        <input type="text" name="adres" id="adres" required
                            placeholder="np. ul. Lipowa 15, 00-000 Miasto">
                    </div>
                    <div class="form-group">
                        <label>Data wykonania pomiarów:</label>
                        <input type="date" name="data_pomiaru" id="data_pomiaru" required>
                    </div>
                    <div class="form-group">
                        <label>Warunki środowiskowe (Pogoda/Temp.):</label>
                        <input type="text" name="pogoda" id="pogoda" placeholder="np. Pochmurno, +21 st. C, sucho">
                    </div>
                    <div class="form-group">
                        <label>Układ sieciowy zasilający:</label>
                        <select id="uklad_sieci" name="uklad_sieci" required>
                            <option value="TN-S">TN-S</option>
                            <option value="TN-C">TN-C</option>
                            <option value="TN-C-S" selected>TN-C-S</option>
                            <option value="TT">TT</option>
                            <option value="IT">IT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Napięcie nominalne fazowe U0 [V]:</label>
                        <input type="number" id="napiecie_u0" name="napiecie_u0" value="230" required>
                    </div>
                </div>

                <h3>Uprawnienia i Personel</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Osoba Wykonująca Pomiary (E):</label>
                        <input type="text" name="inzynier_e" id="inzynier_e" required placeholder="Jan Kowalski">
                    </div>
                    <div class="form-group">
                        <label>Nr uprawnień (E):</label>
                        <input type="text" name="uprawnienia_e" id="uprawnienia_e" required placeholder="E/123/2021">
                    </div>
                    <div class="form-group">
                        <label>
                            Osoba Sprawdzająca (D):
                            <label style="display:inline; margin-left:10px; font-weight:normal; font-size: 0.9em; cursor:pointer;">
                                <input type="checkbox" id="same_person_ed" onchange="toggleSamePerson(this)"> Ta sama osoba
                            </label>
                        </label>
                        <input type="text" name="inzynier_d" id="inzynier_d" required placeholder="Tomasz Nowak">
                    </div>
                    <div class="form-group">
                        <label>Nr uprawnień (D):</label>
                        <input type="text" name="uprawnienia_d" id="uprawnienia_d" required placeholder="D/456/2021">
                    </div>
                </div>

                <hr style="border-top:1px dashed #ccc; margin:20px 0;">
                <h3>Aparatura Pomiarowa i Narzędzia</h3>
                <div class="formula-info">Data ważności przelicza się automatycznie dodając równy rok do daty wzorcowania, aczkolwiek możesz ją ręcznie zmienić.</div>
                <button type="button" class="btn btn-add" onclick="addMiernikRow()">+ Dodaj Miernik</button>
                <table id="tbl-mierniki">
                    <thead>
                        <tr>
                            <th>Użyty Miernik (Nazwa / Model)</th>
                            <th>Data Wzorcowania (Kalibracji)</th>
                            <th>Data Ważności Świadectwa</th>
                            <th>Usuń</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <!-- Ukryty input MIERNIKI do json z backendem -->
                <input type="hidden" name="mierniki_data" id="in_mierniki">
            </div>

            <!-- SEKCJA 1: OGLĘDZINY -->
            <div class="section-box">
                <h2>Krok 1: Oględziny PN-HD 60364-6</h2>
                <div class="formula-info">Oględziny sprawdza się w stanie bez napięcia. Warunek P=Pozytywny, N=Negatywny
                    zamyka certyfikację obwodu.</div>
                <table id="tbl-ogledziny">
                    <thead>
                        <tr>
                            <th>Badany Aspekt (Przykłady)</th>
                            <th>Zaznacz Wynik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1. Ochrona przed dotykiem bezpośrednim (izolacja, obudowy, stopnie IP)</td>
                            <td><select class="ogl_wynik">
                                    <option value="P">Pozytywny (P)</option>
                                    <option value="N">Negatywny (N)</option>
                                    <option value="ND">Nie Dotyczy (ND)</option>
                                </select></td>
                        </tr>
                        <tr>
                            <td>2. Prawidłowość doboru barw ochronnych PE, PEN, N</td>
                            <td><select class="ogl_wynik">
                                    <option value="P">Pozytywny (P)</option>
                                    <option value="N">Negatywny (N)</option>
                                    <option value="ND">Nie Dotyczy (ND)</option>
                                </select></td>
                        </tr>
                    </tbody>
                </table>
                <!-- Ukryty Input dla Backend -->
                <input type="hidden" name="ogledziny_data" id="in_ogledziny">
            </div>

            <!-- SEKCJA 2: IZOLACJA RISO -->
            <div class="section-box">
                <h2>Krok 2: Rezystancja Izolacji</h2>
                <div class="formula-info">Pomiary pomiędzy L+N zwartymi a przewodem ochronnym PE. Norma: R_iso &ge;
                    Wymagane.</div>
                <button type="button" class="btn btn-add" onclick="addRisoRow()">+ Dodaj obwód Izolacji</button>
                <table id="tbl-riso">
                    <thead>
                        <tr>
                            <th>Nazwa Obwodu</th>
                            <th>U robocze/probiercze</th>
                            <th>Wymagane [MΩ]</th>
                            <th>Zmierzone [MΩ]</th>
                            <th>Usuń</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <input type="hidden" name="riso_data" id="in_riso">
            </div>

            <!-- SEKCJA 3: SWZ ZS -->
            <div class="section-box">
                <h2>Krok 3: Samoczynne Wyłączenie Zasilania (SWZ)</h2>
                <div class="formula-info"><strong>Wzory (wyliczane za Ciebie):</strong> I<sub>a</sub> = I<sub>n</sub> *
                    Krotność <br>
                    <strong>TN:</strong> Z<sub>dopuszczalne</sub> = ( U<sub>0</sub> / I<sub>a</sub> ). Wsp. temperaturowy 0.66 (dla starych układów 0.8).<br>
                    <strong>TT:</strong> Ochronę zazwyczaj weryfikuje RCD. Wpisz wymóg R<sub>a</sub> zamiast Z<sub>s</sub>.<br>
                    <strong>Warunek normy: Zmierzona Z<sub>s</sub> / R<sub>a</sub> &le; Z<sub>dopuszczalne (skorygowane)</sub></strong>
                </div>
                <button type="button" class="btn btn-add" onclick="addSwzRow()">+ Dodaj obwód Pętli Zwarcia Zs / Ra</button>
                <table id="tbl-swz">
                    <thead>
                        <tr>
                            <th>Nazwa Obwodu</th>
                            <th>Typ Sieci</th>
                            <th>Zabezp (B/C/D) / RCD</th>
                            <th>Prąd In/I&#916;n [A]</th>
                            <th>Temp. Wsp.</th>
                            <th>Zmierzone [&Omega;]</th>
                            <th>Usuń</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <input type="hidden" name="swz_data" id="in_swz">
            </div>

            <!-- SEKCJA 4: RCD -->
            <div class="section-box">
                <h2>Krok 4: Pomiary Wyłączników RCD</h2>
                <div class="formula-info">Prąd zadziałania 0.5 - 1.0 I_Δn. Czasowy warunek <strong>tA &le;
                        300ms</strong> dla testów powolnych.</div>
                <button type="button" class="btn btn-add" onclick="addRcdRow()">+ Dodaj badanie RCD</button>
                <table id="tbl-rcd">
                    <thead>
                        <tr>
                            <th>Opis RCD</th>
                            <th>Typ (A/AC/B)</th>
                            <th>I_Δn [mA]</th>
                            <th>I_Δ zmierzone [mA]</th>
                            <th>t_A zmierzone [ms]</th>
                            <th>Przycisk TEST</th>
                            <th>Usuń</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <input type="hidden" name="rcd_data" id="in_rcd">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button type="button" class="btn btn-submit" style="margin-top: 0; flex: 1;" onclick="submitForms()">Zatwierdź pomiary i Zapisz do Bazy</button>
                
                <?php if ($edit_protokol_id): ?>
                <a href="index.php?view=<?php echo htmlspecialchars($edit_protokol_id); ?>" class="btn" style="background-color: #f39c12; flex: 1; text-align: center; font-size: 18px; padding: 15px; display: inline-block; box-sizing: border-box; text-decoration: none;">🔍 Przejdź do Podglądu i Opcji Druku</a>
                <?php else: ?>
                <a href="#" class="btn" style="background-color: #7f8c8d; flex: 1; text-align: center; font-size: 18px; padding: 15px; display: inline-block; box-sizing: border-box; text-decoration: none; cursor: not-allowed; opacity: 0.6;" onclick="event.preventDefault();">🔍 Przejdź do Podglądu i Opcji Druku</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        // System przechowywania podręcznego oraz Wczytywanie Danych Edycji //
        const loadedData = <?php echo json_encode($loaded_data); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            const STORAGE_KEY = 'pomiary2_stan_globalny';
            const header_ids = ['obiekt_nazwa', 'adres', 'data_pomiaru', 'pogoda', 'uklad_sieci', 'napiecie_u0', 'inzynier_e', 'uprawnienia_e', 'inzynier_d', 'uprawnienia_d'];

            function saveCache() {
                let data = {};
                header_ids.forEach(id => {
                    let el = document.getElementById(id);
                    if (el) data[id] = el.value;
                });
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }

            if (loadedData && loadedData.naglowek) {
                // Edytujemy, nadpisujemy formularz starymi danymi
                header_ids.forEach(id => {
                    let el = document.getElementById(id);
                    if (el && loadedData.naglowek[id] !== undefined) {
                        el.value = loadedData.naglowek[id];
                    }
                });
            } else {
                // Otwieramy nowy na czysto - ladujemy ewentualnie cache ulatwiajacy prace z localStorage
                let saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    try {
                        let j = JSON.parse(saved);
                        header_ids.forEach(id => {
                            let el = document.getElementById(id);
                            if (el && j[id]) el.value = j[id];
                        });
                    } catch (e) { }
                }
            }

            header_ids.forEach(id => {
                let el = document.getElementById(id);
                if (el) el.addEventListener('input', saveCache);
            });

            // ŁADOWANIE WIERSZY Z JSONA LUB PUSTYCH (TRYB NOWY)
            if (loadedData) {
                // Oględziny
                if (loadedData.ogledziny && loadedData.ogledziny.length > 0) {
                    let trs = document.querySelectorAll('#tbl-ogledziny tbody tr');
                    for (let i = 0; i < loadedData.ogledziny.length && i < trs.length; i++) {
                        let sel = trs[i].querySelector('select');
                        if (sel) sel.value = loadedData.ogledziny[i].wynik;
                    }
                }

                // Riso
                if (loadedData.riso && loadedData.riso.length > 0) {
                    loadedData.riso.forEach(row => addRisoRow(row));
                } else { addRisoRow(); }

                // Swz
                if (loadedData.swz && loadedData.swz.length > 0) {
                    loadedData.swz.forEach(row => addSwzRow(row));
                } else { addSwzRow(); }

                // Mierniki
                if (loadedData.mierniki && loadedData.mierniki.length > 0) {
                    loadedData.mierniki.forEach(row => addMiernikRow(row));
                } else if (loadedData.naglowek && loadedData.naglowek.miernik_nazwa && loadedData.naglowek.miernik_nazwa !== '') {
                    // Wsteczna kompatybilność, starszy protokół wciąga dane z usuniętych pól
                    addMiernikRow({
                        nazwa: loadedData.naglowek.miernik_nazwa, 
                        data_wzorc: '', 
                        data_waznosc: loadedData.naglowek.miernik_wzorcowanie
                    });
                } else { addMiernikRow(); }

                // Rcd
                if (loadedData.rcd && loadedData.rcd.length > 0) {
                    loadedData.rcd.forEach(row => addRcdRow(row));
                } else { addRcdRow(); }
            } else {
                // Nowy formularz
                addMiernikRow();
                addRisoRow();
                addSwzRow();
                addRcdRow();
            }

            // Autodetekcja tej samej osoby przy edycji/pamięci
            let e_name = document.getElementById('inzynier_e').value;
            let d_name = document.getElementById('inzynier_d').value;
            if (e_name && e_name === d_name) {
                let cb = document.getElementById('same_person_ed');
                if(cb) { cb.checked = true; toggleSamePerson(cb); }
            }

        });

        // -------- WSPÓLNY INŻYNIER (E+D) --------
        function toggleSamePerson(cb) {
            let e_name = document.getElementById('inzynier_e');
            let d_name = document.getElementById('inzynier_d');
            if (cb.checked) {
                d_name.value = e_name.value;
                d_name.setAttribute('readonly', 'true');
                d_name.style.backgroundColor = '#f1f1f1';
                e_name.addEventListener('input', syncNames);
            } else {
                d_name.removeAttribute('readonly');
                d_name.style.backgroundColor = '';
                e_name.removeEventListener('input', syncNames);
            }
        }
        function syncNames() {
            if(document.getElementById('same_person_ed').checked) {
                document.getElementById('inzynier_d').value = document.getElementById('inzynier_e').value;
            }
        }

        // -------- MIERNIKI DYNAMICS --------
        function addMiernikRow(data = null) {
            const tbody = document.querySelector('#tbl-mierniki tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-miernik-tr';

            let n = data ? data.nazwa : "Sonel MPI-540";
            let d_wzorc = data ? data.data_wzorc : "";
            let d_wazne = data ? data.data_waznosc : "";

            tr.innerHTML = `
            <td><input type="text" class="miernik_nazwa" value="${n}" placeholder="Nazwa"></td>
            <td><input type="date" class="miernik_wzorc" value="${d_wzorc}" onchange="calcMiernikWaznosc(this)"></td>
            <td><input type="date" class="miernik_waznosc" value="${d_wazne}"></td>
            <td><button type="button" class="btn" style="background:#e74c3c; padding:5px 10px;" onclick="this.closest('tr').remove()">X</button></td>
        `;
            tbody.appendChild(tr);
        }

        function calcMiernikWaznosc(el) {
            let tr = el.closest('tr');
            let wzorc = tr.querySelector('.miernik_wzorc').value;
            if(wzorc) {
                let d = new Date(wzorc);
                d.setFullYear(d.getFullYear() + 1);
                tr.querySelector('.miernik_waznosc').value = d.toISOString().split('T')[0];
            }
        }

        // -------- RISO DYNAMICS --------
        function addRisoRow(data = null) {
            const tbody = document.querySelector('#tbl-riso tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-riso-tr';

            let n = data ? data.nazwa : "Obwód gniazd";
            let u = data ? data.u_prob : "500";
            let w = data ? data.wymagane : "1.0";
            let z = data ? data.zmierzone : ">999";

            tr.innerHTML = `
            <td><input type="text" class="riso_nazwa" value="${n}" placeholder="Nazwa"></td>
            <td><select class="riso_u">
                <option value="500" ${u == '500' ? 'selected' : ''}>230/400V (500V DC probiercze)</option>
                <option value="250" ${u == '250' ? 'selected' : ''}>SELV/PELV (250V DC probiercze)</option></select></td>
            <td><input type="text" class="riso_wymagane" value="${w}" readonly style="background:#eee"></td>
            <td><input type="text" class="riso_zmierzone" value="${z}" oninput="recalcRiso(this)"></td>
            <td><button type="button" class="btn" style="background:#e74c3c; padding:5px 10px;" onclick="this.closest('tr').remove()">X</button></td>
        `;
            tbody.appendChild(tr);
            recalcRiso(tr.querySelector('.riso_u'));
        }

        function recalcRiso(el) {
            let tr = el.closest('tr');
            let uProb = tr.querySelector('.riso_u').value;
            let reqEl = tr.querySelector('.riso_wymagane');
            reqEl.value = (uProb == '250') ? "0.5" : "1.0";
            // Wyniki zatwierdza funkcja submitForms
        }

        // -------- SWZ DYNAMICS --------
        function addSwzRow(data = null) {
            const tbody = document.querySelector('#tbl-swz tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-swz-tr';

            let n = data ? data.nazwa : "Obwód oświetlenia";
            let net = data ? data.net_type : "TN";
            let c = data ? data.typ : "B";
            let in_val = data ? data.in : "16";
            let wsp = data ? data.temp_wsp : "0.66 (Nowa)";
            let zszm = data ? data.zs_zm : "0.45";

            tr.innerHTML = `
            <td><input type="text" class="swz_nazwa" value="${n}" placeholder="Korytarz główny"></td>
            <td>
                <select class="swz_net">
                    <option value="TN" ${net == 'TN' ? 'selected' : ''}>TN (Zs)</option>
                    <option value="TT" ${net == 'TT' ? 'selected' : ''}>TT (Ra RCD)</option>
                </select>
            </td>
            <td>
                <select class="swz_char">
                    <option value="B" ${c == 'B' ? 'selected' : ''}>B (krot=5)</option>
                    <option value="C" ${c == 'C' ? 'selected' : ''}>C (krot=10)</option>
                    <option value="D" ${c == 'D' ? 'selected' : ''}>D (krot=20)</option>
                    <option value="RCD" ${c == 'RCD' ? 'selected' : ''}>RCD (dla TT)</option>
                </select>
            </td>
            <td><input type="number" step="0.01" class="swz_in" value="${in_val}"></td>
            <td>
                <select class="swz_wsp">
                    <option value="0.66" ${wsp == '0.66 (Nowa)' ? 'selected' : ''}>0.66 (Nowa)</option>
                    <option value="0.80" ${wsp == '0.80 (Stara)' ? 'selected' : ''}>0.80 (Stara)</option>
                    <option value="1.00" ${wsp == '1.00 (TT)' ? 'selected' : ''}>1.00 (TT)</option>
                </select>
            </td>
            <td><input type="number" step="0.01" class="swz_zs_zm" value="${zszm}"></td>
            <td><button type="button" class="btn" style="background:#e74c3c; padding:5px 10px;" onclick="this.closest('tr').remove()">X</button></td>
        `;
            tbody.appendChild(tr);
        }

        // -------- RCD DYNAMICS --------
        function addRcdRow(data = null) {
            const tbody = document.querySelector('#tbl-rcd tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-rcd-tr';

            let n = data ? data.nazwa : "RCD Gniazd Łazienka";
            let typ = data ? data.typ_rcd : "AC";
            let mode = data ? data.test_mode : "1x";
            let idn = data ? data.i_dn : "30";
            let izm = data ? data.i_zm : "22.5";
            let tazm = data ? data.ta_zm : "120";
            let btn = data ? data.test_btn : "Sprawny";

            tr.innerHTML = `
            <td><input type="text" class="rcd_nazwa" value="${n}"></td>
            <td>
                <select class="rcd_typ">
                    <option value="AC" ${typ == 'AC' ? 'selected' : ''}>AC</option>
                    <option value="A" ${typ == 'A' ? 'selected' : ''}>A</option>
                    <option value="B" ${typ == 'B' ? 'selected' : ''}>B (PV/Ładowarki)</option>
                </select>
            </td>
            <td>
                <select class="rcd_mode">
                    <option value="1x" ${mode == '1x' ? 'selected' : ''}>1x I&#916;n</option>
                    <option value="5x" ${mode == '5x' ? 'selected' : ''}>5x I&#916;n (40ms)</option>
                </select>
            </td>
            <td><select class="rcd_idn">
                <option value="30" ${idn == '30' ? 'selected' : ''}>30 mA</option>
                <option value="100" ${idn == '100' ? 'selected' : ''}>100 mA</option>
                <option value="300" ${idn == '300' ? 'selected' : ''}>300 mA</option></select></td>
            <td><input type="number" class="rcd_izm" value="${izm}" style="width:70px"></td>
            <td><input type="number" class="rcd_tazm" value="${tazm}" style="width:70px"></td>
            <td><select class="rcd_testbtn">
                  <option value="Sprawny" ${btn == 'Sprawny' ? 'selected' : ''}>Sprawny</option>
                <option value="Uszkodzony" ${btn == 'Uszkodzony' ? 'selected' : ''}>Uszkodzony</option></select></td>
            <td><button type="button" class="btn" style="background:#e74c3c; padding:5px 10px;" onclick="this.closest('tr').remove()">X</button></td>
            `; 
            tbody.appendChild(tr);
        }

        // -------- KOMPILACJA I WYSYŁKA FORMULARZA --------
        function submitForms() {
            let u0 = parseFloat(document.getElementById('napiecie_u0').value) || 230;

            // 0. Mierniki JSON
            let mierniki_arr = [];
            document.querySelectorAll('.obw-miernik-tr').forEach(tr => {
                mierniki_arr.push({
                    nazwa: tr.querySelector('.miernik_nazwa').value,
                    data_wzorc: tr.querySelector('.miernik_wzorc').value,
                    data_waznosc: tr.querySelector('.miernik_waznosc').value
                });
            });
            document.getElementById('in_mierniki').value = JSON.stringify(mierniki_arr);

            // 1. Oględziny JSON
            let ogledziny_arr = [];
            let rowsOg = document.querySelectorAll('#tbl-ogledziny tbody tr');
            rowsOg.forEach(tr => {
                let td = tr.querySelectorAll('td');
                ogledziny_arr.push({ nazwa: td[0].innerText, wynik: td[1].querySelector('select').value });
            });
            document.getElementById('in_ogledziny').value = JSON.stringify(ogledziny_arr);

            // 2. RISO JSON
            let riso_arr = [];
            document.querySelectorAll('.obw-riso-tr').forEach(tr => {
                let zm = document.createElement('div');
                let zVal = tr.querySelector('.riso_zmierzone').value;
                let wVal = parseFloat(tr.querySelector('.riso_wymagane').value);
                let zmValFloat = zVal.includes('>') ? 9999 : parseFloat(zVal);
                let wynikStatus = (zmValFloat >= wVal) ? "POZYTYWNY" : "NEGATYWNY";

                riso_arr.push({
                    nazwa: tr.querySelector('.riso_nazwa').value,
                    u_prob: tr.querySelector('.riso_u').value,
                    wymagane: wVal,
                    zmierzone: zVal,
                    wynik: wynikStatus
                });
            });
            document.getElementById('in_riso').value = JSON.stringify(riso_arr);

            // 3. SWZ JSON
            let swz_arr = [];
            document.querySelectorAll('.obw-swz-tr').forEach(tr => {
                let net_type = tr.querySelector('.swz_net').value;
                let charak = tr.querySelector('.swz_char').value;
                let i_n = parseFloat(tr.querySelector('.swz_in').value);
                let z_szm = parseFloat(tr.querySelector('.swz_zs_zm').value);
                let raw_wsp = tr.querySelector('.swz_wsp');
                let wsp_label = raw_wsp.options[raw_wsp.selectedIndex].text;
                let wsp_val = parseFloat(raw_wsp.value);

                let i_a;
                let z_dop;

                if (net_type === 'TT' || charak === 'RCD') {
                    // TT lub RCD -> Ochrona przy Ra <= 50V / Idn
                    // Traktujemy i_n w inpucie jako I_delta_n w Amperach (np 0.03A = 30mA)
                    i_a = i_n; 
                    let uL = 50; // Napięcie dotykowe długotrwale dopuszczalne (Zazwyczaj 50V dla klimatu normalnego)
                    z_dop = uL / i_a;
                } else {
                    // TN z nadprądowymi
                    let k = (charak === 'B') ? 5 : ((charak === 'C') ? 10 : 20);
                    i_a = i_n * k;
                    z_dop = u0 / i_a;
                }

                // Kalkulacja ze współczynnikiem (0.66 nowa / 0.8 stara / 1.0 TT)
                let z_dop_skor = z_dop * wsp_val;
                let z_dop_format = z_dop_skor.toFixed(2);

                let wynikStatus = (z_szm <= z_dop_skor) ? "POZYTYWNY" : "NEGATYWNY";

                let k_info = (charak === 'RCD' || net_type === 'TT') ? '-' : (charak === 'B' ? 5 : (charak === 'C' ? 10 : 20));

                swz_arr.push({
                    nazwa: tr.querySelector('.swz_nazwa').value,
                    net_type: net_type,
                    typ: charak,
                    in: i_n,
                    temp_wsp: wsp_label,
                    k: k_info,
                    ia: i_a.toFixed(2),
                    zs_zm: z_szm,
                    zs_dop_skor: z_dop_format,
                    wynik: wynikStatus
                });
            });
            document.getElementById('in_swz').value = JSON.stringify(swz_arr);

            // 4. RCD JSON
            let rcd_arr = [];
            document.querySelectorAll('.obw-rcd-tr').forEach(tr => {
                let typ_rcd = tr.querySelector('.rcd_typ').value;
                let mode = tr.querySelector('.rcd_mode').value;
                let zmT = parseFloat(tr.querySelector('.rcd_tazm').value);
                let zmI = parseFloat(tr.querySelector('.rcd_izm').value);
                let idn = parseFloat(tr.querySelector('.rcd_idn').value);
                let btn = tr.querySelector('.rcd_testbtn').value;

                let wynikStatus = "POZYTYWNY";
                
                // Walidacja - czas max zależy od mnożnika (1xIdn = 300ms, 5xIdn = 40ms)
                let max_time = (mode === '5x') ? 40 : 300;
                let required_current_min = (mode === '5x') ? (5 * 0.5 * idn) : (0.5 * idn);
                let required_current_max = (mode === '5x') ? (5 * idn) : idn;

                if (zmT > max_time || zmI < required_current_min || zmI > required_current_max || btn === 'Uszkodzony') {
                    wynikStatus = "NEGATYWNY";
                }

                rcd_arr.push({
                    nazwa: tr.querySelector('.rcd_nazwa').value,
                    typ_rcd: typ_rcd,
                    test_mode: mode,
                    i_dn: idn,
                    i_zm: zmI,
                    ta_zm: zmT,
                    test_btn: btn,
                    wynik: wynikStatus
                });
            });
            document.getElementById('in_rcd').value = JSON.stringify(rcd_arr);

            // Wyslij
            document.getElementById('monoForm').submit();
        }
    </script>

</body>