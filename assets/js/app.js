(function () {
  function updateYearStamp() {
    var stamp = document.getElementById("year");
    if (stamp) {
      stamp.textContent = new Date().getFullYear();
    }
  }

  function scrollToSummary(detailsEl) {
    var summary = detailsEl.querySelector(":scope > summary");
    var anchorSelector = detailsEl.getAttribute("data-scroll-anchor");
    
    var isMobile = window.matchMedia("(max-width: 840px)").matches;
    var targetElement = summary;

    if (!isMobile && anchorSelector) {
      targetElement = document.querySelector(anchorSelector) || summary;
    } else if (isMobile) {
      // Na mobile dla głównego bloku zabiegu przewijamy do pierwszego punktu opisu
      if (detailsEl.classList.contains('treatment-block')) {
        var firstDetail = detailsEl.querySelector('.treatment-detail');
        if (firstDetail) {
          targetElement = firstDetail;
        }
      }
    }

    if (!targetElement) {
      return;
    }
    requestAnimationFrame(function () {
      var nav = document.querySelector("nav");
      // Na mobile nawigacja nie jest sticky, więc nie odejmujemy jej wysokości
      var offset = (nav && !isMobile) ? nav.offsetHeight + 16 : 10;
      var target = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
      window.scrollTo({ top: target, behavior: "smooth" });
    });
  }

  function setupAccordion(attribute) {
    var groups = document.querySelectorAll("[" + attribute + "]");
    if (!groups.length) {
      return;
    }

    groups.forEach(function (group) {
      group.addEventListener("toggle", function () {
        if (!group.open) {
          return;
        }

        var groupName = group.getAttribute(attribute);
        document
          .querySelectorAll("[" + attribute + "='" + groupName + "']")
          .forEach(function (panel) {
            if (panel !== group) {
              panel.open = false;
            }
          });

        scrollToSummary(group);
      });
    });
  }

  function initReviewsFallback() {
    var widgetContainer = document.querySelector('.review-widget div[class*="elfsight-app"]');
    // Pobieramy elementy statyczne, które chcemy ukryć po załadowaniu widżetu
    var fallbacks = document.querySelectorAll('.reviews-list, .google-link-container');

    if (!widgetContainer || fallbacks.length === 0) {
      return;
    }

    var hideFallbacks = function() {
      fallbacks.forEach(function(el) { el.style.display = 'none'; });
    };

    // Jeśli widżet załadował się błyskawicznie (np. z cache)
    if (widgetContainer.children.length > 0) {
      hideFallbacks();
      return;
    }

    var observer = new MutationObserver(function () {
      if (widgetContainer.children.length > 0) {
        hideFallbacks();
        observer.disconnect();
      }
    });

    observer.observe(widgetContainer, { childList: true });
  }

  function loadNewsFromApi() {
    const container = document.getElementById('news-container');
    if (!container) return;

    // Ustal ścieżkę do API względem obecnej lokalizacji
    // getBasePath() zwraca np. "../../" dla podstron, więc API będzie szukane w "../../news.php"
    const apiUrl = getBasePath() + "news.php";

    fetch(apiUrl)
      .then(response => {
        if (!response.ok) {
          // Próbujemy odczytać błąd z JSONa (np. z news.php)
          return response.json().then(err => {
            throw new Error(err.error || `Błąd HTTP ${response.status}`);
          }).catch(() => {
            // Jeśli nie udało się odczytać JSONa (np. błąd krytyczny PHP lub 404 html)
            throw new Error(`Błąd HTTP ${response.status}`);
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.error) {
          throw new Error(data.error);
        }

        // Zabezpieczenie: upewnij się, że otrzymaliśmy tablicę
        let items = Array.isArray(data) ? data : [];
        
        // Jeśli to obiekt (ale nie null), spróbuj wyciągnąć wartości (fallback dla nietypowych odpowiedzi JSON)
        if (!Array.isArray(data) && typeof data === 'object' && data !== null) {
          items = Object.values(data);
        }

        if (items.length === 0) {
          const loadingText = container.querySelector('.loading-state p');
          if (loadingText) loadingText.textContent = "Brak aktualności do wyświetlenia.";
          return;
        }

        let html = '';
        items.forEach(item => {
          // Zabezpieczenie przed brakiem obrazka
          const imageHtml = item.image 
            ? `<div class="news-card__image"><img src="${item.image}" alt="Zdjęcie aktualności" loading="lazy"></div>` 
            : '';

          // Formatowanie daty (zakładamy format YYYY-MM-DD)
          const dateObj = new Date(item.date);
          const dateStr = dateObj.toLocaleDateString('pl-PL', { day: 'numeric', month: 'short', year: 'numeric' });

          // Zamiana znaków nowej linii (\n) na znaczniki HTML <br>
          const contentHtml = item.content ? item.content.replace(/\r\n|\r|\n/g, '<br>') : '';

          let linkHtml = '';
          if (item.link) {
            let href = item.link;
            let target = 'target="_blank"';
            let label = 'Więcej';
            // Jeśli link wygląda jak numer telefonu (cyfry, plus, spacje, myślniki) i nie ma liter
            if ((/^[+\d\s-]+$/.test(item.link) && /\d/.test(item.link)) || item.link.startsWith('tel:')) {
              if (!item.link.startsWith('tel:')) {
                href = 'tel:' + item.link.replace(/\s/g, '');
              }
              target = '';
              label = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.05 12.05 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.05 12.05 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> Zadzwoń`;
            }
            linkHtml = `<a class="button news-card__cta" href="${href}" ${target}>${label}</a>`;
          }

          html += `
            <article class="news-card">
              <div style="padding: 1rem 1rem 0.5rem; font-weight: bold;">
                <time datetime="${item.date}">${dateStr}</time>
              </div>
              ${imageHtml}
              <div class="news-card__body">
                ${item.title ? `<p class="news-card__tagline">${item.title}</p>` : ''}
                <p>${contentHtml}</p>
                ${linkHtml}
              </div>
            </article>
          `;
        });
        
        container.innerHTML = html;
      })
      .catch(error => {
        console.warn("Nie udało się pobrać aktualności:", error);
        const loadingText = container.querySelector('.loading-state p');
        if (loadingText) {
          loadingText.textContent = `Nie udało się załadować aktualności: ${error.message}`;
        }
      });
  }

  // --- Nowy kod do wstrzykiwania komponentów ---

  const navEl = document.getElementById("main-nav");
  const footerEl = document.getElementById("main-footer");

  // Funkcja pomocnicza do ustalania ścieżki bazowej
  const getBasePath = () => {
    // Dynamicznie pobierz ścieżkę na podstawie lokalizacji skryptu app.js
    const script = document.querySelector('script[src*="assets/js/app.js"]');
    if (script) {
      const src = script.getAttribute('src');
      return src.replace("assets/js/app.js", "");
    }
    return "";
  };

  // Funkcja do wczytywania i wstawiania HTML
  const loadComponent = (url, element) => {
    if (!element) return;
    fetch(url)
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.text();
      })
      .then((data) => {
        // Automatycznie napraw ścieżki relatywne wewnątrz komponentu (dla podstron)
        // Zamienia href="plik" na href="../../plik" jeśli jesteśmy w podkatalogu
        const basePath = getBasePath();
        const adjustedData = data.replace(/(href|src)="(?!(?:https?:\/\/|#|\/|mailto:|tel:))/g, `$1="${basePath}`);
        element.innerHTML = adjustedData;

        // Po załadowaniu komponentów, wykonaj odpowiednie akcje
        if (element.id === "main-nav") {
          setActiveNavLink();
          initMobileNav(); // Inicjalizacja hamburgera po załadowaniu HTML
        }
        if (element.id === "main-footer") {
          // Ponownie wywołaj funkcję do aktualizacji roku w nowo dodanej stopce
          updateYearStamp(); 
        }
      })
      .catch((error) => {
        console.error(`Error loading component from ${url}:`, error);
        element.innerHTML = `<p style="color: red; text-align: center;">Błąd ładowania komponentu.</p>`;
      });
  };

  // Funkcja do oznaczania aktywnego linku w nawigacji
  const setActiveNavLink = () => {
    const navLinks = document.querySelectorAll("#main-nav .nav-links a");
    // Normalizacja URL: usuń parametry, index.html i końcowy slash
    const normalize = (url) => url.split(/[?#]/)[0].replace(/\/index\.html$/, "").replace(/\/$/, "");
    const currentUrl = normalize(window.location.href);

    navLinks.forEach((link) => {
      if (normalize(link.href) === currentUrl) {
        link.setAttribute("aria-current", "page");
      }
    });
  };

  // Funkcja obsługująca mobilne menu (Hamburger)
  const initMobileNav = () => {
    const toggleBtn = document.querySelector(".nav-toggle");
    const navLinks = document.querySelector(".nav-links");

    if (!toggleBtn || !navLinks) return;

    toggleBtn.addEventListener("click", function () {
      const isExpanded = toggleBtn.getAttribute("aria-expanded") === "true";
      toggleBtn.setAttribute("aria-expanded", !isExpanded);
      toggleBtn.classList.toggle("active");
      navLinks.classList.toggle("active");
    });

    // Zamknij menu po kliknięciu w link
    navLinks.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", () => {
        toggleBtn.setAttribute("aria-expanded", "false");
        toggleBtn.classList.remove("active");
        navLinks.classList.remove("active");
      });
    });
  };

  // Funkcja obsługująca otwieranie sekcji (accordion) na podstawie hasha w URL
  const handleHashNavigation = () => {
    if (window.location.hash) {
      const targetId = window.location.hash.substring(1);
      const targetElement = document.getElementById(targetId);

      if (targetElement) {
        // Jeśli element jest wewnątrz <details>, otwórz go
        const parentDetails = targetElement.closest('details');
        if (parentDetails) {
          parentDetails.open = true;
        }
        
        // Przewiń do elementu z uwzględnieniem offsetu nawigacji
        setTimeout(() => {
          const nav = document.querySelector("nav");
          const isMobile = window.matchMedia("(max-width: 840px)").matches;
          const offset = (nav && !isMobile) ? nav.offsetHeight + 20 : 20;
          const elementPosition = targetElement.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - offset;
          window.scrollTo({ top: offsetPosition, behavior: "smooth" });
        }, 100);
      }
    }
  };

  // --- Koniec nowego kodu ---
  
  document.addEventListener("DOMContentLoaded", function () {
    // Wczytaj nawigację i stopkę
    const basePath = getBasePath();
    loadComponent(basePath + "_nav.html", navEl);
    loadComponent(basePath + "_footer.html", footerEl);

    // Inicjalizuj pozostałe skrypty
    setupAccordion("data-detail-group");
    initReviewsFallback();
    loadNewsFromApi();
    handleHashNavigation();
  });
})();
