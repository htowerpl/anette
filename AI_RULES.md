# Dokumentacja Projektowa i Zasady Współpracy (Pamięć AI)

Ten plik służy jako pamięć długotrwała dla asystenta AI. Należy go czytać na początku każdej sesji, aby zrozumieć kontekst, styl i historię zmian.

## Słownik komend
- **"No i Git!"** -> Oznacza akceptację zmian i prośbę o wygenerowanie komend git:
  1. `git add .`
  2. `git commit -m "Opis zmian"`
  3. `git push`

## Kluczowe Ustalenia Techniczne
1.  **Dziennik Zmian (ChangeLog / AI_RULES)**: **ZASADA BEZWZGLĘDNA** - przed wykonaniem commita lub całkowitym ukończeniem zadania, wszystkie zaimplementowane udoskonalenia i poprawki muszą zostać niezwłocznie przypisane do sekcji "*Historia Decyzji i Zmian (Log)*" w tym pliku. To nasza stała "Pamięć Długotrwała". Niewykonanie tego to błąd krytyczny.
2.  **Struktura One-Page / Multi-Page**: Projekt jest hybrydą. Główne sekcje są na osobnych podstronach (`pages/`), ale nawigacja i stopka są wstrzykiwane dynamicznie (`loadComponent` w `app.js`) z plików `_nav.html` i `_footer.html`.
3.  **Backend**: Prosty PHP (`news.php`) zwracający JSON. Dane pochodzą z **ręcznej tabeli `Anette_news`**. Automatyzacja została wyłączona z powodu blokady API Google.
4.  **Frontend**: Czysty JS (Vanilla). Brak frameworków typu React/Vue. Style w `styles.css` oparte na zmiennych CSS.
5.  **Konfiguracja Serwera**: Pliki z danymi wrażliwymi (DB, OAuth) znajdują się w bezpiecznym katalogu `/home/opxwpceo/domains/google/` i są dołączane przez absolutne ścieżki.
6.  **Środowisko Lokalne**: Użytkownik nie posiada zainstalowanego interpretera PHP na swojej maszynie lokalnej. Zabronione jest używanie przez AI komend terminalowych typu `php -S` lub `php -l` w celu testowania backendu lub sprawdzania składni. Weryfikacja kodu musi odbywać się statycznie lub za pośrednictwem serwera zewnętrznego użytkownika.
7.  **Rzetelność i Brak Regresji (Zero Zgadywania)**: Projekt jest traktowany bardzo poważnie. Przebudowując kod (np. PHP/HTML) należy rygorystycznie uważać na utratę istniejącej funkcjonalności (jak np. zgubienie stanu `$_GET` przy zapisie, co skutkowało zniknięciem przycisków UX). Jeśli AI czegoś "nie wie" lub ma wątpliwości co do architektury – ma o tym poinformować wprost, zamiast wymyślać lub zgadywać w ciemno.

## Historia Decyzji i Zmian (Log)

### Optymalizacja pod AI Crawlery (AEO)
- **Punkt Przywracania (Checkpoint)**: Ustanowiono stabilny punkt przywracania przed wdrożeniem tekstowym (Po zatwierdzonym commicie: `b98ba42`). Zabezpiecza to czysty stan wdrożonej semantyki oraz technicznych schematów w razie potrzeby wycofania eksperymentów bazujących na słowach kluczowych i linkach zewnętrznych.
- **Semantyka HTML (EEAT)**: Zgodnie z nowymi wytycznymi zaktualizowano architekturę podstron `zabiegi.html` oraz `o-gabinecie.html`. Wdrożono semantyczne tagowanie (`<article>` dla samodzielnych opisów zabiegów/tekstu, `<aside>` dla ramki bocznej i przeciwwskazań). Jasna hierarchia kodu znacząco odciąża parsery wyszukiwarek.
- **Robots.txt**: Zaktualizowano plik `robots_anette.txt`, dodając jawne reguły `Allow: /` dla botów `GPTBot`, `CCBot` oraz `ChatGPT-User`, aby umożliwić i zasygnalizować otwartość na skanowanie struktury przez wiodące modele LLM.
- **Cennik (OfferCatalog)**: Wdrożono bogaty schemat znaczników strukturalnych `JSON-LD` w `pages/cennik/cennik.html`, zmapowano usługi i uwarunkowania cenowe formując je jako spójny obiekt (Zabiegi na Twarz, Estetyka Oka itd.), co zoptymalizuje podsumowania z cennika w wynikach AI.

