(function () {
  function updateYearStamp() {
    var stamp = document.getElementById("year");
    if (stamp) {
      stamp.textContent = new Date().getFullYear();
    }
  }

  function scrollToSummary(detailsEl) {
    var summary = detailsEl.querySelector(":scope > summary");
    if (!summary) {
      return;
    }
    requestAnimationFrame(function () {
      var nav = document.querySelector("nav");
      var offset = nav ? nav.offsetHeight + 16 : 16;
      var target = summary.getBoundingClientRect().top + window.pageYOffset - offset;
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

  document.addEventListener("DOMContentLoaded", function () {
    updateYearStamp();
    setupAccordion("data-detail-group");
    initHomepageVideo();
  });
})();
