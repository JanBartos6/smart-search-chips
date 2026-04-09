document.addEventListener("DOMContentLoaded", function () {
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
            var input = document.querySelector(".ssc-search__input");

            if (!input) {
                return;
            }

            input.focus();
            input.value = query;
            input.dispatchEvent(new Event("input", { bubbles: true }));
        });
    });
});