### Nawigacja
- **Decyzja**: Menu jest ładowane dynamicznie z `_nav.html`.
- **Stan obecny**: Kolejność linków: **Aktualności, Zabiegi, Technologia, Opinie, O gabinecie, Kontakt**.
- **Mobile**: Wprowadzono menu typu "Hamburger". Linki są domyślnie ukryte i rozwijają się po kliknięciu przycisku.
- **Cennik**: Dodano nową podstronę `pages/cennik/cennik.html` oraz link w nawigacji (po "Zabiegi").
- **Layout Cennika**: Zmieniono układ na kolumnowy (Masonry) i przeniesiono najdłuższą sekcję na początek listy dla lepszego balansu. Nota prawna przeniesiona na górę.
- **Cleanup**: Usunięto zduplikowany i nieaktualny plik `cennik.html` z katalogu głównego.
- **Architektura Danych**: Doprecyzowano w dokumentacji, że `news.php` to interfejs do lokalnej bazy danych, a nie proxy do Google API.
- **Automatyzacja News** *(ARCHIWALNE — skrypty usunięte w audycie 03.2026)*: Wdrożono skrypt `api/import_google_news.php` pobierający posty z Google API. Eksperyment zakończony niepowodzeniem (blokada Google API). Skrypt usunięty z repozytorium.
- **Problem API**: Błąd `invalid_grant` (wygasły token). Narzędzie `setup_google_token.php` przechowywane w `google_restricted/` (ignorowanym przez Git). Publiczny loader `api/run_token_setup.php` usunięty z repozytorium w ramach audytu.
- **Diagnostyka API**: Dodano narzędzie `google_restricted/find_google_ids.php` do jednorazowego wyszukania `account_id` i `location_id`. Należy je wgrać na serwer ręcznie, użyć i natychmiast usunąć.
- **Wymagania API**: Do pełnej funkcjonalności (diagnostyka + import postów) wymagane jest włączenie w Google Cloud Console **trzech** interfejsów API:
  1.  **My Business Account Management API** (do znajdowania `account_id`)
  2.  **My Business Business Information API** (do znajdowania `location_id`)
  3.  **Google My Business API** (starsze API, wycofane przez Google). **Status:** Niedostępne — patrz sekcja "Roadblock".

### Roadblock: Google API
- **Problem**: Google całkowicie wycofało i uniemożliwiło włączenie starego `Google My Business API`, które jako jedyne pozwalało na pobieranie postów (`localPosts`). Nowe interfejsy (`Business Information API` etc.) nie posiadają jeszcze tej funkcjonalności.
- **Eksperyment**: Podjęto próbę użycia endpointu v4 (`https://mybusiness.googleapis.com/v4/.../localPosts`), który mimo deprecjacji może nadal działać przy użyciu odpowiednich prefiksów ID (`accounts/...`, `locations/...`).
- **Status**: Eksperyment niepowodzony (blokada po stronie Google). `news.php` przywrócono do korzystania wyłącznie z tabeli ręcznej `Anette_news`. Skrypty importujące (`sync_google.php`, `import_google_news.php`) usunięte z repozytorium w audycie 03.2026.
- **Rozwiązanie (CMS)**: Stworzono panel administracyjny (`api/admin.php`) z logowaniem przez **Google OAuth**.
  - Wykorzystuje plik `config_oauth.php` z bezpiecznego katalogu.
  - Dostęp mają tylko adresy e-mail zdefiniowane w zewnętrznym pliku `config_emails.php` (w bezpiecznym katalogu).
  - Umożliwia dodawanie, edycję i usuwanie wpisów z tabeli `Anette_news`.
  - **Dostęp**: Dodano adres `htowerpl@gmail.com` do listy administratorów.
  - **Konfiguracja**: Wymaga dodania adresu `https://anette.beauty/api/admin.php` (oraz wersji testowych, np. `https://test.anette.beauty/api/admin.php`) do sekcji **Authorized redirect URIs** w Google Cloud Console.

