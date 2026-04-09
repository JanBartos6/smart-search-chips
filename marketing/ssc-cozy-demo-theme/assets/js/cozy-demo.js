document.addEventListener("DOMContentLoaded", function () {
    function setActiveStory(target) {
        if (!target) {
            return;
        }

        document.querySelectorAll("[data-story-step]").forEach(function (step) {
            var isActive = step.getAttribute("data-story-step") === target;
            step.classList.toggle("is-active", isActive);
        });

        document.querySelectorAll("[data-story-jump]").forEach(function (button) {
            var isActive = button.getAttribute("data-story-jump") === target;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });

        document.querySelectorAll("[data-story-visual]").forEach(function (visual) {
            var isActive = visual.getAttribute("data-story-visual") === target;
            visual.classList.toggle("is-active", isActive);

            if (isActive) {
                visual.removeAttribute("hidden");
                return;
            }

            visual.setAttribute("hidden", "hidden");
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
            var card = button.closest(".ssc-hero-card, .ssc-hero-cinema, .ssc-page-hero, .ssc-live-demo-shell");
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

    var storySteps = document.querySelectorAll("[data-story-step]");
    if (storySteps.length) {
        setActiveStory(storySteps[0].getAttribute("data-story-step"));

        var storyObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                setActiveStory(entry.target.getAttribute("data-story-step"));
            });
        }, {
            threshold: 0.4
        });

        storySteps.forEach(function (step) {
            storyObserver.observe(step);
        });
    }

    document.querySelectorAll("[data-story-jump]").forEach(function (button) {
        button.addEventListener("click", function () {
            var target = button.getAttribute("data-story-jump");
            var step = document.querySelector('[data-story-step="' + target + '"]');

            setActiveStory(target);

            if (step) {
                step.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    });
});
