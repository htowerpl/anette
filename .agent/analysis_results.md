# Raport z Analizy Bezpieczeństwa i Funkcjonalności

Zgodnie z poleceniem przeanalizowałem Twój projekt pod kątem bezpieczeństwa oraz funkcjonalności, **nie wprowadzając żadnych zmian w kodzie**. 
Analizie poddałem zarówno strukturę katalogów, pliki konfiguracyjne (`AI_RULES.md`), kod frontendowy (`app.js`, HTML), jak i backendowy (`news.php`, `api/admin.php`, `api/pomiary/index.php`).

Oto szczegółowe wnioski i propozycje optymalizacji.

---

## 1. Bezpieczeństwo (Security)

### 🥇 Mocne strony (Co już działa dobrze):
*   **Ochrona przed SQL Injection:** W aplikacjach używasz biblioteki PDO oraz tzw. *prepared statements* (bindowanie parametrów) we wszystkich operacjach dodawania, aktualizacji i usuwania (widoczne m.in. w `api/admin.php` i `api/pomiary/index.php`).
*   **Logowanie OAuth:** Logowanie do panelu administratora za pośrednictwem Google OAuth na podstawie autoryzowanych adresów e-mail z pliku poza katalogiem publicznym (`config_emails.php`) to niezwykle bezpieczne, nowoczesne rozwiązanie.
*   **Ukrywanie konfiguracji:** Bardzo dobrą praktyką jest umieszczanie danych uwierzytelniających (np. baza danych) w katalogu wyżej `/home/opxwpceo/domains/google/...`, poza zasięgiem publicznym (`public_html`).

### ⚠️ Propozycje poprawek (Luki do załatania):

#### A. Ryzyko XSS (Cross-Site Scripting) na podstronie Aktualności
*   **Problem:** W pliku `app.js` (linia 147 i 173) treść aktualności formatowana jest z bazy i pobierana jako string do zmiennej (`replace(/\r\n|\r|\n/g, '<br>')`), a następnie wstrzykiwana bezpośrednio do DOM jako surowy HTML (`${contentHtml}`). Jeśli w bazie w kolumnie `content` znalazłby się złośliwy skrypt (np. `<script>alert('XSS')</script>`), przeglądarka go wykona.
*   **Propozycja:** Mimo, że panel CMS blokuje dostęp dla osób trzecich, dobrą i wymaganą praktyką jest sanitacja danych na froncie. Zamiast czystego wpisywania HTML, zalecam stosowanie ucieczki tagów przed replace, zapinanie tego przez `textContent` w czystym JS, lub użycie mini-biblioteki np. DOMPurify. Ewentualnie w backendzie (`news.php`) użyć `htmlspecialchars()`.

#### B. Brak ochrony CSRF (Cross-Site Request Forgery)
*   **Problem:** Formularze POST w `api/admin.php` i `api/pomiary/index.php` weryfikują jedynie, czy padło żądanie `POST`, ale nie posiadają tokenów weryfikacyjnych CSRF. Atakujący mógłby skłonić załogowanego administratora do wejścia w spreparowany link/stronę, która w tle wysłałaby formularz i np. usunęła lub dodała artykuł/protokół.
*   **Sytuacja Krytyczna:** W systemie pomiarów wprowadzono metodę usuwania realizowaną przez zwykłe zapytanie GET: `?delete=ID`. Kliknięcie "na ślepo" przez administratora w wygenerowany link z kasowaniem usunie protokół.
*   **Propozycja:** 
    1. Do każdej akcji usuwania zawsze używać metody POST (lub okna z tokenem).
    2. We wszystkich formularzach generować po stronie sesji i wstawiać unikalny `<input type="hidden" name="csrf_token">`, weryfikując go po stronie PHP przed każdą transakcją bazodanową.

