/**
 * Smart Search Chips - Frontend JavaScript
 *
 * Handles chip toggling, AJAX search with debounce,
 * loading states, and XHR interception for existing theme search forms.
 *
 * @package SmartSearchChips
 * @version 1.0.0
 */

(function () {
    "use strict";

    var activeTags = [];
    var config = window.sscConfig || {};
    var debounceTimer = null;
    var DEBOUNCE_MS = 300;

    /**
     * Send search request with active tags.
     */
    function doSearch(input, resultsContainer) {
        if (!input || input.value.length < (config.minChars || 2)) {
            if (resultsContainer) resultsContainer.innerHTML = "";
            return;
        }

        // Show loading
        if (resultsContainer) {
            resultsContainer.classList.add("ssc-loading");
        }

        var formData = new FormData();
        formData.append("action", config.action || "ssc_search");
        formData.append("keyword", input.value);
        formData.append("post_type", config.postType || "post");
        formData.append("f-form--submitted", "true");

        if (config.nonce) {
            formData.append("ssc_nonce", config.nonce);
        }

        activeTags.forEach(function (t) {
            formData.append("active_tags[]", t);
        });

        var xhr = new XMLHttpRequest();
        xhr.open("POST", config.ajaxUrl || "/wp-admin/admin-ajax.php", true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (resultsContainer) {
                    resultsContainer.classList.remove("ssc-loading");
                }
                if (xhr.status === 200 && resultsContainer) {
                    resultsContainer.innerHTML = xhr.responseText;
                }
            }
        };

        xhr.send(formData);
    }

    /**
     * Debounced search - waits for user to stop typing.
     */
    function debouncedSearch(input, resultsContainer) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            doSearch(input, resultsContainer);
        }, DEBOUNCE_MS);
    }

    /**
     * Find the results container relative to an element.
     */
    function findResults(el) {
        var container = el.closest(".ssc-search") || el.closest(".ssc-search__results, .js-ssc-results, .js-search__results");
        if (container) {
            var parent = container.closest(".ssc-search") || container.parentElement;
            return parent ? parent.querySelector(".js-ssc-results, .js-search__results, .ssc-search__results") : null;
        }
        // Fallback
        var form = document.querySelector(".js-ssc-search, .js-search");
        if (form) {
            var results = form.querySelector(".js-ssc-results, .js-search__results");
            if (!results) results = form.parentElement.querySelector(".js-ssc-results, .js-search__results");
            return results;
        }
        return null;
    }

    /**
     * Find the search input relative to an element.
     */
    function findInput(el) {
        var container = el.closest(".ssc-search");
        if (container) {
            return container.querySelector('input[type="search"]');
        }
        var form = document.querySelector(".js-ssc-search, .js-search");
        return form ? form.querySelector('input[type="search"]') : null;
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

        var input = findInput(chip);
        var results = findResults(chip);

        if (input && results) {
            doSearch(input, results); // immediate, no debounce for chip clicks
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
            if (data.entries) {
                for (var pair of data.entries()) {
                    if (pair[0] === "action" && pair[1] === (config.action || "ssc_search")) {
                        hasAction = true;
                        break;
                    }
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
     * Built-in search form handler (for [ssc_search] shortcode) - with debounce.
     */
    document.addEventListener("keyup", function (e) {
        var input = e.target;
        if (!input.matches || !input.matches(".ssc-search__input")) return;

        var results = findResults(input);

        if (input.value.length >= (config.minChars || 2)) {
            debouncedSearch(input, results);
        } else if (results) {
            clearTimeout(debounceTimer);
            results.innerHTML = "";
            activeTags = [];
        }
    });

    /**
     * Prevent built-in form submission (use AJAX instead).
     */
    document.addEventListener("submit", function (e) {
        if (e.target.matches && e.target.matches(".ssc-search__form, .js-ssc-search")) {
            e.preventDefault();
            var input = e.target.querySelector('input[type="search"]');
            var results = findResults(input);
            if (input) {
                doSearch(input, results);
            }
        }
    });
})();
