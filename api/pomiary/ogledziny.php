<?php
// APLIKACJA: Elektroniczny Protokół Pomiarowy - Oględziny Wstępne
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
    echo "<title>Protokół Oględzin - $obiekt_nazwa</title>";
    echo "<style>
            body { font-family: 'Times New Roman', serif; line-height: 1.6; margin: 40px; color: #333; }
            h1, h2 { text-align: center; }
           .header-info { margin-bottom: 30px; padding: 15px; border: 1px solid #000; background-color: #f9f9f9; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { text-align: center; background-color: #e0e0e0; }
            td.center { text-align: center; font-weight: bold; }
           .pozytywny { color: green; }
           .negatywny { color: red; }
           .neutralny { color: gray; }
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
    echo "<a href='ogledziny.php' class='no-print' style='margin-left: 15px; text-decoration: none; padding: 10px 20px; background: #eee; color: #000; border: 1px solid #ccc; font-size: 16px;'>Powrót</a>";

    echo "<h1>PROTOKÓŁ Z POMIARÓW ELEKTRYCZNYCH</h1>";
    echo "<h2>Protokół Oględzin Instalacji</h2>";
    echo "<div class='header-info'>";
    echo "<strong>Obiekt:</strong> $obiekt_nazwa <br>";
    echo "<strong>Data oględzin:</strong> $data_pomiaru <br>";
    echo "<strong>Układ sieciowy zasilający:</strong> $uklad_sieci <br>";
    echo "<strong>Napięcie nominalne U0:</strong> $napiecie_u0 V <br>";
    echo "<strong>Podstawa prawna:</strong> PN-HD 60364-6:2016-07<br>";
    echo "<strong>Uwagi metodyczne:</strong> Oględziny przeprowadzono przy wyłączonym napięciu zasilania (gdzie to możliwe). Jest to krytyczny etap weryfikacji przed przystąpieniem do prób metrologicznych.";
    echo "</div>";

    echo "<table>";
    echo "<thead><tr>
            <th style='width: 5%'>Lp.</th>
            <th style='width: 35%'>Obszar kontroli</th>
            <th style='width: 45%'>Wymagania techniczne i normatywne</th>
            <th style='width: 15%'>Wynik oceny</th>
          </tr></thead><tbody>";

    $lp = 1;
    $wszystkie_pozytywne = true;

    foreach ($pomiary as $row) {
        $wynik_class = 'neutralny';
        if ($row['wynik'] === 'POZYTYWNY (P)')
            $wynik_class = 'pozytywny';
        if ($row['wynik'] === 'NEGATYWNY (N)') {
            $wynik_class = 'negatywny';
            $wszystkie_pozytywne = false;
        }

        echo "<tr>";
        echo "<td class='center'>" . $lp++ . "</td>";
        echo "<td>" . htmlspecialchars($row['obszar']) . "</td>";
        echo "<td>" . htmlspecialchars($row['wymagania']) . "</td>";
        echo "<td class='center $wynik_class'>" . htmlspecialchars($row['wynik']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='orzeczenie'>";
    echo "ORZECZENIE Z OGLĘDZIN WIZUALNYCH:<br><br>";
    if ($wszystkie_pozytywne) {
        echo "<span class='pozytywny'>Oględziny NIE WYKAZAŁY rażących uchybień. Stan instalacji pozwala na bezpieczne przystąpienie do pomiarów i prób metrologicznych.</span>";
    }
    else {
        echo "<span class='negatywny'>UWAGA: Oględziny wykazały błędy krytyczne (wynik negatywny). Zgodnie z normą PN-HD 60364-6 prace pomiarowe zostają WSTRZYMANE do czasu usunięcia wykrytych usterek i wykonania ponownych oględzin instalacji.</span>";
    }
    echo "</div>";

    echo "<div class='signatures'>
            <div class='sig-box'>Oględziny przeprowadził (Uprawnienia E)</div>
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
    <title>Protokoły - Oględziny</title>
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
        input[type="date"] {
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

        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }

        .status-ok {
            background-color: #d5f5e3;
            color: #27ae60;
        }

        .status-err {
            background-color: #fadbd8;
            color: #c0392b;
        }

        .status-nd {
            background-color: #f2f2f2;
            color: #7f8c8d;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="nav-menu">
            <a href="index.php">Moduł 1: SWZ (Pętla Zwarcia)</a>
            <a href="rezystancja.php">Moduł 2: Rezystancja Izolacji</a>
            <a href="ogledziny.php" class="active">Moduł 3: Oględziny</a>
            <a href="rcd.php">Moduł 4: Wyłączniki RCD</a>
        </div>

        <h1>Checklista Oględzin Instalacji (PN-HD 60364-6)</h1>
        <form id="protocolForm" method="POST" action="ogledziny.php" onsubmit="prepareDataForSubmit(event)">

            <div class="grid-2">
                <div class="form-group">
                    <label>Nazwa Obiektu Budowlanego:</label>
                    <input type="text" name="obiekt_nazwa" required placeholder="np. Dom jednorodzinny">
                </div>
                <div class="form-group">
                    <label>Data wykonania oględzin:</label>
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

            <h3>Tabela Kontroli Wizualnej</h3>
            <p style="font-size:0.9em; color:#7f8c8d;">Pamiętaj: Wybierz negatywny wynik, jeśli instalacja nosi ślady
                uszkodzeń zagrażające życiu. Każdy negatywny wpis (N) zablokowałby proceduralnie dopuszczenie do
                pomiarów napięciowych przez uprawnionego elektryka D.</p>

            <table id="measurementsTable">
                <thead>
                    <tr>
                        <th style="width: 25%">Obszar kontroli</th>
                        <th style="width: 50%">Wymagania techniczne i normatywne</th>
                        <th style="width: 25%">Wynik z oceny</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Wiersze generowane na sztywno z tabeli 3 norm z czatu -->
                </tbody>
            </table>

            <input type="hidden" id="pomiary_data" name="pomiary_data" value="">

            <br>
            <hr><br>
            <div style="text-align: center;">
                <button type="submit" class="btn" style="font-size: 1.2em; padding: 15px 30px;">Zatwierdź i Generuj
                    Protokół Oględzin</button>
            </div>
        </form>
    </div>

    <script>
        const ogledzinyList = [
            { obszar: 'Stan rozdzielnicy', req: 'Obudowa nieuszkodzona, czytelne opisy obwodów, schemat jednoznaczy i obecny na drzwiczkach' },
            { obszar: 'Ochrona przed dotykiem', req: 'Izolacja przewodów i szyn uziemiających ciągła, brak części czynnych łatwo dostępnych' },
            { obszar: 'Połączenia wyrównawcze', req: 'Widoczna ciągłość połączeń GSU z rurami i konstrukcją metalową budynku. Obecność obejm.' },
            { obszar: 'Kolorystyka żył zasilających', req: 'Weryfikacja kodowania: L (brąz/czarny/szary), N (niebieski), PE/PEN (żółto-zielony)' },
            { obszar: 'Zabezpieczenia RCD', req: 'Dobór i selektywność typu (A/AC), obecność i test funkcjonalności przycisku bimetalicznego TEST' },
            { obszar: 'Osprzęt (gniazda/łączniki)', req: 'Stabilność montażu w puszkach, obecność bolca PE, brak osmoleń i uszkodzeń mechanicznych' },
            { obszar: 'Drogi kablowe', req: 'Prawidłowe mocowanie i dławiki, brak narażeń na ściskanie czy ostre tarcie na przepustach ściennych' }
        ];

        window.onload = function () {
            renderTable();
        };

        function renderTable() {
            const tbody = document.querySelector('#measurementsTable tbody');

            ogledzinyList.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td style="text-align: left; font-weight: bold;">${item.obszar}</td>
                <td style="text-align: left; font-size: 0.95em;">${item.req}</td>
                <td>
                    <select class="obw_wynik" onchange="updateColors(this)">
                        <option value="POZYTYWNY (P)" class="status-ok">Pozytywny (P)</option>
                        <option value="NEGATYWNY (N)" class="status-err">Negatywny (N)</option>
                        <option value="NIE DOTYCZY (ND)" class="status-nd">Nie Dotyczy (ND)</option>
                    </select>
                </td>
            `;
                tbody.appendChild(tr);
                updateColors(tr.querySelector('select'));
            });
        }

        function updateColors(selectElement) {
            if (selectElement.value.includes('POZYTYWNY')) {
                selectElement.className = 'obw_wynik status-ok';
            } else if (selectElement.value.includes('NEGATYWNY')) {
                selectElement.className = 'obw_wynik status-err';
            } else {
                selectElement.className = 'obw_wynik status-nd';
            }
        }

        function prepareDataForSubmit(e) {
            const rows = document.querySelectorAll('#measurementsTable tbody tr');
            let data = [];

            rows.forEach((row, index) => {
                let rowData = {
                    obszar: ogledzinyList[index].obszar,
                    wymagania: ogledzinyList[index].req,
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