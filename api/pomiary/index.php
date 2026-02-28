<?php
// APLIKACJA: Elektroniczny Protokół Pomiarowy - SWZ (Samoczynne Wyłączenie Zasilania)
// Zgodność: PN-HD 60364-6, Prawo Budowlane (Art. 62), C-KOB.
// Architektura: PHP do renderingu formularza/widoku wynikowego, JS do kalkulacji logiki fizycznej.

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($is_submitted) {
    // Faza Renderingu Protokołu (Back-end)
    $obiekt_nazwa = htmlspecialchars($_POST['obiekt_nazwa'] ?? '');
    $uklad_sieci = htmlspecialchars($_POST['uklad_sieci'] ?? '');
    $napiecie_u0 = (float)($_POST['napiecie_u0'] ?? 230);
    $data_pomiaru = htmlspecialchars($_POST['data_pomiaru'] ?? '');
    $pomiary_json = $_POST['pomiary_data'] ?? '[]'; // Dane z tabeli przekazane przez JS w JSON
    $pomiary = json_decode($pomiary_json, true);
    if (!is_array($pomiary))
        $pomiary = [];

    // Generowanie widoku protokołu
    echo "<!DOCTYPE html><html lang='pl'><head><meta charset='UTF-8'>";
    echo "<title>Protokół Kontroli Okresowej - $obiekt_nazwa</title>";
    echo "<style>
            body { font-family: 'Times New Roman', serif; line-height: 1.6; margin: 40px; color: #333; }
            h1, h2 { text-align: center; }
           .header-info { margin-bottom: 30px; padding: 15px; border: 1px solid #000; background-color: #f9f9f9; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
            th, td { border: 1px solid #000; padding: 8px; text-align: center; }
            th { background-color: #e0e0e0; }
           .pozytywny { color: green; font-weight: bold; }
           .negatywny { color: red; font-weight: bold; }
           .orzeczenie { margin-top: 40px; border: 2px solid #000; padding: 20px; font-size: 1.1em; font-weight: bold; }
           .signatures { margin-top: 50px; display: flex; justify-content: space-around; }
           .sig-box { border-top: 1px solid #000; width: 30%; text-align: center; padding-top: 5px; }
            @media print {
                .no-print { display: none; }
                @page { size: A4; margin: 15mm; }
                body { margin: 0; padding: 0; font-size: 11pt; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; page-break-after: auto; }
            }
          </style></head><body>";

    echo "<button class='no-print' onclick='window.print()' style='padding: 10px 20px; font-size: 16px; margin-bottom: 20px; cursor:pointer;'>Drukuj Protokół</button>";
    echo "<a href='index.php' class='no-print' style='margin-left: 15px; text-decoration: none; padding: 10px 20px; background: #eee; color: #000; border: 1px solid #ccc; font-size: 16px;'>Powrót</a>";

    echo "<h1>PROTOKÓŁ Z POMIARÓW ELEKTRYCZNYCH</h1>";
    echo "<h2>Badanie Skuteczności Samoczynnego Wyłączenia Zasilania (SWZ)</h2>";
    echo "<div class='header-info'>";
    echo "<strong>Obiekt:</strong> $obiekt_nazwa <br>";
    echo "<strong>Data pomiaru:</strong> $data_pomiaru <br>";
    echo "<strong>Układ sieciowy zasilający:</strong> $uklad_sieci <br>";
    echo "<strong>Napięcie nominalne fazowe (U0):</strong> $napiecie_u0 V <br>";
    echo "<strong>Podstawa prawna:</strong> PN-HD 60364-6:2016-07, Prawo Budowlane Art. 62<br>";
    echo "<strong>Zasada oceny pętli zwarcia:</strong> Wdrożono rygorystyczny współczynnik temperaturowy 2/3.";
    echo "</div>";

    echo "<table>";
    echo "<thead><tr>
            <th>Lp.</th>
            <th>Nazwa Obwodu / Urządzenia</th>
            <th>Typ Zabezp.</th>
            <th>In [A]</th>
            <th>Krotność</th>
            <th>Prąd Wyłączający Ia [A]</th>
            <th>Impedancja Zmierzona Zs [Ω]</th>
            <th>Z dopuszczalne (korekta 2/3) [Ω]</th>
            <th>Wynik Oceny</th>
          </tr></thead><tbody>";

    $lp = 1;
    $wszystkie_pozytywne = true;

    foreach ($pomiary as $row) {
        $wynik_class = ($row['wynik'] === 'POZYTYWNY') ? 'pozytywny' : 'negatywny';
        if ($row['wynik'] === 'NEGATYWNY')
            $wszystkie_pozytywne = false;

        echo "<tr>";
        echo "<td>" . $lp++ . "</td>";
        echo "<td>" . htmlspecialchars($row['nazwa']) . "</td>";
        echo "<td>" . htmlspecialchars($row['typ_zab']) . "</td>";
        echo "<td>" . htmlspecialchars($row['in']) . "</td>";
        echo "<td>" . htmlspecialchars($row['krotnosc']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ia']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['zs_zm']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['zs_dop']) . "</td>";
        echo "<td class='$wynik_class'>" . htmlspecialchars($row['wynik']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='orzeczenie'>";
    echo "ORZECZENIE O STANIE TECHNICZNYM:<br><br>";
    if ($wszystkie_pozytywne) {
        echo "<span class='pozytywny'>Instalacja w zbadanym zakresie NADAJE SIĘ do bezpiecznej eksploatacji. Wymogi ochrony przy uszkodzeniu zostały spełnione.</span>";
    }
    else {
        echo "<span class='negatywny'>Instalacja NIE NADAJE SIĘ do eksploatacji. Stwierdzono przekroczenie dopuszczalnych parametrów impedancji pętli zwarcia w ujęciu zasady 2/3. Wymagane natychmiastowe prace naprawcze!</span>";
    }
    echo "</div>";

    echo "<div class='signatures'>
            <div class='sig-box'>Pomiary wykonał (Uprawnienia E)</div>
            <div class='sig-box'>Protokół sprawdził (Uprawnienia D)</div>
          </div>";

    echo "</body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator Protokołu SWZ - Panel Inżynierski</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eaeff2;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #34495e;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #bdc3c7;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #34495e;
            color: #fff;
        }

        .btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }

        .btn:hover {
            background-color: #27ae60;
        }

        .btn-add {
            background-color: #3498db;
            margin-bottom: 10px;
        }

        .btn-add:hover {
            background-color: #2980b9;
        }

        .btn-remove {
            background-color: #e74c3c;
            padding: 5px 10px;
        }

        .btn-remove:hover {
            background-color: #c0392b;
        }

        input[readonly] {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }

        .status-ok {
            background-color: #d5f5e3;
            color: #27ae60;
            font-weight: bold;
        }

        .status-err {
            background-color: #fadbd8;
            color: #c0392b;
            font-weight: bold;
        }

        .nav-menu {
            text-align: center;
            margin-bottom: 30px;
        }

        .nav-menu a {
            display: inline-block;
            text-decoration: none;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 5px;
            background: #ecf0f1;
            color: #2c3e50;
            font-weight: bold;
        }

        .nav-menu a.active {
            background: #3498db;
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="nav-menu">
            <a href="index.php" class="active">Moduł 1: SWZ (Pętla Zwarcia)</a>
            <a href="rezystancja.php">Moduł 2: Rezystancja Izolacji</a>
            <a href="ogledziny.php">Moduł 3: Oględziny</a>
            <a href="rcd.php">Moduł 4: Wyłączniki RCD</a>
        </div>

        <h1>Panel Inżynierski - System Akwizycji Danych (PN-HD 60364-6)</h1>
        <form id="protocolForm" method="POST" action="index.php" onsubmit="prepareDataForSubmit(event)">

            <div class="grid-2">
                <div class="form-group">
                    <label>Nazwa Obiektu Budowlanego:</label>
                    <input type="text" name="obiekt_nazwa" required
                        placeholder="np. Budynek biurowy, ul. Przemysłowa 5">
                </div>
                <div class="form-group">
                    <label>Data wykonania pomiaru:</label>
                    <input type="date" name="data_pomiaru" required>
                </div>
                <div class="form-group">
                    <label>Układ sieciowy zasilający:</label>
                    <select id="uklad_sieci" name="uklad_sieci" required>
                        <option value="TN-S">TN-S (Separowany PE i N)</option>
                        <option value="TN-C">TN-C (Wspólny PEN)</option>
                        <option value="TN-C-S">TN-C-S (Punkt podziału)</option>
                        <option value="TT">TT (Uziemienie indywidualne)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Napięcie nominalne względem ziemi U0 [V]:</label>
                    <input type="number" id="napiecie_u0" name="napiecie_u0" value="230" required
                        onchange="recalculateAll()">
                </div>
            </div>

            <h3>Rejestr Pomiarów Impedancji Pętli Zwarciowej</h3>
            <p style="font-size:0.9em; color:#7f8c8d;">Algorytm automatycznie uwzględnia temperaturowy współczynnik
                korekcyjny zjawiska grzania przewodów podczas zwarcia (2/3 z Zs). Charakterystyki czasowe dla 0.4s:
                B=5x, C=10x, D=20x.</p>

            <button type="button" class="btn btn-add" onclick="addRow()">+ Dodaj nowy obwód do pomiaru</button>

            <table id="measurementsTable">
                <thead>
                    <tr>
                        <th style="width: 25%">Nazwa Obwodu</th>
                        <th style="width: 10%">Typ (Charakt.)</th>
                        <th style="width: 10%">In [A]</th>
                        <th style="width: 10%">Krotność</th>
                        <th style="width: 10%">Ia [A] (Kalkul.)</th>
                        <th style="width: 12%">Zmierzona Zs [Ω]</th>
                        <th style="width: 10%">Dopuszcz. Zs [Ω] (Wzór 2/3)</th>
                        <th style="width: 8%">Wynik</th>
                        <th style="width: 5%">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <input type="hidden" id="pomiary_data" name="pomiary_data" value="">

            <br>
            <hr><br>
            <div style="text-align: center;">
                <button type="submit" class="btn" style="font-size: 1.2em; padding: 15px 30px;">Generuj Oficjalny
                    Protokół z Orzeczeniem</button>
            </div>
        </form>
    </div>

    <script>
        // Inicjalizacja pierwszego wiersza
        window.onload = function () {
            addRow();
        };

        function addRow() {
            const tbody = document.querySelector('#measurementsTable tbody');
            const rowId = Date.now(); // unikalne ID wiersza

            const tr = document.createElement('tr');
            tr.id = 'row_' + rowId;

            tr.innerHTML = `
            <td><input type="text" class="obw_nazwa" placeholder="np. Gniazda pokój 1" required></td>
            <td>
                <select class="obw_typ" onchange="calculateRow(${rowId})">
                    <option value="B">B (Instalacyjny)</option>
                    <option value="C">C (Instalacyjny)</option>
                    <option value="D">D (Instalacyjny)</option>
                    <option value="gG">Wkładka gG</option>
                </select>
            </td>
            <td><input type="number" class="obw_in" value="16" min="1" step="1" oninput="calculateRow(${rowId})" required></td>
            <td><input type="number" class="obw_krotnosc" value="5" min="1" step="0.1" oninput="calculateRow(${rowId})" required></td>
            <td><input type="text" class="obw_ia" readonly></td>
            <td><input type="number" class="obw_zszm" step="0.01" value="0.00" oninput="calculateRow(${rowId})" required></td>
            <td><input type="text" class="obw_zsdop" readonly></td>
            <td><input type="text" class="obw_wynik" readonly style="text-align:center;"></td>
            <td><button type="button" class="btn btn-remove" onclick="removeRow(${rowId})">X</button></td>
        `;
            tbody.appendChild(tr);
            calculateRow(rowId);
        }

        function removeRow(rowId) {
            const tr = document.getElementById('row_' + rowId);
            if (tr) tr.remove();
        }

        function recalculateAll() {
            const rows = document.querySelectorAll('#measurementsTable tbody tr');
            rows.forEach(row => {
                const id = row.id.split('_')[1];
                if (id) calculateRow(parseInt(id));
            });
        }

        // Rdzeń logiczny aplikacji - Implementacja wymagań normy PN-HD 60364-6
        function calculateRow(rowId) {
            const row = document.getElementById('row_' + rowId);
            if (!row) return;

            const u0 = parseFloat(document.getElementById('napiecie_u0').value);
            const typ = row.querySelector('.obw_typ').value;
            const currentIn = parseFloat(row.querySelector('.obw_in').value);
            const krotnoscInput = row.querySelector('.obw_krotnosc');

            let multiplier = parseFloat(krotnoscInput.value);

            // Algorytm doboru krotności prądu Ia dla czasu 0.4s w zależności od charakterystyki
            // Norma nakazuje przyjmować górną granicę pasma zadziałania.
            if (document.activeElement !== krotnoscInput) { // Zmień automatycznie tylko jeśli user sam nie wpisuje krotności
                switch (typ) {
                    case 'B': multiplier = 5; break;
                    case 'C': multiplier = 10; break;
                    case 'D': multiplier = 20; break;
                    // Dla gG (bezpiecznik topikowy) krotności są nieliniowe (odczyt z ch-ki czasowo-prądowej), zostawiamy swobodę wpisu lub wartość default.
                }
                krotnoscInput.value = multiplier;
            }

            const Ia = currentIn * multiplier;
            row.querySelector('.obw_ia').value = Ia.toFixed(2);

            // Fizyka ochrony przeciwporażeniowej: Zs * Ia <= U0  => Zs <= U0 / Ia
            const zs_theoretical = u0 / Ia;

            // Zastosowanie współczynnika rygoru temperaturowego (2/3) złącz miedzianych wg PN-HD 60364-6
            const zs_corrected = zs_theoretical * (2 / 3);
            row.querySelector('.obw_zsdop').value = zs_corrected.toFixed(2);

            const measuredZs = parseFloat(row.querySelector('.obw_zszm').value);
            const wynikInput = row.querySelector('.obw_wynik');

            // Walidacja bezpieczeństwa instalacji
            if (measuredZs > 0 && measuredZs <= zs_corrected) {
                wynikInput.value = "POZYTYWNY";
                wynikInput.className = "obw_wynik status-ok";
            } else {
                wynikInput.value = "NEGATYWNY";
                wynikInput.className = "obw_wynik status-err";
            }
        }

        // Konwersja danych HTML Table do obiektu JSON w celu bezstratnej transmisji do PHP
        function prepareDataForSubmit(e) {
            const rows = document.querySelectorAll('#measurementsTable tbody tr');
            let data = []; // FIX syntax error here

            rows.forEach(row => {
                let rowData = {
                    nazwa: row.querySelector('.obw_nazwa').value,
                    typ_zab: row.querySelector('.obw_typ').value,
                    in: row.querySelector('.obw_in').value,
                    krotnosc: row.querySelector('.obw_krotnosc').value,
                    ia: row.querySelector('.obw_ia').value,
                    zs_zm: row.querySelector('.obw_zszm').value,
                    zs_dop: row.querySelector('.obw_zsdop').value,
                    wynik: row.querySelector('.obw_wynik').value
                };
                data.push(rowData);
            });

            document.getElementById('pomiary_data').value = JSON.stringify(data);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const STORAGE_KEY = 'pomiary_stan_header';
            const fields = ['obiekt_nazwa', 'data_pomiaru', 'uklad_sieci', 'napiecie_u0'];
            function restoreState() {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    try {
                        const data = JSON.parse(saved);
                        fields.forEach(f => {
                            const el = document.querySelector('[name="' + f + '"]');
                            if (el && data[f]) {
                                el.value = data[f];
                                el.dispatchEvent(new Event('change'));
                            }
                        });
                    } catch (e) { }
                }
            }
            function saveState() {
                const data = {};
                fields.forEach(f => {
                    const el = document.querySelector('[name="' + f + '"]');
                    if (el) data[f] = el.value;
                });
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }
            restoreState();
            fields.forEach(f => {
                const el = document.querySelector('[name="' + f + '"]');
                if (el) { el.addEventListener('input', saveState); el.addEventListener('change', saveState); }
            });
        });
    </script>
</body>

</html>