### Aktualności (News)
- **Problem**: Tekst z bazy nie miał akapitów, a data była mało widoczna.
- **Rozwiązanie**: Zmodyfikowano `app.js` (funkcja `loadNewsFromApi`).
  - Data jest teraz **pogrubiona** i znajduje się **nad zdjęciem**.
  - Znaki nowej linii `\n` z bazy są zamieniane na `<br>` w HTML.
  - Zmieniono logikę wyświetlania zdjęć na elastyczną: zdjęcie **wypełnia 100% szerokości** (skaluje się), ale jego wysokość jest ograniczona do `max-height: 23rem` (6x ramki daty). Jeśli zdjęcie jest niższe – ramka się kurczy. Jeśli wyższe – pojawiają się marginesy boczne `var(--surface-alt)`.

### Optymalizacja Mobile
- **Decyzja**: Używamy `window.matchMedia("(max-width: 840px)")` w JS do wykrywania urządzeń mobilnych.
- **Wideo na start**: Wideo z YouTube ładuje się na wszystkich urządzeniach (do czasu wdrożenia dedykowanego pliku wideo dla mobile).
- **Redirekcja**: Po intro (lub kliknięciu "Pomiń") następuje przekierowanie do sekcji **Aktualności**.

### Strona Główna (Splash Screen)
- **Decyzja**: Strona główna (`index.html`) pełni funkcję ekranu powitalnego (Intro).
- **Zmiana**: Usunięto z niej nawigację (`nav`) oraz stopkę (`footer`). Zawiera tylko logo, wideo i przycisk wejścia.
- **Wideo**: Zastąpiono osadzony odtwarzacz YouTube lokalnym plikiem `pages/Anette.mp4` (Globalnie).
  - **Optymalizacja i Bezpieczeństwo (Wdrożono Marzec 2026)**: Usunięto zawodny atrybut `onended` z HTML.
  - Oczyszczono plik `index.html` z surowych kodów script. Cała izolowana logika odtwarzania została powierzona `initIntroVideo()` na końcu dokumentu `app.js`.
  - **Dynamika Zakończenia (Timeupdate)**: Czas nasłuchu `timeupdate` ucina wideo na `Czas Całkowity - 0.5s`, wymuszając naturalne płynne zaniknięcie znane z wycofanego wideo z YouTube. Usunięto surowe wyciemnienia. Ogranicza to wbudowane błędy flashu końca HTML5.
  - Zaimplementowano asynchroniczny strumień JS, po usunięciu natarczywego "Odtwórz Demo", aplikacja w pełni ignoruje restrykcyjne blokady przeglądarki polem `catch(error)`. Tło wypełnia niezmiennie okładka grafiki statycznej.
  - Wdrożono awaryjny "Bezpiecznik Timeout" 10.5-sekundowy (10500ms) dla 9-sekundowego filmu, który niezależnie od blokad lub usterek autoodtwarzania z błędu wyżej, wypchnie odwiedzającego na stronę aktualności.
  - Pod tag wideo powiązano pre-loadowany plik grafiki startowej `pages/Anette_in.webp` w atrybucie `poster`.
  - Przestrzeń pod wideo (`.video-frame`) została zabezpieczona stałym atrybutem `aspect-ratio: 16/9`, aby zapobiec szarpaniu tła podczas wczytywania zasobów. Tło obramowania wyświetla niezmienną kopię bazową `pages/Anette_in.webp` na poczet późniejszego `Fade-outu`.
- **Cache Busting**: Wprowadzono politykę łamania pamięci podręcznej przy użyciu wersji dodawanej do adresów w formacie daty (np. `?v=20260309`).  
  - **ZASADA GLOBALNA**: Zmieniając jakiekolwiek pliki statyczne (Style CSS, Pliki JavaScript, Pliki Wideo lub Główne banery), AI (oraz człowiek) zobowiązani są do inkrementacji numeru parametrycznego `?v=` nie tylko w pliku `index.html`, **ale również we wszystkich głównych plikach znajdujących się w obrębie całego projektu** (wszystkie podstrony HTML w katalogu `pages/`). Gwarantuje to ogołocenie klientek z nieświeżych wersji plików zalegających na ich urządzeniach na każdej możliwej ścieżce wejścia.

