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
    var VISITOR_STORAGE_KEY = "ssc_visitor_id";

    function getVisitorId() {
        var existing = "";

        try {
            existing = window.localStorage ? window.localStorage.getItem(VISITOR_STORAGE_KEY) : "";
        } catch (error) {
            existing = "";
        }

        if (existing) {
            return existing;
        }

        var generated = "ssc-" + Math.random().toString(36).slice(2) + Date.now().toString(36);

        try {
            if (window.localStorage) {
                window.localStorage.setItem(VISITOR_STORAGE_KEY, generated);
            }
        } catch (error) {
            // Ignore storage failures and continue with in-memory id for this page.
        }

        return generated;
    }

    function analyticsEnabled() {
        return !!(config.analytics && config.analytics.enabled && config.analytics.nonce);
    }

    function trackEvent(payload) {
        if (!analyticsEnabled()) {
            return;
        }

        var data = new FormData();
        data.append("action", "ssc_track_event");
        data.append("nonce", config.analytics.nonce);
        data.append("ssc_lang", config.lang || "");
        data.append("visitor_id", getVisitorId());
        data.append("event_type", payload.eventType || "");
        data.append("search_query", payload.searchQuery || "");
        data.append("chip_label", payload.chipLabel || "");
        data.append("page_url", window.location.href);
        data.append("result_count", String(typeof payload.resultCount === "number" ? payload.resultCount : -1));
        data.append("active_tag_count", String(activeTags.length));

        if (Array.isArray(payload.chipLabels)) {
            payload.chipLabels.forEach(function (label) {
                if (label) {
                    data.append("chip_labels[]", label);
                }
            });
        }

        if (navigator.sendBeacon) {
            navigator.sendBeacon(config.ajaxUrl || "/wp-admin/admin-ajax.php", data);
            return;
        }

        fetch(config.ajaxUrl || "/wp-admin/admin-ajax.php", {
            method: "POST",
            credentials: "same-origin",
            body: data
        }).catch(function () {
            // Analytics should never interrupt search UX.
        });
    }

    function getQueryParam(name) {
        try {
            var params = new URLSearchParams(window.location.search);
            return params.get(name) || "";
        } catch (error) {
            return "";
        }
    }

    function getCurrentSearchQuery() {
        var shortcodeInput = document.querySelector(".ssc-search__input");
        if (shortcodeInput && shortcodeInput.value) {
            return shortcodeInput.value;
        }

        return getQueryParam("s");
    }

    function getArchiveResultCount() {
        var posts = document.querySelectorAll("main .wp-block-query.alignfull .wp-block-post-template > li.wp-block-post");
        if (posts.length) {
            return posts.length;
        }

        var wooProducts = document.querySelectorAll("main ul.products > li.product");
        if (wooProducts.length) {
            return wooProducts.length;
        }

        return -1;
    }

    function trackChipImpressions(labels, context) {
        if (!labels || !labels.length) {
            return;
        }

        trackEvent({
            eventType: "chip_impression",
            searchQuery: context && context.searchQuery ? context.searchQuery : "",
            resultCount: context && typeof context.resultCount === "number" ? context.resultCount : -1,
            chipLabels: labels
        });
    }

    /**
     * Send search request with active tags.
     */
    function doSearch(input, resultsContainer, meta) {
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
        formData.append("ssc_lang", config.lang || "");

        if (config.nonce) {
            formData.append("ssc_nonce", config.nonce);
        }

        activeTags.forEach(function (t) {
            formData.append("active_tags[]", t);
        });

        var xhr = new XMLHttpRequest();
        xhr._sscManagedRequest = true;
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

                if (xhr.status === 200) {
                    var resultCount = parseInt(xhr.getResponseHeader("X-SSC-Results-Count"), 10);
                    if (isNaN(resultCount)) {
                        resultCount = -1;
                    }

                    trackEvent({
                        eventType: "search",
                        searchQuery: input.value,
                        resultCount: resultCount
                    });

                    if (resultsContainer) {
                        var visibleChips = Array.prototype.map.call(
                            resultsContainer.querySelectorAll(".ssc-chips .ssc-chip[data-tag]"),
                            function (chipNode) {
                                return chipNode.getAttribute("data-tag") || "";
                            }
                        ).filter(Boolean);

                        trackChipImpressions(visibleChips, {
                            searchQuery: input.value,
                            resultCount: resultCount
                        });
                    }

                    if (meta && meta.trigger === "chip" && meta.chipLabel) {
                        trackEvent({
                            eventType: "chip_click",
                            searchQuery: input.value,
                            chipLabel: meta.chipLabel,
                            resultCount: resultCount
                        });
                    }
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
            doSearch(input, resultsContainer, { trigger: "search" });
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

        var tag = chip.getAttribute("data-tag");
        var input = findInput(chip);
        var results = findResults(chip);

        // Archive/search-result chips are normal links and should keep
        // the browser's native navigation/history behavior.
        if (!input || !results) {
            if (chip.tagName === "A") {
                trackEvent({
                    eventType: "chip_click",
                    searchQuery: getCurrentSearchQuery(),
                    chipLabel: tag,
                    resultCount: getArchiveResultCount()
                });
            }
            return;
        }

        e.preventDefault();

        var idx = activeTags.indexOf(tag);

        if (idx > -1) {
            activeTags.splice(idx, 1);
        } else {
            activeTags.push(tag);
        }

        doSearch(input, results, {
            trigger: "chip",
            chipLabel: tag
        });
    });

    /**
     * Intercept existing AJAX search requests (from theme's search.js)
     * to append active tags.
     */
    var origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.send = function (data) {
        if (data instanceof FormData) {
            var hasAction = false;
            var keyword = "";
            if (data.entries) {
                for (var pair of data.entries()) {
                    if (pair[0] === "action" && pair[1] === (config.action || "ssc_search")) {
                        hasAction = true;
                    }
                    if (pair[0] === "keyword") {
                        keyword = pair[1];
                    }
                }
            }

            if (hasAction) {
                if (!this._sscManagedRequest) {
                    if (config.lang && !data.has("ssc_lang")) {
                        data.append("ssc_lang", config.lang);
                    }

                    activeTags.forEach(function (t) {
                        data.append("active_tags[]", t);
                    });

                    this.addEventListener("loadend", function () {
                        if (this.status !== 200 || !keyword || keyword.length < (config.minChars || 2)) {
                            return;
                        }

                        var resultCount = parseInt(this.getResponseHeader("X-SSC-Results-Count"), 10);
                        if (isNaN(resultCount)) {
                            resultCount = -1;
                        }

                        trackEvent({
                            eventType: "search",
                            searchQuery: keyword,
                            resultCount: resultCount
                        });
                    }, { once: true });
                }
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
                doSearch(input, results, { trigger: "submit" });
            }
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        var archiveChipLabels = Array.prototype.map.call(
            document.querySelectorAll(".ssc-loop-filters .ssc-chip[data-tag]"),
            function (chipNode) {
                return chipNode.getAttribute("data-tag") || "";
            }
        ).filter(Boolean);

        trackChipImpressions(archiveChipLabels, {
            searchQuery: getCurrentSearchQuery(),
            resultCount: getArchiveResultCount()
        });
    });
})();
