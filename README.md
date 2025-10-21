# Anette – strona wizerunkowa

Statyczna strona w czystym HTML5 prezentująca ofertę i klimat marki **Aneta Szachniewicz**. Projekt inspirowany układem
z serwisu [devpl8.getspace.us/anetaszachniewicz](https://devpl8.getspace.us/anetaszachniewicz/), rozbudowany o sekcje
multimedialne (wideo, audio, galeria) i bloki informacyjne.

## Co zawiera projekt?
- hero z wideo z YouTube i zdjęciem stylizacyjnym,
- sekcje „O mnie” i „Oferta” z kartami informacyjnymi,
- galerię realizacji, opinie klientek oraz banner CTA,
- kontakt z formularzem i danymi teleadresowymi,
- responsywny design, bez frameworków i bibliotek JS.

## Jak uruchomić podgląd lokalny?
1. Otwórz plik `index.html` w dowolnej współczesnej przeglądarce **lub** uruchom prosty serwer HTTP:
   ```bash
   python -m http.server
   ```
2. Wejdź na `http://localhost:8000/index.html`, aby zobaczyć pełną stronę wizerunkową wraz z multimediami.

## Publikacja na GitHub Pages
Repozytorium zawiera gotowy workflow GitHub Actions (`.github/workflows/deploy.yml`), który publikuje statyczną stronę
na GitHub Pages po każdym pushu do gałęzi `work` (możesz zmienić gałąź według potrzeb).

1. Dodaj zdalne repozytorium i wypchnij gałąź z plikami strony:
   ```bash
   git remote add origin https://github.com/htowerpl/anette.git
   git push -u origin work   # lub: git push -u origin main
   ```
2. Upewnij się, że na GitHubie patrzysz na tę samą gałąź (np. `work`). Jeśli chcesz, aby pliki były od razu na
   głównej gałęzi, scal `work` do `main` (PR lub `git merge work`).
3. Wejdź w **Settings → Pages** i wybierz `GitHub Actions` jako źródło (jeśli GitHub jeszcze o to poprosi).
4. W zakładce **Actions → Deployments** znajdziesz status publikacji oraz link podglądu strony.
5. Aby aktualizacje trafiały na produkcję, kontynuuj pracę na gałęzi `work` lub `main` (workflow wspiera obie).

### Gdy nadal nie widzisz zmian na GitHubie
- Sprawdź, czy polecenie `git push` nie zwróciło błędu („repository not found”, brak uprawnień itp.).
- Zweryfikuj, że lokalne pliki są zacommitowane (`git status` powinien pokazać „nothing to commit”).
- W przeglądarce repozytorium wybierz właściwą gałąź z rozwijanego menu obok etykiety `main`.
- Jeśli GitHub Pages pokazuje starą wersję, wymuś ponowną publikację: przejdź do **Actions → Deploy static site to
  GitHub Pages** i kliknij **Run workflow** na najnowszym commicie.

## Struktura repozytorium
- `index.html` – główny plik strony,
- `README.md` – ten opis projektu,
- `.github/workflows/deploy.yml` – automatyczna publikacja na GitHub Pages.

Projekt nie wymaga instalacji zależności ani środowiska wykonawczego – wystarczy przeglądarka.