### Zabiegi (Treatments)
- **Problem Mobile**: Panel boczny (Wskazania) wyświetlał się przed treścią (kwestia `order` w CSS).
- **Problem Scroll**: Na mobile przewijanie było nieprecyzyjne (zbyt duży offset) i nie kierowało do treści zabiegu.
- **Rozwiązanie**:
  - CSS: Upewniono się, że `.treatment-sidebar` nie ma `order: -1` na mobile (naturalna kolejność DOM).
  - JS: `scrollToSummary` w `app.js`:
    - Mobile: Offset zmniejszony do 10px (brak sticky nav).
    - Mobile: Dla głównego bloku zabiegu przewija do pierwszego punktu opisu (`.treatment-detail`).
    - Desktop: Zachowano przewijanie do zdjęcia (`data-scroll-anchor`) i offset nawigacji.
  - CSS: Dodano `padding-top` do `.treatment-body`, aby oddzielić treść od nagłówka po rozwinięciu.

### Interakcje (UX)
- **CTA Buttons**: Przyciski "Umów konsultację" (i analogiczne w panelach bocznych) kierują teraz bezpośrednio do aplikacji telefonu (`tel:`), zamiast na stronę kontaktu.
- **Social Media**: Na stronie kontaktu pozostawiono tylko Google. Pozostałe linki (YouTube, Panorama Firm, GoWork, Gliwice Dla Was) przeniesiono do stopki jako eleganckie ikony (pobierane dynamicznie).
- **Styl UI**: Ikony w stopce są monochromatyczne i nabierają kolorów po najechaniu (hover) na desktopie. Na mobile są od razu kolorowe. Przeniesiono je na sam dół, pod tekst copyright.
- **Aktualności**: Przycisk "Więcej" automatycznie wykrywa numer telefonu, zmienia etykietę na "Zadzwoń" i dodaje ikonę słuchawki (SVG).

## Planowane Zadania (Backlog)
- **Wdrożenie Raportu Bezpieczeństwa i Funkcjonalności**:
  - **Zadanie**: Wykorzystanie wygenerowanego raportu analizy (`.agent/analysis_results.md`) w celu systematycznego łatania luk bezpieczeństwa (XSS, brak CSRF, brak autoryzacji w dziale pomiarów) oraz refaktoryzacji monolitu `api/pomiary/index.php`. AI przed rozpoczęciem prac poprawkowych w tych rejonach musi bezwzględnie zaczytać ten plik.
  - **Status**: Do wdrożenia sukcesywnie we wskazanej przez użytkownika kolejności.

- **Cleanup JS**: Wykonano. Usunięto stary kod obsługi YouTube API z pliku `app.js`.
- **Optymalizacja Mediów (Wydajność)**:
  - **Zdjęcia**: Konwersja plików PNG (`recepcja_001`, `makijaz-zabieg`, `lipoliza-zabieg`) na format **WebP** (znaczna redukcja wagi).
  - **Fonty**: Konwersja fontów z formatu `.otf` na `.woff2` w `styles.css`.
  - **Status**: Czekamy na przekonwertowane pliki od użytkownika.

- **Refaktoryzacja Struktury**:
  - **Zadanie**: Przeniesienie skryptu `news.php` do dedykowanego katalogu `api/` i aktualizacja ścieżki w `app.js`.
  - **Status**: Oczekuje na większą aktualizację na serwerze w celu zachowania kompatybilności.

## Lista Kontrolna Przed Publikacją (Pre-launch Checklist)
1.  [x] **Baza Danych**: Zaktualizować ścieżkę `$configFile` w `news.php` do poprawnej lokalizacji na serwerze produkcyjnym.
2.  [x] **Config DB**: Wgrać plik `config_db.php` poza katalog publiczny (dla bezpieczeństwa) i uzupełnić go danymi nowej bazy.
3.  [ ] **Media**: Wykonać konwersję zdjęć do WebP i fontów do WOFF2 (zadanie z Backlogu).
4.  [x] **SEO**: Wygenerować plik `sitemap.xml` (np. online generator) po uruchomieniu strony i wgrać go do katalogu głównego.
5.  [x] **SSL**: Wymusić przekierowanie na HTTPS w panelu hostingu. *(Zweryfikowano 03.2026 — działa)*
6.  [ ] **Pomiary Elektryczne**: Stworzenie w `database.php` trwałych tabel typu SŁOWNIK (`inzynierowie_slownik` oraz `mierniki_slownik`) pod auto-uzupełnianie etykiet `<datalist>` w formularzach GUI dla powtarzalnego personelu i maszyn mierzących. (Plan oczekujący - patrz: `implementation_plan.md`).

