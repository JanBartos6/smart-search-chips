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
    var config = window.sschpConfig || {};
    var debounceTimer = null;
    var DEBOUNCE_MS = 300;
    var VISITOR_STORAGE_KEY = "sschp_visitor_id";

    function getResultsSelector() {
        return config.resultSelector || ".js-ssc-results, .js-search__results, .ssc-search__results";
    }

    function getKeywordField() {
        return config.keywordField || "keyword";
    }

    function getInputSelector() {
        return config.inputSelector || '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]';
    }

    function markSearchUiActive(input, resultsContainer) {
        if (input && typeof input.focus === "function" && document.activeElement !== input) {
            try {
                input.focus({ preventScroll: true });
            } catch (error) {
                input.focus();
            }
        }

        if (resultsContainer && resultsContainer.classList) {
            resultsContainer.classList.add("ssc-search-active");
            resultsContainer.style.display = "";
            resultsContainer.removeAttribute("hidden");
            resultsContainer.setAttribute("aria-hidden", "false");
            resultsContainer.setAttribute("aria-expanded", "true");
        }

        var root = input ? input.closest("form, .ssc-search, .js-search, .js-ssc-search") : null;
        if (root && root.classList) {
            root.classList.add("ssc-search-root-active");
            root.setAttribute("aria-expanded", "true");
        }
    }

    function closeSearchUi(input, resultsContainer) {
        clearTimeout(debounceTimer);

        if (resultsContainer && resultsContainer.classList) {
            resultsContainer.classList.remove("ssc-loading");
            resultsContainer.classList.remove("ssc-search-active");
            resultsContainer.innerHTML = "";
            resultsContainer.style.display = "none";
            resultsContainer.setAttribute("hidden", "hidden");
            resultsContainer.setAttribute("aria-hidden", "true");
            resultsContainer.setAttribute("aria-expanded", "false");
        }

        var root = input ? input.closest("form, .ssc-search, .js-search, .js-ssc-search") : null;
        if (root && root.classList) {
            root.classList.remove("ssc-search-root-active");
            root.setAttribute("aria-expanded", "false");
        }
    }

    function findSearchDismissControl(target) {
        if (!target || !target.closest) {
            return null;
        }

        return target.closest([
            ".js-search__clear",
            ".js-search__close",
            ".ssc-search__clear",
            ".ssc-search__close",
            "[data-search-clear]",
            "[data-search-close]",
            "button[type=\"reset\"]"
        ].join(", "));
    }

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
        data.append("action", "sschp_track_event");
        data.append("nonce", config.analytics.nonce);
        data.append("sschp_lang", config.lang || "");
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
            closeSearchUi(input, resultsContainer);
            return;
        }

        // Show loading
        if (resultsContainer) {
            resultsContainer.classList.add("ssc-loading");
        }

        var formData = new FormData();
        var keywordField = getKeywordField();
        formData.append("action", config.action || "sschp_search");
        formData.append(keywordField, input.value);
        if (keywordField !== "keyword") {
            formData.append("keyword", input.value);
        }
        formData.append("post_type", config.postType || "post");
        formData.append("f-form--submitted", "true");
        formData.append("sschp_lang", config.lang || "");

        if (config.nonce) {
            formData.append("sschp_nonce", config.nonce);
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
                    markSearchUiActive(input, resultsContainer);
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
        var selector = getResultsSelector();
        var container = el.closest(".ssc-search") || el.closest(selector);
        if (container) {
            var parent = container.closest(".ssc-search") || container.parentElement;
            return parent ? parent.querySelector(selector) : null;
        }
        // Fallback
        var form = document.querySelector(".js-ssc-search, .js-search");
        if (form) {
            var results = form.querySelector(selector);
            if (!results) results = form.parentElement.querySelector(selector);
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
            return container.querySelector(getInputSelector());
        }

        var results = el.closest(getResultsSelector());
        if (results) {
            var scopedRoot = results.closest("form, .js-search, .js-ssc-search") || results.parentElement;
            if (scopedRoot) {
                var scopedInput = scopedRoot.querySelector(getInputSelector());
                if (scopedInput) {
                    return scopedInput;
                }
            }
        }

        var form = document.querySelector(".js-ssc-search, .js-search");
        if (form) {
            var formInput = form.querySelector(getInputSelector());
            if (formInput) {
                return formInput;
            }
        }

        return document.querySelector(getInputSelector());
    }

    function applyTagSelection(source, tag, options, meta) {
        var input = findInput(source);
        var results = findResults(source);

        if (!input || !results) {
            return false;
        }

        options = options || {};

        if (options.replacePrefix) {
            activeTags = activeTags.filter(function (activeTag) {
                return activeTag.indexOf(options.replacePrefix) !== 0;
            });
        }

        if (tag) {
            var idx = activeTags.indexOf(tag);

            if (idx > -1 && options.mode !== "replace") {
                activeTags.splice(idx, 1);
            } else if (idx === -1) {
                activeTags.push(tag);
            }
        }

        doSearch(input, results, {
            trigger: "chip",
            chipLabel: meta && meta.chipLabel ? meta.chipLabel : tag
        });
        markSearchUiActive(input, results);

        return true;
    }

    document.addEventListener("sschp:applyTag", function (e) {
        var detail = e.detail || {};
        var source = detail.source;

        if (!source || !detail.replacePrefix && !detail.tag) {
            return;
        }

        applyTagSelection(source, detail.tag || "", {
            replacePrefix: detail.replacePrefix || "",
            mode: detail.mode || "replace"
        }, {
            chipLabel: detail.chipLabel || detail.tag || ""
        });
    });

    function handleChipActivation(e) {
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
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === "function") {
            e.stopImmediatePropagation();
        }

        applyTagSelection(chip, tag, {}, { chipLabel: tag });
    }

    /**
     * Keep chip clicks inside the search UI. Some themes close autocomplete
     * panels on document pointer/click handlers or input blur.
     */
    document.addEventListener("mousedown", function (e) {
        var chip = e.target.closest(".ssc-chip");
        if (!chip || !findInput(chip) || !findResults(chip)) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === "function") {
            e.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener("touchstart", function (e) {
        var chip = e.target.closest(".ssc-chip");
        if (!chip || !findInput(chip) || !findResults(chip)) {
            return;
        }

        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === "function") {
            e.stopImmediatePropagation();
        }
    }, true);

    /**
     * Handle chip clicks - toggle active state and re-search.
     */
    document.addEventListener("click", handleChipActivation, true);

    /**
     * Intercept existing AJAX search requests (from theme's search.js)
     * to append active tags.
     */
    var origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.send = function (data) {
        if (data instanceof FormData) {
            var hasAction = false;
            var keyword = "";
            var keywordField = getKeywordField();
            if (data.entries) {
                for (var pair of data.entries()) {
                    if (pair[0] === "action" && pair[1] === (config.action || "sschp_search")) {
                        hasAction = true;
                    }
                    if (pair[0] === keywordField || pair[0] === "keyword" || pair[0] === "s" || pair[0] === "query") {
                        keyword = pair[1];
                    }
                }
            }

            if (hasAction) {
                if (!this._sscManagedRequest) {
                    if (config.lang && !data.has("sschp_lang")) {
                        data.append("sschp_lang", config.lang);
                    }

                    if (config.nonce && !data.has("sschp_nonce")) {
                        data.append("sschp_nonce", config.nonce);
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
        var control = findSearchDismissControl(e.target);
        if (!control) {
            return;
        }

        var input = findInput(control);
        var results = findResults(control);
        activeTags = [];
        closeSearchUi(input, results);
        window.setTimeout(function () {
            closeSearchUi(input, results);
        }, 0);
    }, true);

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
            activeTags = [];
            closeSearchUi(input, results);
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
