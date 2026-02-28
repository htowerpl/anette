# Baza Wiedzy i Wytyczne Normatywne
## System Pomiary Okresowe Instalacji Elektrycznych

Poniżej znajduje się skondensowany wyciąg zasad (Knowledge Items) na podstawie teorii metrologii i wytycznych prawnych (Prawo Budowlane, PN-HD 60364-6, PN-EN 61557), który stanowi fundamentalną bazę dla logiki algorytmów obliczeniowych w aplikacji `Pomiary`.

### 1. Aspekty Prawno-Administracyjne
*   **Obowiązek Prawny:** Kontrole instalacji (sprawdzenie połączeń, osprzętu, izolacji, uziemień i aparatury) dla obiektów budowlanych muszą być wykonywane co najmniej dyspozycyjnie **raz na 5 lat** (Art. 62 Prawo Budowlane).
*   **Ważność ubezpieczeniowa:** Brak protokołu jest traktowany jako rażące niedbalstwo skutkujące regresem lub odmową wypłaty odszkodowania pożarowego.
*   **Kwalifikacje Personelu (Prawo Energetyczne):** 
    *   **Uprawnienia D (Dozór):** Osoba zatwierdzająca protokół, bierze odpowiedzialność prawną za orzeczenie. Posiada uprawnienia pomiarowe ważne max. 5 lat.
    *   **Uprawnienia E (Eksploatacja):** Osoba fizycznie asystująca lub realizująca pomiary. Zawsze wymaga kotrasygnaty "D" na dokumencie.
*   **Archiwizacja (C-KOB):** Protokoły od 2023 r. wprowadzane są elektronicznie do Cyfrowej Książki Obiektu Budowlanego, co wymusza pełną standaryzację nazewnictwa w aplikacji webowej.

### 2. Typologia Układów Sieciowych (warunkująca logikę aplikacji)
*   **TN-C (Historyczny):** Wspólny przewód PEN. Wysokie ryzyko porażeniowe przy przerwaniu PEN. RCD - bezwzględnie niedopuszczalne! (tylko stacjonarne pow. 10mm2 Cu). 
*   **TN-S (Współczesny):** Rozdzielone żyły N i PE z bezpośrednim uziemieniem ochronnym. Powszechne użycie zabezpieczeń nadprądowych i różnicowoprądowych (RCD). Fundament ochrony: niska impedancja pętli zwarcia ($Z_s$).
*   **TT (Wysoka impedancja):** Pętla uziomowa. Zabezpieczenia prądowe są nieskuteczne w walce z porażeniem. Algorytm w aplikacji musi polegać na weryfikacji rezystancji uziemienia ochronnego $R_A$ oraz wyłącznie na aparatach RCD.
*   **IT (Sieć izolowana):** Obiekty krytyczne (medyczne/przemysłowe). Podstawowa zaleta: pierwsze zwarcie z PE nie wyzwala zasilania. Bazuje na monitorach stanu izolacji (IMD).

### 3. Wymagania Metrologiczne i Błędy Przyrządów
*   Zgodnie z serią PN-EN 61557 dopuszczalne błędy robocze w terenie:
    *   **Riso, Zs, Rezystancja uziemień, Ciągłość** = $\pm30\%$
    *   **Parametry RCD** = $\pm10\%$ (dla prądu).
*   *Zasada Inżynierska:* Zmierzona Wartość bliska granicy błędu (np. zaledwie o 5% lepsza od maksymalnego dopuszczalnego czasu wyzwolenia) traktowana jest jako wątpliwa strefa czerwona.
*   **Wzorcowanie** Mierniki powinny mieć ważne świadectwo wzorcowania (przyjmuje się okres roczny 12 m-cy). Bez tego dowodu cały protokół jest bezwartościowy prawnie w sądzie.

### 4. Protokołowanie Metodologii Pomiarów w PN-HD 60364-6
Aplikacja kalkulacyjna musi wymuszać bezwzględną kolejność badań inżyniera:
1.  **Oględziny:** Pierwszy krok. Wykrycie uszkodzeń, weryfikowanie braku dotyku bezpośredniego i dróg kablowych przy odłączonym napięciu.
2.  **Ciągłość Przewodów [PE]:** (U: 4-24V, I > 200mA). Oczekiwana $R \approx 0~\Omega$. Opór powyżej 1-2 $\Omega$ generuje alarm u pomiarowca.
3.  **Rezystancja Izolacji [$R_{iso}$]:** Krok przeciwpożarowy. Napięcie nominalne 230/400V wymaga podania $500\text{V DC}$. Tolerowane minimalne $R_{iso} = 1,0\text{ M}\Omega$. Możliwe wpisy w aplikacji typu `>999` $\text{M}\Omega$. Norma dopuszcza łączenie żył L+N względem PE.
4.  **Pętla Zwarcia [$Z_s$]:** (Badanie pod napięciem). Prawo Ohma wymusza szybszy czas reakcji zabezpieczenia: $Z_{zs} \le U_0 / (k \cdot I_n)$.
    *   $k$ = prąd wyłączający Ia krotność dla B/C/D.
    *   *Margines dla starych / nowych instalacji:* Współczynnik dla weryfikacji bezpieczeństwa termicznego $Z_s \le 2/3 Z_{dop}$ w temperaturach roboczych pow. 70 stopni. 
5.  **Test RCD ($t_A, I_\Delta$):**
    *   Testy realizuje się w układzie prądu nominalnego $1\times I_{\Delta n}$ (max czas: 300 ms).
    *   Druga runda pomiarów u inżynierów w normach: pobudzenie obwodu w czasie 40ms prądem $5\times I_{\Delta n}$ (czyli np 150 mA dla RCD 30 mA).
6.  **Pomiary Uziemień i GSW/MSW:** Cel uziomu obwodowego = $<10~\Omega$ w normach piorunowych. MSW obligatoryjne dla stref mokrych (Łazienki - strefy 0/1/2).

### 5. Wyzwania Związane z Fotowoltaiką (OZE) i LED
*   Moduły muszą pozwalać na dopuszczanie aparatów specjalnych dla urządzeń produkujących szum i wprowadzających składową prądu stałego (DC).
*   **OZE/PV:** Aplikacja protokołowa powinna umożliwiać wprowadzenie na listę różnicówek **typu B** (przekszatłtniki w autach, falowniki).
*   **LED:** Wyższe harmoniczne odkształceń (THD). Należy w aplikacjach przemysłowych zostawić pole na pomiar upływności na samym pojedynczym przewodzie N z powodu ryzyka sumowania wektorów przez zasilacze impulsowe.

### 6. Architektura Protokołu w Aplikacji
Program musi bezwzględnie wylistować na finalnym widoku druku HTML do PDF:
- Metrykę (Zleceniodawca, Adres, Data).
- Układ Sieci i Napięcie Robocze ($U_{0}$).
- Informację o wpisach do świadectwa wzorcowania miernika.
- Warunki fizyczne (Pogoda, gleba wilgotność).
- Tabelaryczne, wyliczone stany obwodów (Riso, Zs, tA).
- Ostateczne Orzeczenie (TAK - NADAJE SIĘ vs NIE NADAJE SIĘ - WYŁĄCZENIE).
- Dwa miejsca na podpisy z pieczęciami kwalifikacji Sep "E" oraz "D".
