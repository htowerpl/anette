# Anette

Repozytorium marki **Anette** — połączenie luksusu i minimalistycznej elegancji (paleta z akcentami pomarańczu).

## Podgląd statycznej strony
- `public/index.html` — minimalistyczna implementacja landing page oparta wyłącznie na natywnym HTML5 i CSS (bez frameworków, build stepów i zależności Node).

## Struktura
- `public/index.html` – główny plik strony (można otworzyć bezpośrednio w przeglądarce)
- `README.md` – instrukcje i dokumentacja projektu

## Jak opublikować projekt na GitHubie
1. Zaloguj się na swoje konto GitHub i utwórz **nowe repozytorium** (np. `anette-site`). Nie dodawaj do niego żadnych plików startowych (README, .gitignore itp.).
2. Na swoim komputerze sklonuj to repozytorium lub skopiuj aktualny katalog projektu. Następnie w terminalu wykonaj:
   ```bash
   git init
   git add .
   git commit -m "Initial commit: statyczna strona Anette"
   git branch -M main
   git remote add origin https://github.com/<twoja_nazwa_uzytkownika>/<twoje_repo>.git
   git push -u origin main
   ```
   Uwaga: zamień `<twoja_nazwa_uzytkownika>` oraz `<twoje_repo>` na własne dane.
3. (Opcjonalnie) Aby udostępnić stronę publicznie, włącz GitHub Pages w ustawieniach repozytorium i wskaż gałąź `main` oraz katalog `/ (root)`. Po zapisaniu zmian statyczna strona będzie dostępna pod adresem `https://<twoja_nazwa_uzytkownika>.github.io/<twoje_repo>/`.

## Lokalny podgląd
Nie jest potrzebna żadna konfiguracja – wystarczy otworzyć plik `public/index.html` w przeglądarce lub uruchomić prosty serwer statyczny, np.:

```bash
python -m http.server --directory public 8000
```
Następnie wejdź na `http://localhost:8000`.
