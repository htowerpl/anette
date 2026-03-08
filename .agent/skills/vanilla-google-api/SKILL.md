---
name: Vanilla Google API Integration
description: Wytyczne dotyczące integracji Google Business Profile API przy użyciu czystego PHP i JavaScript.
---

# Cel
Bezpieczna integracja Google Business Profile API, pobranie aktualności firmy i wyświetlenie ich na stronie internetowej.

# Architektura projektu (Vanilla Stack)
*   **Serwer/Backend:** Czysty (Vanilla) PHP. Służy WYŁĄCZNIE do automatyzacji, bezpiecznego łączenia się z Google API (OAuth/Klucze) oraz zapisu/odczytu z relacyjnej bazy danych.
*   **Frontend:** HTML5, CSS3, małe skrypty w Vanilla JavaScript (tylko do interakcji na stronie).
*   **Środowisko:** Debian.

# Zasady pisania kodu i współpracy (KRYTYCZNE)
1.  **Zero frameworków:** Nigdy nie proponuj użycia frameworków (np. Laravel, React, Vue, jQuery). Trzymaj się czystego kodu (Vanilla).
2.  **Bezpieczeństwo po stronie serwera:** Klucze API i tokeny OAuth mogą być obsługiwane tylko przez PHP. Nigdy nie wystawiaj logiki API do plików JavaScript po stronie klienta (Frontend).
3.  **Separacja logiki:** Zawsze rozdzielaj logikę! PHP zajmuje się pobieraniem postów z Google i zapisem do bazy, a HTML/CSS/JS zajmują się tylko prezentacją pobranych już danych.
4.  **Konfiguracja GCP na pierwszym miejscu:** Zanim wygenerujesz jakikolwiek kod PHP do Google API, krok po kroku zdiagnozuj problemy z uprawnieniami w Google Cloud Console (ekran zgody OAuth, scope'y, odblokowanie Restricted API). Zawsze zapytaj, na jakim etapie w konsoli jest użytkownik, zanim przejdziesz do pisania kodu.