---

## PROJEKT POBOCZNY: Pomiary Elektryczne (Sub-projekt)
**Lokalizacja**: `api/pomiary/`
**Status**: Faza początkowa.
**Cel**: Aplikacja do tworzenia i drukowania protokołów z pomiarów instalacji elektrycznej.
**Zależności**: Projekt całkowicie niezależny od logiki `anette.beauty`.

### Log Zmian (Pomiary)
- **Formatowanie Wydruku (PDF)**: Zwiększono globalną czytelność protokołu zmieniając główną czcionkę dokumentu (tag `body`) z 11pt na 12pt dla zgodności z czytelnym formatem A4. Zmieniono tekst przed uprawnieniami na zwykłe wylistowanie "Uprawnienia:" dla ujednolicenia w obu wariantach (E i D, oraz E=D). Dodano nową, scentralizowaną Stronę Tytułową raportu oraz wprowadziło rygorystyczne miejsca na odręczny "podpis" inżyniera z zachowaniem 2 cm na postawienie autografu na wykropkowanej linii (skopiowane także pod tabele części pomiarowej). Marginesy wydruku zostały poprawione z użyciem sztywnego atrybutu omijającego usterki pecetowych konfiguratorów druku.
- **Podpisy Multisekcyjne (PDF)**: Zmieniono mechanizm renderowania podpisów "Wykonał" i "Sprawdził" w pliku `index.php`. Zamiast generować te okienka wyłącznie jednokrotnie na dole ostatniej strony, cały blok z nazwiskami E/D wraz z certyfikatami elegancko doczepia się bezpośrednio pod każdą ukończoną wcześniej tabelą (Oględziny, SWZ, RCD, RISO). Dostosowano również odstępy CSS wydruku, aby podpisy przylegały do klamry tabeli.
- **Nawigacja i Usprawnienia Bazy (UX)**: Dopracowano przepływ logiki przycisków w `index.php`.
  - Dla całkiem nowych protokołów, nie posiadających jeszcze ID w bazie (nie zatwierdzonych) przycisk "Podgląd i Druk" ukazuje się jako celowo nieaktywny, szary przycisk (zamiast znikania). 
  - W podglądzie Read-Only/PDF wprowadzono wyraźny przycisk pozwalający na powrót do formularza z trybem edycji bez utraty wybranego ekranu (`?edit=ID`). 
  - Utworzono backend obsługujący ścieżkę usuwania (`?delete=ID`), który kaskadowo niszczy rekord przypisany z tablicy `protokoly` oraz powiązane z nim JSONy z tablicy `pomiary_linie`. W samym widoku archiwum pojawił się czerwony przycisk "Usuń" z wywoływanym monitem JavaScript upewniającym użytkownika.
- **Podpisy E i D (UX/UI)**: Dodano checkbox "Ta sama osoba" w definicjach inżynierów. Zaznaczenie blokuje pole 'Sprawdzającego (D)' i kopiuje Imię 'Wykonawcy (E)'. W głównym nagłówku na wydruku HTML (PDF) i na dole przy polu do podpisu fizycznego, jeżeli imiona obu inżynierów są identyczne, poszczególne div'y inteligentnie scalają się w jeden elegancki blok "Wykonał i Sprawdził" z wypisanymi oba identyfikatorami uprawnień E i D pod spodem jednego nazwiska.
- **Logika Interfejsu (UX/UI)**: Naprawiono błąd gubienia identyfikatora ID rekordu protokołu podczas operacji zapisu (`$_GET['edit']` czyściło zmienną do poziomu null). Przyciski na nowo renderują się w domach bez problemu z odczytem kontekstu.
- **Kasowanie Omyłek**: Rozszerzono strukturę tabel `MIERNIKI`, `RISO`, `SWZ` i `RCD` w głównym formularzu dodając czerwoną kolumnę z przyciskiem do czyszczenia wiersza (`X`).
- **Aparatura Pomiarowa (JSON)**: Całkowicie usunięto pojedyncze, płaskie kolumny rejestrujące w bazie miernik i jego certyfikat. Od teraz w `index.php` sprzęt dodawany jest jako wieloelementowa lista dynamiczna tabeli HTML, która ląduje w bazie jako rekord JSON kategorii `MIERNIKI` obok innych JSONów w tabeli `pomiary_linie`. 
  - Dodano od zera dwa pola z wizualnym wyborem dat z kalendarza GUI dla wzorcowań. 
  - Dołączono automatyczne uzupełnianie się ważności na równe 365 dni od daty wydania (z możliwością modyfikacji z ręki). 
  - W podglądzie PDF stare rekordy bez wpisów JSON są ładowane po weryfikacji starej zmiennej (backward compatibility).
