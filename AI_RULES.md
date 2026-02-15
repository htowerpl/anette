# Dokumentacja Projektowa i Zasady Współpracy (Pamięć AI)

Ten plik służy jako pamięć długotrwała dla asystenta AI. Należy go czytać na początku każdej sesji, aby zrozumieć kontekst, styl i historię zmian.

## Słownik komend
- **"No i Git!"** -> Oznacza akceptację zmian i prośbę o wygenerowanie komend git:
  1. `git add .`
  2. `git commit -m "Opis zmian"`
  3. `git push`

## Kluczowe Ustalenia Techniczne
1.  **Struktura One-Page / Multi-Page**: Projekt jest hybrydą. Główne sekcje są na osobnych podstronach (`pages/`), ale nawigacja i stopka są wstrzykiwane dynamicznie (`loadComponent` w `app.js`) z plików `_nav.html` i `_footer.html`.
2.  **Backend**: Prosty PHP (`news.php`) zwracający JSON. Nie używamy frameworków PHP, tylko czysty PDO.
3.  **Frontend**: Czysty JS (Vanilla). Brak frameworków typu React/Vue. Style w `styles.css` oparte na zmiennych CSS.

## Historia Decyzji i Zmian (Log)

### Nawigacja
- **Decyzja**: Menu jest ładowane dynamicznie z `_nav.html`.
- **Stan obecny**: Kolejność linków: **Aktualności, Zabiegi, Technologia, Opinie, O gabinecie, Kontakt**.

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
- **Styl UI**: Ikony w stopce są monochromatyczne i nabierają kolorów po najechaniu (hover).

## Planowane Zadania (Backlog)
- **Lepsze Intro Mobile**: Zastąpienie czarnego ekranu na mobile krótkim, lokalnym wideo (`intro-mobile.mp4`).
  - Cel: Zachowanie "efektu wow" bez ładowania ciężkiego YouTube.
  - Działanie: Autoplay (muted), po zakończeniu auto-redirect do Aktualności.
  - Status: Czekamy na plik wideo od użytkownika.
