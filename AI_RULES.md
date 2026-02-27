# Dokumentacja Projektowa i Zasady Współpracy (Pamięć AI)

Ten plik służy jako pamięć długotrwała dla asystenta AI. Należy go czytać na początku każdej sesji, aby zrozumieć kontekst, styl i historię zmian.

## Słownik komend
- **"No i Git!"** -> Oznacza akceptację zmian i prośbę o wygenerowanie komend git:
  1. `git add .`
  2. `git commit -m "Opis zmian"`
  3. `git push`

## Kluczowe Ustalenia Techniczne
1.  **Struktura One-Page / Multi-Page**: Projekt jest hybrydą. Główne sekcje są na osobnych podstronach (`pages/`), ale nawigacja i stopka są wstrzykiwane dynamicznie (`loadComponent` w `app.js`) z plików `_nav.html` i `_footer.html`.
2.  **Backend**: Prosty PHP (`news.php`) zwracający JSON. Dane pochodzą z **ręcznej tabeli `Anette_news`**. Automatyzacja została wyłączona z powodu blokady API Google.
3.  **Frontend**: Czysty JS (Vanilla). Brak frameworków typu React/Vue. Style w `styles.css` oparte na zmiennych CSS.
4.  **Konfiguracja Serwera**: Pliki z danymi wrażliwymi (DB, OAuth) znajdują się w bezpiecznym katalogu `/home/opxwpceo/domains/google/` i są dołączane przez absolutne ścieżki.

## Historia Decyzji i Zmian (Log)

### Nawigacja
- **Decyzja**: Menu jest ładowane dynamicznie z `_nav.html`.
- **Stan obecny**: Kolejność linków: **Aktualności, Zabiegi, Technologia, Opinie, O gabinecie, Kontakt**.
- **Mobile**: Wprowadzono menu typu "Hamburger". Linki są domyślnie ukryte i rozwijają się po kliknięciu przycisku.
- **Cennik**: Dodano nową podstronę `pages/cennik/cennik.html` oraz link w nawigacji (po "Zabiegi").
- **Layout Cennika**: Zmieniono układ na kolumnowy (Masonry) i przeniesiono najdłuższą sekcję na początek listy dla lepszego balansu. Nota prawna przeniesiona na górę.
- **Cleanup**: Usunięto zduplikowany i nieaktualny plik `cennik.html` z katalogu głównego.
- **Architektura Danych**: Doprecyzowano w dokumentacji, że `news.php` to interfejs do lokalnej bazy danych, a nie proxy do Google API.
- **Automatyzacja News**: Wdrożono skrypt `api/import_google_news.php` pobierający posty z Google API do tabeli `Anette_news_g`. Przełączono `news.php` na nową tabelę. Skonfigurowano użycie plików serwerowych (`config_oauth.php`).
- **Problem API**: Błąd `invalid_grant` (wygasły token). Stworzono narzędzie `setup_google_token.php` do generowania nowego `refresh_token`.
  - **Zalecenie bezpieczeństwa**: Skrypt `setup_google_token.php` jest przechowywany lokalnie w katalogu `google_restricted/` (ignorowanym przez Git). W razie potrzeby należy go wgrać do bezpiecznego katalogu na serwerze (`/home/opxwpceo/domains/google/`). Do jego uruchomienia służy publiczny "loader" `api/run_token_setup.php`, który należy usunąć z serwera po użyciu.
- **Diagnostyka API**: Dodano narzędzie `google_restricted/find_google_ids.php` do jednorazowego wyszukania `account_id` i `location_id`. Należy je wgrać na serwer ręcznie, użyć i natychmiast usunąć.
- **Wymagania API**: Do pełnej funkcjonalności (diagnostyka + import postów) wymagane jest włączenie w Google Cloud Console **trzech** interfejsów API:
  1.  **My Business Account Management API** (do znajdowania `account_id`)
  2.  **My Business Business Information API** (do znajdowania `location_id`)
  3.  **Google My Business API** (starsze API, wciąż wymagane do pobierania postów/aktualności). **Uwaga:** Może być ukryte w wyszukiwarce. Należy użyć bezpośredniego linku: `https://console.cloud.google.com/apis/library/mybusiness.googleapis.com`.
  3.  **Google My Business API** (starsze API, wciąż wymagane do pobierania postów/aktualności). **Uwaga:** Jest ukryte w wyszukiwarce. Należy użyć bezpośredniego linku: `https://console.cloud.google.com/apis/library/mybusiness.googleapis.com`.

### Roadblock: Google API
- **Problem**: Google całkowicie wycofało i uniemożliwiło włączenie starego `Google My Business API`, które jako jedyne pozwalało na pobieranie postów (`localPosts`). Nowe interfejsy (`Business Information API` etc.) nie posiadają jeszcze tej funkcjonalności.
- **Eksperyment**: Podjęto próbę użycia endpointu v4 (`https://mybusiness.googleapis.com/v4/.../localPosts`), który mimo deprecjacji może nadal działać przy użyciu odpowiednich prefiksów ID (`accounts/...`, `locations/...`).
- **Status**: Eksperyment niepowodzony (blokada po stronie Google). `news.php` przywrócono do korzystania wyłącznie z tabeli ręcznej `Anette_news`. Skrypt importujący pozostawiono jako narzędzie diagnostyczne.
- **Rozwiązanie (CMS)**: Stworzono panel administracyjny (`api/admin.php`) z prostym logowaniem hasłem. Umożliwia on dodawanie, edycję i usuwanie wpisów z tabeli `Anette_news`. Dodano podgląd miniaturek na liście wpisów.

### Aktualności (News)
- **Problem**: Tekst z bazy nie miał akapitów, a data była mało widoczna.
- **Rozwiązanie**: Zmodyfikowano `app.js` (funkcja `loadNewsFromApi`).
  - Data jest teraz **pogrubiona** i znajduje się **nad zdjęciem**.
  - Znaki nowej linii `\n` z bazy są zamieniane na `<br>` w HTML.

### Optymalizacja Mobile
- **Decyzja**: Używamy `window.matchMedia("(max-width: 840px)")` w JS do wykrywania urządzeń mobilnych.
- **Wideo na start**: Wideo z YouTube ładuje się na wszystkich urządzeniach (do czasu wdrożenia dedykowanego pliku wideo dla mobile).
- **Redirekcja**: Po intro (lub kliknięciu "Pomiń") następuje przekierowanie do sekcji **Aktualności**.

### Strona Główna (Splash Screen)
- **Decyzja**: Strona główna (`index.html`) pełni funkcję ekranu powitalnego (Intro).
- **Zmiana**: Usunięto z niej nawigację (`nav`) oraz stopkę (`footer`). Zawiera tylko logo, wideo i przycisk wejścia.
- **Wideo**: Start od 2. sekundy, wyciszone (autoplay muted), automatyczne przejście do serwisu na **9 sekund** przed końcem.

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
- **Lepsze Intro Mobile**: Zastąpienie czarnego ekranu na mobile krótkim, lokalnym wideo (`intro-mobile.mp4`).
  - Cel: Zachowanie "efektu wow" bez ładowania ciężkiego YouTube.
  - Działanie: Autoplay (muted), po zakończeniu auto-redirect do Aktualności.
  - Status: Czekamy na plik wideo od użytkownika.
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
5.  [ ] **SSL**: Wymusić przekierowanie na HTTPS w panelu hostingu.
