<?php
// APLIKACJA: Elektroniczny Protokół Pomiarowy - Wyłączniki RCD
// Zgodność: PN-HD 60364-6, Prawo Budowlane (Art. 62), C-KOB.

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($is_submitted) {
    // Faza Renderingu Protokołu (Back-end)
    $obiekt_nazwa = htmlspecialchars($_POST['obiekt_nazwa'] ?? '');
    $data_pomiaru = htmlspecialchars($_POST['data_pomiaru'] ?? '');
    $uklad_sieci = htmlspecialchars($_POST['uklad_sieci'] ?? '');
    $napiecie_u0 = htmlspecialchars($_POST['napiecie_u0'] ?? '230');
    $pomiary_json = $_POST['pomiary_data'] ?? '[]';
    $pomiary = json_decode($pomiary_json, true);
    if (!is_array($pomiary))
        $pomiary = [];

    echo "<!DOCTYPE html><html lang='pl'><head><meta charset='UTF-8'>";
    echo "<title>Protokół RCD - $obiekt_nazwa</title>";
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
    echo "<a href='rcd.php' class='no-print' style='margin-left: 15px; text-decoration: none; padding: 10px 20px; background: #eee; color: #000; border: 1px solid #ccc; font-size: 16px;'>Powrót</a>";

    echo "<h1>PROTOKÓŁ Z POMIARÓW ELEKTRYCZNYCH</h1>";
    echo "<h2>Badanie Wyłączników Różnicowoprądowych (RCD)</h2>";
    echo "<div class='header-info'>";
    echo "<strong>Obiekt:</strong> $obiekt_nazwa <br>";
    echo "<strong>Data pomiaru:</strong> $data_pomiaru <br>";
    echo "<strong>Układ sieciowy zasilający:</strong> $uklad_sieci <br>";
    echo "<strong>Napięcie nominalne U0:</strong> $napiecie_u0 V <br>";
    echo "<strong>Podstawa prawna:</strong> PN-HD 60364-6:2016-07 / PN-EN 61557-6<br>";
    echo "<strong>Normatywy czasowe:</strong> Wymagane zadziałanie poniżej 300 ms przy nominalnym prądzie zadziałania I&Delta;n.";
    echo "</div>";

    echo "<table>";
    echo "<thead><tr>
            <th>Lp.</th>
            <th>Identyfikacja Aparatu RCD</th>
            <th>Typ</th>
            <th>I&Delta;n [mA]</th>
            <th>Prąd zadz. I&Delta; [mA] (zmierzony)</th>
            <th>Czas zadz. tA [ms] (przy 1x I&Delta;n)</th>
            <th>Przycisk TEST</th>
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
        echo "<td>" . htmlspecialchars($row['typ']) . "</td>";
        echo "<td>" . htmlspecialchars($row['idn']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['id_zm']) . "</strong></td>";
        echo "<td><strong>" . htmlspecialchars($row['ta_zm']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['test_btn']) . "</td>";
        echo "<td class='$wynik_class'>" . htmlspecialchars($row['wynik']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='orzeczenie'>";
    echo "ORZECZENIE O STANIE TECHNICZNYM WYŁĄCZNIKÓW RCD:<br><br>";
    if ($wszystkie_pozytywne) {
        echo "<span class='pozytywny'>RCD SPRAWNE. Czasy i prody zadziałania wszystkich urządzeń, jak i badanie manualne, zawierają się w tolerancjach wdrożonych z norm PN-EN 61557 i dają gwarancję ochrony dodatkowej porażeniowej.</span>";
    }
    else {
        echo "<span class='negatywny'>RCD NIESPRAWNE! Detekcja uszkodzeń w parametrach czasu lub prądu upływu. Stanowi to podważenie fundamentów bezpieczeństwa dla urządzeń podłączonych. Elementy wadliwe do wymiany!</span>";
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
    <title>Protokoły - Wyłączniki RCD</title>
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
            margin-top: 0;
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
        input[type="date"],
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
    </style>
</head>

<body>

    <div class="container">
        <div class="nav-menu">
            <a href="index.php">Moduł 1: SWZ (Pętla Zwarcia)</a>
            <a href="rezystancja.php">Moduł 2: Rezystancja Izolacji</a>
            <a href="ogledziny.php">Moduł 3: Oględziny</a>
            <a href="rcd.php" class="active">Moduł 4: Wyłączniki RCD</a>
        </div>

        <h1>Badanie Wyłączników RCD (PN-EN 61557-6)</h1>
        <form id="protocolForm" method="POST" action="rcd.php" onsubmit="prepareDataForSubmit(event)">

            <div class="grid-2">
                <div class="form-group">
                    <label>Nazwa Obiektu Budowlanego:</label>
                    <input type="text" name="obiekt_nazwa" required placeholder="np. Dom jednorodzinny">
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
                        <option value="IT">IT (Sieć izolowana)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Napięcie nominalne względem ziemi U0 [V]:</label>
                    <input type="number" id="napiecie_u0" name="napiecie_u0" value="230" required>
                </div>
            </div>

            <h3>Rejestr pomiarowy wyłączników</h3>
            <p style="font-size:0.9em; color:#7f8c8d;">Algorytm ustala prawidłowość parametrów. Dla typowych aparatów
                (np. 30mA) bezpieczny próg zadziałania prądowego to <strong>[ 0.5 x IΔn ; 1.0 x IΔn ]</strong>.
                Jednocześnie czas otwarcia zestyków standardowych urządzeń ma być drastycznie krótszy niż graniczne 300
                ms.</p>

            <button type="button" class="btn btn-add" onclick="addRow()">+ Dodaj nowy aparat RCD do badania</button>

            <table id="measurementsTable">
                <thead>
                    <tr>
                        <th style="width: 20%">Identyfikator (nazwa/nr)</th>
                        <th style="width: 10%">Typ (A/AC/B)</th>
                        <th style="width: 10%">IΔn [mA]</th>
                        <th style="width: 15%">Zmierzony prąd zadziałania IΔ [mA]</th>
                        <th style="width: 15%">Czas tA (zadziałania) [ms]</th>
                        <th style="width: 10%">Test ręczny</th>
                        <th style="width: 15%">Wynik Oceny</th>
                        <th style="width: 5%">Usuń</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <input type="hidden" id="pomiary_data" name="pomiary_data" value="">

            <br>
            <hr><br>
            <div style="text-align: center;">
                <button type="submit" class="btn" style="font-size: 1.2em; padding: 15px 30px;">Zatwierdź i Generuj
                    Protokół RCD</button>
            </div>
        </form>
    </div>

    <script>
        window.onload = function () {
            addRow();
        };

        function addRow() {
            const tbody = document.querySelector('#measurementsTable tbody');
            const rowId = Date.now();

            const tr = document.createElement('tr');
            tr.id = 'row_' + rowId;

            tr.innerHTML = `
            <td><input type="text" class="obw_nazwa" placeholder="RCD-1 Łazienka" required></td>
            <td>
                <select class="obw_typ" onchange="calculateRow(${rowId})">
                    <option value="AC" selected>AC</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                </select>
            </td>
            <td>
                <select class="obw_idn" onchange="calculateRow(${rowId})">
                    <option value="10">10 mA</option>
                    <option value="30" selected>30 mA</option>
                    <option value="100">100 mA</option>
                    <option value="300">300 mA</option>
                    <option value="500">500 mA</option>
                </select>
            </td>
            <td><input type="number" class="obw_idzm" step="0.1" value="0.0" oninput="calculateRow(${rowId})" required></td>
            <td><input type="number" class="obw_tazm" step="1.0" value="0" oninput="calculateRow(${rowId})" required></td>
            <td>
                <select class="obw_test" onchange="calculateRow(${rowId})">
                    <option value="Sprawny" selected>Tak/OK</option>
                    <option value="Niesprawny">Zepsuty</option>
                </select>
            </td>
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

        function calculateRow(rowId) {
            const row = document.getElementById('row_' + rowId);
            if (!row) return;

            const idn = parseInt(row.querySelector('.obw_idn').value); // w mA
            const idZmInput = parseFloat(row.querySelector('.obw_idzm').value); // zmierzony w mA
            const taZmInput = parseFloat(row.querySelector('.obw_tazm').value); // czas zadzialania w ms
            const testBtn = row.querySelector('.obw_test').value; // sprawność manualna

            const wynikInput = row.querySelector('.obw_wynik');

            if (isNaN(idZmInput) || isNaN(taZmInput) || idZmInput <= 0) {
                wynikInput.value = '';
                wynikInput.className = 'obw_wynik';
                return;
            }

            // Granice parametrow RCD: czas < 300 ms dla domyslnych bezwzlocznych. Półkrotność < I∆ < Jednokrotność
            let isPrzeplywOk = false;
            if (idZmInput >= (idn * 0.5) && idZmInput <= idn) {
                isPrzeplywOk = true;
            }

            let isCzasOk = false;
            if (taZmInput > 0 && taZmInput <= 300) {
                isCzasOk = true;
            }

            if (isPrzeplywOk && isCzasOk && testBtn === 'Sprawny') {
                wynikInput.value = "POZYTYWNY";
                wynikInput.className = "obw_wynik status-ok";
            } else {
                wynikInput.value = "NEGATYWNY";
                wynikInput.className = "obw_wynik status-err";
            }
        }

        function prepareDataForSubmit(e) {
            const rows = document.querySelectorAll('#measurementsTable tbody tr');
            let data = [];

            rows.forEach(row => {
                let rowData = {
                    nazwa: row.querySelector('.obw_nazwa').value,
                    typ: row.querySelector('.obw_typ').value,
                    idn: row.querySelector('.obw_idn').value,
                    id_zm: row.querySelector('.obw_idzm').value,
                    ta_zm: row.querySelector('.obw_tazm').value,
                    test_btn: row.querySelector('.obw_test').value,
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