#### C. Całkowity brak autoryzacji w module Pomiary (`api/pomiary/index.php`)
*   **Problem:** Skrypt `admin.php` posiada piękną autoryzację Google, natomiast `api/pomiary/index.php` (aplikacja Monolit - Pomiary 2.0) wydaje się nie implementować metody `session_start()` połączonej z weryfikacją logowania. Jeśli plik jest publicznie dostępny (np. pod adresem `twojadomena.pl/api/pomiary/`), to absolutnie każda osoba w internecie może przeglądać, modyfikować, a nawet **na trwałe usunąć** (`?delete=ID`) każdy protokół pomiarowy.
*   **Propozycja:** Należy "owinąć" dostęp do aplikacji `pomiary/index.php` identycznym plikiem weryfikującym OAuth jak zrobiono to dla `admin.php`, lub zaimplementować przynajmniej logowanie poprzez proste hasło statyczne / Basic Auth.

---

## 2. Funkcjonalność i Architektura (Functionality)

### 🥇 Mocne strony:
*   **Nowoczesny UX na Vanilla JS:** Funkcja `scrollToSummary`, użycie Web API `window.matchMedia`, responsywne wsparcie video fallbacks i rozbijanie Cache (Cache-busting).
*   **Podręczny zapis stanów w locie:** Genialny pomysł z `localStorage.setItem('pomiary2_stan_globalny')` ubezpieczający dane formularza przed odświeżeniem strony.

### 💡 Propozycje poprawek (UX i utrzymanie):

#### A. Rosnący Monolit "Pomiary 2.0"
*   **Obserwacja:** Plik `api/pomiary/index.php` ma już ponad 1100 linijek kodu. Łączy w sobie routing bazowy, HTML, CSS i JS na froncie. Z czasem edycja takiego pliku i dołączanie kolejnych norm PDF (np. Pomiary Natężenia Oświetlenia) stanie się drogą przez mękę.
*   **Propozycja:** Refaktoryzacja na mniejsze, tematyczne pliki zachowując czysty PHP (bez narzutu frameworka):
    1. `index.php` - Główny hub formularza UI.
    2. `backend_save.php` - Sam handler do bazy danych.
    3. `print_pdf.php` - Genarator widoku A4 i PDF.
    4. Główne bloki CSS / JS powinny być wyciągnięte do plików zewnętrznych. To ustabilizuje proces rozwojowy.

#### B. Nieszczelne sprawdzanie błędów z Fetch API (app.js)
*   **Obserwacja:** W pliku `app.js` pobieranie aktualności korzysta z: 
    ```js
    return response.json().then(err => { throw new Error(...) })
    ```
    Jeśli z jakiegoś powodu serwer backendowy wyrzuci zepsuty nagłówek, lub błąd HTTP `500` wyrzuci czysty string HTML o błędzie (czyli treść, która *nie jest* poprawnym JSON-em), ten blok `catch` nie zdoła sprawnie usunąć domyślnego tekstu "Ładowanie aktualności...".
*   **Propozycja:** Zabezpieczyć promisy poprzez przechwytywanie formatu niezgodnego z JSON oraz wprowadzić twardy `catch` czyszczący loader w przypadku timeout-u.

#### C. System Dynamicznych Podpisów w "Pogłówku" PDF
*   **Obserwacja:** Generujesz formularze do poboru dat wzorcowania, co jest świetne. Sprytne spychacze puste bloki `<div style='width: 40%;'></div>` w szablonie podpisów na fakturze są skuteczne, ale na niektórych starszych czy nowszych renderach PDF mogą się zawinąć.
*   **Propozycja:** Przejście w blokach druku na `display: flex; justify-content: flex-end;` w przypadkach edycji jednej i tej samej osoby (kiedy E=D) daje gwarancję stabilności blokowej bez łatania "pustymi divami".

---

### Decyzja
Przeanalizowałem stan aplikacji, wszystkie powyższe punkty traktuj jako moje spostrzeżenia, które pozwolą ci na wdrożenie standardów Premium. Daj mi znać, od którego punktu zacząć pracę, a wygeneruję odpowiednie plany i udoskonalę pliki bez utraty wypracowanej bazy!
