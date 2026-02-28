<?php
// Aplikacja: Pomiary 2.0 (Monolit)
// Przechowywanie w lokalnej bazie SQLite
require_once __DIR__ . '/database.php';

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$success_msg = '';

if ($is_submitted) {
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
        $stmt = $db->prepare("
            INSERT INTO protokoly 
            (obiekt_nazwa, adres, data_pomiaru, uklad_sieci, napiecie_u0, inzynier_e, uprawnienia_e, inzynier_d, uprawnienia_d, pogoda) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $obiekt_nazwa, $adres, $data_pomiaru, $uklad_sieci, $napiecie_u0,
            $inzynier_e, $uprawnienia_e, $inzynier_d, $uprawnienia_d, $pogoda
        ]);

        $protokol_id = $db->lastInsertId();

        $insert_linie = $db->prepare("INSERT INTO pomiary_linie (protokol_id, kategoria, dane_json) VALUES (?, ?, ?)");

        // Oględziny
        $insert_linie->execute([$protokol_id, 'OGLEDZINY', $ogledziny_json]);
        // RISO
        $insert_linie->execute([$protokol_id, 'RISO', $riso_json]);
        // SWZ
        $insert_linie->execute([$protokol_id, 'SWZ', $swz_json]);
        // RCD
        $insert_linie->execute([$protokol_id, 'RCD', $rcd_json]);

        $db->commit();
        $success_msg = "Pomyślnie utworzono i zapisano Protokół ID: $protokol_id w bazie danych!";

        // Render View Mode
        $render_protokol_id = $protokol_id;

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

