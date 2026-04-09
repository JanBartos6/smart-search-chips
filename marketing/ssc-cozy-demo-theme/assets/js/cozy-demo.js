document.addEventListener("DOMContentLoaded", function () {
    function setActiveFeature(target) {
        if (!target) {
            return;
        }

        document.querySelectorAll("[data-feature-target]").forEach(function (button) {
            var isActive = button.getAttribute("data-feature-target") === target;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });

        document.querySelectorAll("[data-feature-panel]").forEach(function (panel) {
            var isActive = panel.getAttribute("data-feature-panel") === target;
            panel.classList.toggle("is-active", isActive);

            if (isActive) {
                panel.removeAttribute("hidden");
                return;
            }

            panel.setAttribute("hidden", "hidden");
        });
    }

    function submitDemoQuery(input, query) {
        if (!input) {
            return;
        }

        var form = input.closest("form");
        if (!form) {
            return;
        }

        input.focus();
        input.value = query;
        input.dispatchEvent(new Event("input", { bubbles: true }));

        if (typeof form.requestSubmit === "function") {
            form.requestSubmit();
            return;
        }

        form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
    }

    var revealTargets = document.querySelectorAll("[data-reveal]");
    if (revealTargets.length) {
        document.documentElement.classList.add("ssc-has-motion");

        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("is-visible");
                revealObserver.unobserve(entry.target);
            });
        }, {
            threshold: 0.18
        });

        revealTargets.forEach(function (target) {
            revealObserver.observe(target);
        });
    }

    document.querySelectorAll("[data-demo-query]").forEach(function (button) {
        button.addEventListener("click", function () {
            var query = button.getAttribute("data-demo-query");
            var card = button.closest(".ssc-hero-card, .ssc-page-hero, .ssc-live-demo-shell");
            var input = card ? card.querySelector(".ssc-search__input") : document.querySelector(".ssc-search__input");

            submitDemoQuery(input, query);
        });
    });

    var autoloadSearch = document.querySelector("[data-demo-autoload]");
    if (autoloadSearch) {
        var autoloadInput = autoloadSearch.querySelector(".ssc-search__input");
        var initialQuery = autoloadSearch.getAttribute("data-demo-autoload");

        if (autoloadInput && initialQuery) {
            window.setTimeout(function () {
                submitDemoQuery(autoloadInput, initialQuery);
            }, 120);
        }
    }

    document.querySelectorAll("[data-feature-target]").forEach(function (button) {
        button.addEventListener("click", function () {
            setActiveFeature(button.getAttribute("data-feature-target"));
        });
    });
});
