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
    var anchor = anchorSelector ? document.querySelector(anchorSelector) : null;
    var targetElement = anchor || summary;
    if (!targetElement) {
      return;
    }
    requestAnimationFrame(function () {
      var nav = document.querySelector("nav");
      var offset = nav ? nav.offsetHeight + 16 : 16;
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
      return;
    }

    var hasRedirected = false;
    var skipButton = document.querySelector(".skip-button");
    if (skipButton) {
      skipButton.addEventListener("click", function () {
        hasRedirected = true;
      });
    }

    function redirectToNews() {
      if (hasRedirected) {
        return;
      }
      hasRedirected = true;
      window.location.href = "pages/aktualnosci/";
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

  // --- Nowy kod do wstrzykiwania komponentów ---

  const navEl = document.getElementById("main-nav");
  const footerEl = document.getElementById("main-footer");

  // Funkcja pomocnicza do ustalania ścieżki bazowej (dla podstron w folderze pages/)
  const getBasePath = () => {
    return window.location.pathname.includes("/pages/") ? "../../" : "";
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
    const currentPath = window.location.pathname;

    navLinks.forEach((link) => {
      const linkPath = link.getAttribute("href");
      // Sprawdzamy, czy ścieżka linku jest częścią aktualnego URL
      if (linkPath !== "index.html" && currentPath.includes(linkPath)) {
        link.setAttribute("aria-current", "page");
      }
      // Specjalna obsługa strony głównej
      else if (linkPath === "index.html" && (currentPath.endsWith("/") || currentPath.endsWith("index.html"))) {
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
    // updateYearStamp(); // Usunięte, bo jest wywoływane po załadowaniu stopki
    setupAccordion("data-detail-group");
    initHomepageVideo();
  });
})();
