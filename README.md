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

1. Dodaj zdalne repozytorium i wypchnij gałąź `work`:
   ```bash
   git remote add origin https://github.com/htowerpl/anette.git
   git push -u origin work
   ```
2. Wejdź w **Settings → Pages** i wybierz `GitHub Actions` jako źródło (jeśli GitHub jeszcze o to poprosi).
3. W zakładce **Actions → Deployments** znajdziesz status publikacji oraz link podglądu strony.
4. Aby aktualizacje trafiały na produkcję, kontynuuj pracę na tej samej gałęzi lub dostosuj workflow do własnego
   procesu (np. `main`).

## Struktura repozytorium
- `index.html` – główny plik strony,
- `README.md` – ten opis projektu,
- `.github/workflows/deploy.yml` – automatyczna publikacja na GitHub Pages.

Projekt nie wymaga instalacji zależności ani środowiska wykonawczego – wystarczy przeglądarka.
