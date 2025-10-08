# Anette

Repozytorium marki **Anette** — połączenie luksusu i minimalistycznej elegancji (paleta z akcentami pomarańczu).

## Start
- Wymagania: Node 20+ / pnpm lub npm
- Devcontainer: planowany (`.devcontainer/devcontainer.json`)

## Struktura
- `src/` – kod źródłowy
- `public/` – statyczne zasoby (logo, favicon)
- `docs/` – materiały projektowe
- `index.html` – statyczna strona demonstracyjna z multimediami

## Uruchomienie (przykład)
```bash
npm install
npm run dev
```

## Podgląd strony demonstracyjnej
1. Otwórz `index.html` bezpośrednio w przeglądarce lub uruchom lokalny serwer HTTP, np.:
   ```bash
   python -m http.server
   ```
2. Wejdź na `http://localhost:8000/index.html`, aby zobaczyć:
   - hero z opisem marki i przyciskiem CTA,
   - galerię z obrazem w wysokiej rozdzielczości,
   - kartę audio z natywnym odtwarzaczem,
   - wideo z plakatem (poster) uruchamiane w odtwarzaczu HTML5.
