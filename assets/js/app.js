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

  function initHomepageVideo() {
    var playerContainer = document.getElementById("intro-player");
    if (!playerContainer) {
      // Jeśli nie ma playera na stronie (np. podstrony), nie uruchamiaj logiki YouTube
      return;
    }
    var endTimer = null;

    var hasRedirected = false;
    var skipButton = document.querySelector(".skip-button");
    if (skipButton) {
      skipButton.addEventListener("click", function () {
        hasRedirected = true;
      });
    }

    function redirectToNews() {
      if (endTimer) clearInterval(endTimer);
      if (hasRedirected) {
        return;
      }
      hasRedirected = true;
      window.location.href = "pages/aktualnosci/aktualnosci.html";
    }

    function createPlayer() {
      if (!(window.YT && typeof window.YT.Player === "function")) {
        return false;
      }

      new window.YT.Player("intro-player", {
        events: {
          onReady: function (event) {
            try {
              event.target.mute();
            } catch (error) {
              // ignore if mute is unavailable
            }
            event.target.playVideo();
          },
          onStateChange: function (event) {
            // Gdy film gra (PLAYING = 1), uruchom sprawdzanie czasu
            if (event.data === window.YT.PlayerState.PLAYING && !endTimer) {
              endTimer = setInterval(function() {
                try {
                  var duration = event.target.getDuration();
                  var currentTime = event.target.getCurrentTime();
                  // Jeśli do końca zostało mniej niż 9 sekund, przekieruj
                  if (duration > 0 && currentTime >= (duration - 9)) {
                    redirectToNews();
                  }
                } catch (e) {}
              }, 500);
            }
            if (event.data === window.YT.PlayerState.ENDED) {
              redirectToNews();
            }
          }
        }
      });

      return true;
    }

    if (createPlayer()) {
      return;
    }

    var attempts = 0;
    var poller = setInterval(function () {
      attempts += 1;
      if (createPlayer() || attempts > 40) {
        clearInterval(poller);
      }
    }, 250);
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

          html += `
            <article class="news-card">
              <div style="padding: 1rem 1rem 0.5rem; font-weight: bold;">
                <time datetime="${item.date}">${dateStr}</time>
              </div>
              ${imageHtml}
              <div class="news-card__body">
                ${item.title ? `<p class="news-card__tagline">${item.title}</p>` : ''}
                <p>${contentHtml}</p>
                ${item.link ? `<a class="button news-card__cta" href="${item.link}" target="_blank">Więcej</a>` : ''}
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

  // --- Koniec nowego kodu ---
  
  document.addEventListener("DOMContentLoaded", function () {
    // Wczytaj nawigację i stopkę
    const basePath = getBasePath();
    loadComponent(basePath + "_nav.html", navEl);
    loadComponent(basePath + "_footer.html", footerEl);

    // Inicjalizuj pozostałe skrypty
    setupAccordion("data-detail-group");
    initHomepageVideo();
    initReviewsFallback();
    loadNewsFromApi();
  });
})();