- **Rozdzielenie procesu (UX)**: Akcja "Zapisz do bazy" ładuje ponownie otwarty formularz zachowując dane i dopina komunikat sukcesu, a "Podgląd i Opcje Druku" wyświetlane są po zapisie jako osobny przycisk przenoszący do dokumentu HTML generowanego na zasadzie Read-Only/PDF-Ready.
- **Rozwój Funkcji Pomiarowych**: Wdrożono poprawki normatywne zgodnie z udostępnionym `knowledge_base.md`. 
  - Do `database.php` w tabeli `protokoly` dodano kolumny `miernik_nazwa` oraz `miernik_wzorcowanie`. Baza używa `CREATE TABLE IF NOT EXISTS` zapobiegając usterkom odczytów dla starych środowisk bez najświeższych modyfikacji oraz skryptów `ALTER TABLE`.
  - W `index.php` dodano w HTML i JS ich obsługę. 
  - Moduł samoczynnego wyłączenia (SWZ) wspiera teraz wybór Układu Sieci (TT/TN) oraz wprowadzanie współczynnika temperatury z palca.
  - Testy RCD uaktualniono o mnożniki `5x I delta` ze zmniejszonymi czasami do 40ms. Wszystkie powyższe zmiany są również brane pod uwagę w ostatecznym renderingu PDO `echo` dla wydruku podsumowania dokumentu HTML (PDF).
- **Inicjalizacja**: Utworzono katalog `api/pomiary` oraz plik `index.php` (punkt wejścia).
- **Naprawa Błędów**: Poprawiono uszkodzoną składnię JavaScript w formularzu `index.php` (m.in. funkcje `addRisoRow`, `addSwzRow`, `addRcdRow`), umożliwiając poprawne generowanie i wysyłanie pakietów JSON do bazy danych.

### Audyt Bezpieczeństwa i SEO (Marzec 2026)
- **Repozytorium**: Usunięto artefakty gita (`et --hard...`) oraz pliki testowe (`test_braces.py`).
- **Endpointy News**: Usunięto zduplikowany plik `api/get_news.php`. Główne zapytania trafiają do `news.php`.
- **Likwidacja martwego kodu Google API**: Usunięto z repozytorium endpointy `api/sync_google.php`, `api/import_google_news.php`, `api/run_token_setup.php` oraz puste placeholdery (`config_emails.php`, `oauth_callback.php`). Pliki dodane do `.gitignore`.
- **Wycieki danych diagnostycznych**: Wyciszono `$e->getMessage()` we wszystkich plikach PHP (`admin.php`, `pomiary/index.php`, `pomiary/database.php`). Szczegóły błędów trafiają do `error_log()` zamiast na ekran.
- **Panel Admin (`admin.php`)**: Usunięto `CURLOPT_SSL_VERIFYPEER=false`, zamieniono `file_get_contents` na `cURL` przy pobieraniu danych użytkownika Google.
- **Baza SQLite (Pomiary)**: Zmieniono `chmod 0666` na `0644`. Dodano `.htaccess` z `Deny from all` w katalogu `db/` blokujący publiczne pobieranie pliku bazy.
- **SEO**: Zastąpiono docelowy plik reguł botów plikiem `robots_anette.txt` (z odblokowanym dostępem do `/assets/`). W głównym `robots.txt` wprowadzono całkowitą blokadę środowiska testowego (`Disallow: /`).
