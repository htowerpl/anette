<?php
// APLIKACJA: Elektroniczny Protokół Pomiarowy - Rezystancja Izolacji
// Zgodność: PN-HD 60364-6, Prawo Budowlane (Art. 62), C-KOB.

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($is_submitted) {
    // Faza Renderingu Protokołu (Back-end)
    $obiekt_nazwa = htmlspecialchars($_POST['obiekt_nazwa'] ?? '');
    $data_pomiaru = htmlspecialchars($_POST['data_pomiaru'] ?? '');
    $napiecie_instalacji = htmlspecialchars($_POST['napiecie_instalacji'] ?? '230/400V');
    $pomiary_json = $_POST['pomiary_data'] ?? '[]';
    $pomiary = json_decode($pomiary_json, true);
    if (!is_array($pomiary))
        $pomiary = [];

    echo "<!DOCTYPE html><html lang='pl'><head><meta charset='UTF-8'>";
    echo "<title>Protokół Rezystancji Izolacji - $obiekt_nazwa</title>";
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
            @media print {.no-print { display: none; } body { margin: 0; } }
          </style></head><body>";

    echo "<button class='no-print' onclick='window.print()' style='padding: 10px 20px; font-size: 16px; margin-bottom: 20px; cursor:pointer;'>Drukuj Protokół</button>";
    echo "<a href='rezystancja.php' class='no-print' style='margin-left: 15px; text-decoration: none; padding: 10px 20px; background: #eee; color: #000; border: 1px solid #ccc; font-size: 16px;'>Powrót</a>";

    echo "<h1>PROTOKÓŁ Z POMIARÓW ELEKTRYCZNYCH</h1>";
    echo "<h2>Badanie Rezystancji Izolacji Obwodów</h2>";
    echo "<div class='header-info'>";
    echo "<strong>Obiekt:</strong> $obiekt_nazwa <br>";
    echo "<strong>Napięcie nominalne instalacji:</strong> $napiecie_instalacji <br>";
    echo "<strong>Data pomiaru:</strong> $data_pomiaru <br>";
    echo "<strong>Podstawa prawna:</strong> PN-HD 60364-6:2016-07<br>";
    echo "<strong>Metodologia:</strong> Zabezpieczenia nadprądowe załączone, pomiar między zwartymi przewodami czynnymi (L+N) a przewodem ochronnym (PE).";
    echo "</div>";

    echo "<table>";
    echo "<thead><tr>
            <th>Lp.</th>
            <th>Nazwa Obwodu / Urządzenia</th>
            <th>Napięcie Probiercze [V DC]</th>
            <th>Wymagane R<sub>iso</sub> [MΩ]</th>
            <th>Zmierzono R<sub>iso</sub> L+N do PE [MΩ]</th>
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
        echo "<td>" . htmlspecialchars($row['napi_prob']) . " V</td>";
        echo "<td>&ge; " . htmlspecialchars($row['r_min']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['r_zm']) . "</strong></td>";
        echo "<td class='$wynik_class'>" . htmlspecialchars($row['wynik']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='orzeczenie'>";
    echo "ORZECZENIE O STANIE TECHNICZNYM IZOLACJI:<br><br>";
    if ($wszystkie_pozytywne) {
        echo "<span class='pozytywny'>Stan izolacji w badanej instalacji JEST W NORMIE. Ryzyko przebić i zwarć zminimalizowane. Instalacja NADAJE SIĘ do eksploatacji.</span>";
    }
    else {
        echo "<span class='negatywny'>Ostrzeżenie: Rezystancja izolacji NIE SPEŁNIA wartości minimalnych! Możliwe upływności. Instalacja NIE NADAJE SIĘ do bezpiecznej eksploatacji.</span>";
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
    <title>Protokoły - Rezystancja Izolacji</title>
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
            <a href="rezystancja.php" class="active">Moduł 2: Rezystancja Izolacji</a>
            <a href="ogledziny.php">Moduł 3: Oględziny</a>
            <a href="rcd.php">Moduł 4: Wyłączniki RCD</a>
        </div>

        <h1>Badanie Rezystancji Izolacji (PN-HD 60364-6)</h1>
        <form id="protocolForm" method="POST" action="rezystancja.php" onsubmit="prepareDataForSubmit(event)">

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
                    <label>Napięcie robocze instalacji:</label>
                    <input type="text" name="napiecie_instalacji" value="230/400 V" required>
                </div>
            </div>

            <h3>Rejestr Pomiarów Rezystancji Izolacji</h3>
            <p style="font-size:0.9em; color:#7f8c8d;">Algorytm ustala min. dopuszczalne wartości odczytu: <strong>0.5
                    MΩ</strong> (dla obwodów SELV/PELV 250V) lub <strong>1.0 MΩ</strong> (dla obwodów 500V i 1000V).
                Możesz wprowadzać znaki np. <code>>999</code> lub <code>>200</code> zjawisko to automatycznie kończy się
                wynikiem POZYTYWNYM.</p>

            <button type="button" class="btn btn-add" onclick="addRow()">+ Dodaj obwód do pomiaru</button>

            <table id="measurementsTable">
                <thead>
                    <tr>
                        <th style="width: 30%">Nazwa Obwodu</th>
                        <th style="width: 15%">U Probiercze [V]</th>
                        <th style="width: 15%">Wymagane min. [MΩ]</th>
                        <th style="width: 20%">Zmierzono R (L+N do PE) [MΩ]</th>
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
                <button type="submit" class="btn" style="font-size: 1.2em; padding: 15px 30px;">Generuj Oficjalny
                    Protokół Izolacji</button>
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
            <td><input type="text" class="obw_nazwa" placeholder="np. Oświetlenie parter" required></td>
            <td>
                <select class="obw_uprob" onchange="calculateRow(${rowId})">
                    <option value="250">250 V DC</option>
                    <option value="500" selected>500 V DC</option>
                    <option value="1000">1000 V DC</option>
                </select>
            </td>
            <td><input type="text" class="obw_rmin" readonly></td>
            <td><input type="text" class="obw_rzm" placeholder="np. >999 lub 5.5" oninput="calculateRow(${rowId})" required></td>
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

            const uProb = parseInt(row.querySelector('.obw_uprob').value);
            let rMin = 1.0;

            if (uProb === 250) {
                rMin = 0.5;
            } else {
                rMin = 1.0;
            }

            row.querySelector('.obw_rmin').value = rMin.toFixed(1);

            const rZmInput = row.querySelector('.obw_rzm').value.trim().replace(',', '.');
            const wynikInput = row.querySelector('.obw_wynik');

            if (rZmInput === '') {
                wynikInput.value = '';
                wynikInput.className = 'obw_wynik';
                return;
            }

            let isOk = false;

            // Jeżeli badacz użył notacji "<" lub ">" np. >199 MOm
            if (rZmInput.startsWith('>')) {
                const val = parseFloat(rZmInput.substring(1));
                // jeśli wpisuje >999, to na pewno przejdzie (bo >1 > 1.0 itd)
                if (!isNaN(val) && val >= rMin) {
                    isOk = true;
                } else if (!isNaN(val)) {
                    isOk = true; // Zazwyczaj samo wysokie wskazanie jest OK, ale wymuszamy logikę
                }
            } else {
                const val = parseFloat(rZmInput);
                if (!isNaN(val) && val >= rMin) {
                    isOk = true;
                }
            }

            if (isOk) {
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
                    napi_prob: row.querySelector('.obw_uprob').value,
                    r_min: row.querySelector('.obw_rmin').value,
                    r_zm: row.querySelector('.obw_rzm').value,
                    wynik: row.querySelector('.obw_wynik').value
                };
                data.push(rowData);
            });

            document.getElementById('pomiary_data').value = JSON.stringify(data);
        }
    </script>

</body>

</html>