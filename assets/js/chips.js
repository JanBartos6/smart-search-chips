/**
 * Smart Search Chips - Frontend JavaScript
 *
 * Handles chip toggling, AJAX search, and XHR interception
 * for existing theme search forms.
 *
 * @package SmartSearchChips
 * @version 1.0.0
 */

(function () {
    "use strict";

    var activeTags = [];
    var config = window.sscConfig || {};

    /**
     * Send search request with active tags.
     */
    function doSearch(input, resultsContainer, formEl) {
        if (!input || input.value.length < (config.minChars || 2)) {
            return;
        }

        var formData = new FormData();
        formData.append("action", config.action || "ssc_search");
        formData.append("keyword", input.value);
        formData.append("post_type", config.postType || "post");
        formData.append("f-form--submitted", "true");

        activeTags.forEach(function (t) {
            formData.append("active_tags[]", t);
        });

        var xhr = new XMLHttpRequest();
        xhr.open("POST", config.ajaxUrl || "/wp-admin/admin-ajax.php", true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200 && resultsContainer) {
                resultsContainer.innerHTML = xhr.responseText;
            }
        };

        xhr.send(formData);
    }

    /**
     * Handle chip clicks - toggle active state and re-search.
     */
    document.addEventListener("click", function (e) {
        var chip = e.target.closest(".ssc-chip");
        if (!chip) return;
        e.preventDefault();

        var tag = chip.getAttribute("data-tag");
        var idx = activeTags.indexOf(tag);

        if (idx > -1) {
            activeTags.splice(idx, 1);
        } else {
            activeTags.push(tag);
        }

        // Find the nearest search form and trigger re-search
        var container = chip.closest(".ssc-search") || chip.closest(".ssc-search__results, .js-ssc-results, .js-search__results");
        var form, input, results;

        if (container) {
            var parent = container.closest(".ssc-search") || container.parentElement;
            form = parent ? parent.querySelector("form") : null;
            input = form ? form.querySelector('input[type="search"]') : null;
            results = parent ? parent.querySelector(".js-ssc-results, .js-search__results, .ssc-search__results") : null;
        }

        // Fallback: find any search form on page
        if (!input) {
            form = document.querySelector(".js-ssc-search, .js-search");
            input = form ? form.querySelector('input[type="search"]') : null;
            results = form ? form.querySelector(".js-ssc-results, .js-search__results") : null;
            if (!results && form) {
                results = form.parentElement.querySelector(".js-ssc-results, .js-search__results");
            }
        }

        if (input && results) {
            doSearch(input, results, form);
        }
    });

    /**
     * Intercept existing AJAX search requests (from theme's search.js)
     * to append active tags.
     */
    var origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.send = function (data) {
        if (data instanceof FormData && activeTags.length > 0) {
            var hasAction = false;
            var entries = data.entries ? data.entries() : [];

            for (var pair of entries) {
                if (pair[0] === "action" && pair[1] === (config.action || "ssc_search")) {
                    hasAction = true;
                    break;
                }
            }

            if (hasAction) {
                activeTags.forEach(function (t) {
                    data.append("active_tags[]", t);
                });
            }
        }

        return origSend.apply(this, arguments);
    };

    /**
     * Reset active tags when search is cleared.
     */
    document.addEventListener("click", function (e) {
        if (e.target.closest(".js-search__clear, .ssc-search__clear")) {
            activeTags = [];
        }
    });

    /**
     * Built-in search form handler (for [ssc_search] shortcode).
     */
    document.addEventListener("keyup", function (e) {
        var input = e.target;
        if (!input.matches || !input.matches(".ssc-search__input")) return;

        var form = input.closest(".ssc-search");
        var results = form ? form.querySelector(".js-ssc-results") : null;

        if (input.value.length >= (config.minChars || 2)) {
            doSearch(input, results, form);
        } else if (results) {
            results.innerHTML = "";
        }
    });

    /**
     * Prevent built-in form submission (use AJAX instead).
     */
    document.addEventListener("submit", function (e) {
        if (e.target.matches && e.target.matches(".ssc-search__form, .js-ssc-search")) {
            e.preventDefault();
            var input = e.target.querySelector('input[type="search"]');
            var results = e.target.closest(".ssc-search");
            results = results ? results.querySelector(".js-ssc-results") : null;
            if (input) {
                doSearch(input, results, e.target);
            }
        }
    });
})();