// Jeśli wczytujemy podgląd ze świeżego zapisu lub GET, ładujemy z BD z pięknym widokiem
if ($render_protokol_id) {
    // Renderowanie PDFa (Backend-view)
    $stmt = $db->prepare("SELECT * FROM protokoly WHERE id = ?");
    $stmt->execute([$render_protokol_id]);
    $protokol = $stmt->fetch();
    if (!$protokol) {
        die("Nie znaleziono protokołu");
    }

    // Pobranie Linii
    function getLines($db, $pid, $cat)
    {
        $s = $db->prepare("SELECT dane_json FROM pomiary_linie WHERE protokol_id = ? AND kategoria = ?");
        $s->execute([$pid, $cat]);
        $res = $s->fetch();
        return $res ? json_decode($res['dane_json'], true) : [];
    }

    $ogledziny_lines = getLines($db, $render_protokol_id, 'OGLEDZINY');
    $riso_lines = getLines($db, $render_protokol_id, 'RISO');
    $swz_lines = getLines($db, $render_protokol_id, 'SWZ');
    $rcd_lines = getLines($db, $render_protokol_id, 'RCD');

    echo "<!DOCTYPE html><html lang='pl'><head><meta charset='UTF-8'><title>Protokół #{$render_protokol_id}</title>";
    echo "<style>
            body { font-family: 'Times New Roman', serif; line-height: 1.4; margin: 40px; color: #333; font-size: 11pt; }
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
            .signatures { margin-top: 50px; display: flex; justify-content: space-around; page-break-inside: avoid;}
            .sig-box { border-top: 1px solid #000; width: 40%; text-align: center; padding-top: 5px; }
            @media print {
                .no-print { display: none; }
                @page { size: A4; margin: 15mm; }
                body { margin: 0; padding: 0; }
            }
          </style></head><body>";

    echo "<button class='no-print' onclick='window.print()' style='padding: 10px 20px; margin-bottom: 20px; cursor:pointer;'>Drukuj Protokół</button>";
    echo "<a href='index.php' class='no-print' style='margin-left: 15px; padding: 10px 20px; background: #eee; border: 1px solid #ccc; text-decoration:none; color:#000;'>Nowy Dokument</a>";

    if ($success_msg) {
        echo "<div class='no-print' style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; font-weight: bold;'>$success_msg</div>";
    }

    // NAGŁÓWEK PROTOKOŁU (Widoczny na pierwszej stronie przed wszystkimi tabelami)
    echo "<h1>PROTOKÓŁ Z POMIARÓW ELEKTRYCZNYCH</h1>";
    echo "<div class='header-info'>";
    echo "<div class='header-grid'>";
    echo "<div><strong>Obiekt:</strong> {$protokol['obiekt_nazwa']}<br><strong>Adres:</strong> {$protokol['adres']}<br><strong>Data pomiaru:</strong> {$protokol['data_pomiaru']}<br><strong>Warunki środowiskowe:</strong> {$protokol['pogoda']}</div>";
    echo "<div><strong>Układ sieciowy zasilający:</strong> {$protokol['uklad_sieci']}<br><strong>Napięcie nominalne fazowe U<sub>0</sub>:</strong> {$protokol['napiecie_u0']} V<br></div>";
    echo "</div>";
    echo "<hr style='margin:10px 0;'>";
    echo "<div class='header-grid'>";
    echo "<div><strong>Wykonał pomiary (E):</strong> {$protokol['inzynier_e']}<br>Uprawnienia: {$protokol['uprawnienia_e']}</div>";
    echo "<div><strong>Sprawdził (D):</strong> {$protokol['inzynier_d']}<br>Uprawnienia: {$protokol['uprawnienia_d']}</div>";
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
        echo "</tbody></table></div>";
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
        echo "</tbody></table></div>";
    }

    // 3. Pętla Zwarcia SWZ
    if (count($swz_lines) > 0) {
        echo "<div class='sekcja'><h2>3. Skuteczność Samoczynnego Wyłączenia Zasilania (SWZ)</h2>";
        echo "<p>Zastosowano wzory z PN-HD 60364-6 z kryterium temperaturowym dla stanu nagrzanego:<br> 
              <span class='wzor'>k = wskaźnik_ch-yki (B=5, C=10)</span>, 
              <span class='wzor'>I<sub>a</sub> = I<sub>n</sub> &times; k</span>, 
              <span class='wzor'>Z<sub>dop</sub> = U<sub>0</sub> / I<sub>a</sub></span>, 
              <span class='wzor'>Z<sub>dop_ciepły</sub> = 2/3 &times; Z<sub>dop</sub></span>,
              <span class='wzor'>Z<sub>s</sub> &le; Z<sub>dop_ciepły</sub></span></p>";

        echo "<table><thead><tr><th>Lp.</th><th>Obwód / Urządzenie</th><th>Typ/In [A]</th><th>Krotność (k)</th><th>I<sub>a</sub> [A]</th><th>Zmierzone Z<sub>s</sub> [Ω]</th><th>Dopuszczalne 2/3 Z<sub>dop</sub> [Ω]</th><th>Wynik</th></tr></thead><tbody>";
        foreach ($swz_lines as $idx => $row) {
            $lp = $idx + 1;
            $klasa = ($row['wynik'] == 'POZYTYWNY') ? 'pozytywny' : 'negatywny';
            echo "<tr><td>$lp</td><td style='text-align:left;'>" . htmlspecialchars($row['nazwa']) . "</td><td>" . htmlspecialchars($row['typ']) . " " . htmlspecialchars($row['in']) . "A</td>";
            echo "<td>" . htmlspecialchars($row['k']) . "</td><td>" . htmlspecialchars($row['ia']) . "</td><td>" . htmlspecialchars($row['zs_zm']) . "</td><td>" . htmlspecialchars($row['zs_dop_skor']) . "</td><td class='$klasa'>" . htmlspecialchars($row['wynik']) . "</td></tr>";
        }
        echo "</tbody></table></div>";
    }

    // 4. RCD
    if (count($rcd_lines) > 0) {
        echo "<div class='sekcja'><h2>4. Badanie Wyłączników Różnicowoprądowych (RCD)</h2>";
        echo "<p>Wzory dla pomiarów czasu testowego przy $1\times I_{\Delta n}$ z normy PN-EN 61557-6:<br> 
              <span class='wzor'>I<sub>&Delta;</sub> &isin; [0.5 &times; I<sub>&Delta;n</sub>, 1.0 &times; I<sub>&Delta;n</sub>]</span>, 
              <span class='wzor'>t<sub>A</sub> &le; 300 ms (dla bezzwłocznych)</span></p>";

        echo "<table><thead><tr><th>Lp.</th><th>Typ RCD / Oznacz.</th><th>Typ Prądu</th><th>I<sub>&Delta;n</sub> [mA]</th><th>I<sub>&Delta;</sub> Zmierzone [mA]</th><th>t<sub>A</sub> Zmierzone [ms]</th><th>Przycisk TEST</th><th>Wynik</th></tr></thead><tbody>";
        foreach ($rcd_lines as $idx => $row) {
            $lp = $idx + 1;
            $klasa = ($row['wynik'] == 'POZYTYWNY') ? 'pozytywny' : 'negatywny';
            echo "<tr><td>$lp</td><td style='text-align:left;'>" . htmlspecialchars($row['nazwa']) . "</td><td>" . htmlspecialchars($row['typ_rcd']) . "</td>";
            echo "<td>" . htmlspecialchars($row['i_dn']) . "</td><td>" . htmlspecialchars($row['i_zm']) . "</td><td>" . htmlspecialchars($row['ta_zm']) . "</td><td>" . htmlspecialchars($row['test_btn']) . "</td><td class='$klasa'>" . htmlspecialchars($row['wynik']) . "</td></tr>";
        }
        echo "</tbody></table></div>";
    }

    // Podpisy - zawsze na samym dole protokołu
    echo "<div class='signatures'>
            <div class='sig-box'>Osoba wykonująca pomiary (Uprawnienia " . $protokol['uprawnienia_e'] . ")<br><br><br></div>
            <div class='sig-box'>Osoba sprawdzająca/zatwierdzająca (Uprawnienia " . $protokol['uprawnienia_d'] . ")<br><br><br></div>
          </div>";

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

        <form id="monoForm" method="POST" action="index.php">

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
                        <label>Osoba Sprawdzająca (D):</label>
                        <input type="text" name="inzynier_d" id="inzynier_d" required placeholder="Tomasz Nowak">
                    </div>
                    <div class="form-group">
                        <label>Nr uprawnień (D):</label>
                        <input type="text" name="uprawnienia_d" id="uprawnienia_d" required placeholder="D/456/2021">
                    </div>
                </div>
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
                    Z<sub>dopuszczalne</sub> = Z<sub>dop_ciepły</sub> = ( U<sub>0</sub> / I<sub>a</sub> ) * 0.66<br>
                    <strong>Warunek normy: Zmierzona Z<sub>s</sub> &le; Z<sub>dop_ciepły</sub></strong>
                </div>
                <button type="button" class="btn btn-add" onclick="addSwzRow()">+ Dodaj obwód Pętli Zwarcia Zs</button>
                <table id="tbl-swz">
                    <thead>
                        <tr>
                            <th>Nazwa Obwodu</th>
                            <th>Zabezp (B/C/D)</th>
                            <th>Prąd In [A]</th>
                            <th>Zs Zmierzone [Ω]</th>
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
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <input type="hidden" name="rcd_data" id="in_rcd">
            </div>

            <button type="button" class="btn btn-submit" onclick="submitForms()">Zatwierdź pomiary i Zapisz do
                Bazy</button>
        </form>
    </div>

    <script>
        // System przechowywania podręcznego w localStorage na wypadek odświeżenia strony //
        document.addEventListener('DOMContentLoaded', function () {
            const STORAGE_KEY = 'pomiary2_stan_globalny';
            const inputs = Array.from(document.querySelectorAll('#monoForm input[type="text"], #monoForm input[type="date"], #monoForm input[type="number"], #monoForm select'));

            // Zapis tylko głównych informacji nagłówkowych
            const header_ids = ['obiekt_nazwa', 'adres', 'data_pomiaru', 'pogoda', 'uklad_sieci', 'napiecie_u0', 'inzynier_e', 'uprawnienia_e', 'inzynier_d', 'uprawnienia_d'];

            function saveCache() {
                let data = {};
                header_ids.forEach(id => {
                    let el = document.getElementById(id);
                    if (el) data[id] = el.value;
                });
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }

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

            header_ids.forEach(id => {
                let el = document.getElementById(id);
                if (el) el.addEventListener('input', saveCache);
            });
        });

        // -------- RISO DYNAMICS --------
        function addRisoRow() {
            const tbody = document.querySelector('#tbl-riso tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-riso-tr';
            tr.innerHTML = `
            <td><input type="text" class="riso_nazwa" value="Obwód Gniazd" placeholder="Nazwa"></td>
            <td><select class="riso_u"><option value="500">230/400V (500V DC probiercze)</option><option value="250">SELV/PELV (250V DC probiercze)</option></select></td>
            <td><input type="text" class="riso_wymagane" value="1.0" readonly style="background:#eee"></td>
            <td><input type="text" class="riso_zmierzone" value=">999" oninput="recalcRiso(this)"></td>
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
        function addSwzRow() {
            const tbody = document.querySelector('#tbl-swz tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-swz-tr';
            tr.innerHTML = `
            <td><input type="text" class="swz_nazwa" value="Obwód oświetlenia" placeholder="Korytarz główny"></td>
            <td>
                <select class="swz_char">
                    <option value="B">B (krotność=5)</option>
                    <option value="C">C (krotność=10)</option>
                    <option value="D">D (krotność=20)</option>
                </select>
            </td>
            <td><input type="number" step="1" class="swz_in" value="16" ></td>
            <td><input type="number" step="0.01" class="swz_zs_zm" value="0.45" ></td>
        `;
            tbody.appendChild(tr);
        }

        // -------- RCD DYNAMICS --------
        function addRcdRow() {
            const tbody = document.querySelector('#tbl-rcd tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row obw-rcd-tr';
            tr.innerHTML = `
            <td><input type="text" class="rcd_nazwa" value="RCD Gniazd Łazienka"></td>
            <td>
                <select class="rcd_typ">
                    <option value="AC">AC</option>
                    <option value="A">A</option>
                    <option value="B">B (Prostowniki/PV)</option>
                </select>
            </td>
            <td><select class="rcd_idn"><option value="30">30 mA</option><option value="100">100 mA</option><option value="300">300 mA</option></select></td>
            <td><input type="number" class="rcd_izm" value="22.5"></td>
            <td><input type="number" class="rcd_tazm" value="120"></td>
            <td><select class="rcd_testbtn"><option value="Sprawny">Sprawny</option><option value="Uszkodzony">Uszkodzony</option></select></td>
        `;
            tbody.appendChild(tr);
        }

        // Na start dodajemy po 1 przykładowym wierszu
        window.onload = () => { addRisoRow(); addSwzRow(); addRcdRow(); };

        // -------- KOMPILACJA I WYSYŁKA FORMULARZA --------
        function submitForms() {
            let u0 = parseFloat(document.getElementById('napiecie_u0').value) || 230;

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
                let charak = tr.querySelector('.swz_char').value;
                let i_n = parseFloat(tr.querySelector('.swz_in').value);
                let z_szm = parseFloat(tr.querySelector('.swz_zs_zm').value);

                let k = (charak === 'B') ? 5 : ((charak === 'C') ? 10 : 20);
                let i_a = i_n * k;

                // WZOR 2/3: Z_dop * 0.66
                let z_dop = u0 / i_a;
                let z_dop_2_3 = (z_dop * 2) / 3;
                let z_dop_format = z_dop_2_3.toFixed(2);

                let wynikStatus = (z_szm <= parseFloat(z_dop_format)) ? "POZYTYWNY" : "NEGATYWNY";

                swz_arr.push({
                    nazwa: tr.querySelector('.swz_nazwa').value,
                    typ: charak,
                    in: i_n,
                    k: k,
                    ia: i_a,
                    zs_zm: z_szm,
                    zs_dop_skor: z_dop_format,
                    wynik: wynikStatus
                });
            });
            document.getElementById('in_swz').value = JSON.stringify(swz_arr);

            // 4. RCD JSON
            let rcd_arr = [];
            document.querySelectorAll('.obw-rcd-tr').forEach(tr => {
                let zmT = parseFloat(tr.querySelector('.rcd_tazm').value);
                let zmI = parseFloat(tr.querySelector('.rcd_izm').value);
                let idn = parseFloat(tr.querySelector('.rcd_idn').value);
                let btn = tr.querySelector('.rcd_testbtn').value;

                let wynikStatus = "POZYTYWNY";
                // Walidacja - czas do 300ms, prad 0.5-1.0
                if (zmT > 300 || zmI < (0.5 * idn) || zmI > idn || btn === 'Uszkodzony') {
                    wynikStatus = "NEGATYWNY";
                }

                rcd_arr.push({
                    nazwa: tr.querySelector('.rcd_nazwa').value,
                    typ_rcd: tr.querySelector('.rcd_typ').value,
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

</html>