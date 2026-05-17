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

    var config = window.sschpConfig || {};
    var searchStates = new WeakMap();
    var selectorWarnings = {};
    var DEBOUNCE_MS = 300;
    var SEARCH_EVENT_DEDUP_MS = 1000;
    var IMPRESSION_DEDUP_MS = 10000;
    var VISITOR_STORAGE_KEY = "sschp_visitor_id";
    var DEFAULT_INPUT_SELECTOR = '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]';
    var DEFAULT_RESULTS_SELECTOR = ".js-ssc-results, .js-search__results, .ssc-search__results";

    window.sschpDiagnostics = window.sschpDiagnostics || {
        lastDetectedRequestMethod: "",
        transport: "",
        bodyType: "",
        actionDetected: false,
        nonceAttached: false,
        keywordFound: false,
        resultContainerFound: false,
        modified: false
    };

    function debugEnabled() {
        if (!config.isAdmin) {
            return false;
        }

        try {
            return new URLSearchParams(window.location.search).get("sschp_debug") === "1";
        } catch (error) {
            return false;
        }
    }

    function debugLog(message, data) {
        if (!debugEnabled() || !window.console || typeof window.console.info !== "function") {
            return;
        }

        window.console.info("[Smart Search Chips Debug] " + message, data || {});
    }

    function warnInvalidSelectorOnce(label, selector) {
        var key = label + ":" + selector;

        if (!config.isAdmin || selectorWarnings[key]) {
            return;
        }

        selectorWarnings[key] = true;

        if (window.console && typeof window.console.warn === "function") {
            window.console.warn("Smart Search Chips ignored an invalid " + label + " selector and used the default selector instead.");
        }
    }

    function isValidSelector(selector) {
        if (!selector) {
            return false;
        }

        try {
            document.createDocumentFragment().querySelector(selector);
            return true;
        } catch (error) {
            return false;
        }
    }

    function getSafeConfiguredSelector(selector, fallback, label) {
        selector = (selector || "").trim();

        if (!selector) {
            return fallback;
        }

        if (isValidSelector(selector)) {
            return selector;
        }

        warnInvalidSelectorOnce(label, selector);
        return fallback;
    }

    function safeMatches(el, selector) {
        if (!el || !el.matches || !selector) {
            return false;
        }

        try {
            return el.matches(selector);
        } catch (error) {
            return false;
        }
    }

    function safeClosest(el, selector) {
        if (!el || !el.closest || !selector) {
            return null;
        }

        try {
            return el.closest(selector);
        } catch (error) {
            return null;
        }
    }

    function safeQuerySelector(root, selector) {
        if (!root || !root.querySelector || !selector) {
            return null;
        }

        try {
            return root.querySelector(selector);
        } catch (error) {
            return null;
        }
    }

    function safeQuerySelectorAll(root, selector) {
        if (!root || !root.querySelectorAll || !selector) {
            return [];
        }

        try {
            return root.querySelectorAll(selector);
        } catch (error) {
            return [];
        }
    }

    function getResultsSelector() {
        return getSafeConfiguredSelector(config.resultSelector, DEFAULT_RESULTS_SELECTOR, "results container");
    }

    function getKeywordField() {
        return config.keywordField || "keyword";
    }

    function getSearchAction() {
        return config.action || "sschp_search";
    }

    function getThemeAction() {
        var themeAction = config.themeAction || config.overrideAction || "";

        if (!themeAction && config.action && config.action !== "sschp_search") {
            themeAction = config.action;
        }

        return themeAction;
    }

    function themeEnhancementEnabled() {
        return !!getThemeAction();
    }

    function getConfiguredAjaxUrl() {
        return config.ajaxUrl || "/wp-admin/admin-ajax.php";
    }

    function getInputSelector() {
        return getSafeConfiguredSelector(config.inputSelector, DEFAULT_INPUT_SELECTOR, "search input");
    }

    function getSearchRoot(el) {
        if (!el || !el.closest) {
            return document.documentElement;
        }

        var shortcodeRoot = safeClosest(el, ".ssc-search");
        if (shortcodeRoot) {
            return shortcodeRoot;
        }

        var themeRoot = safeClosest(el, ".js-search, .js-ssc-search");
        if (themeRoot) {
            return themeRoot;
        }

        var results = safeClosest(el, getResultsSelector());
        if (results) {
            return results.parentElement || document.documentElement;
        }

        var form = safeClosest(el, "form");
        if (form) {
            return form;
        }

        return document.documentElement;
    }

    function getState(el) {
        var root = getSearchRoot(el);

        if (!searchStates.has(root)) {
            searchStates.set(root, {
                activeTags: [],
                debounceTimer: null,
                lastQuery: "",
                lastResultsContainer: null,
                pendingFocusTag: "",
                currentXhr: null,
                requestId: 0,
                lastRenderedHtml: "",
                lastSearchEventKey: "",
                lastSearchEventAt: 0,
                lastImpressionKey: "",
                lastImpressionAt: 0
            });
        }

        return searchStates.get(root);
    }

    function getDebounceMs() {
        var value = parseInt(config.debounceMs, 10);

        if (isNaN(value)) {
            value = DEBOUNCE_MS;
        }

        return Math.max(50, Math.min(2000, value));
    }

    function normalizeTag(tag) {
        return String(tag || "").toLowerCase();
    }

    function findActiveTagIndex(activeTags, tag) {
        var normalized = normalizeTag(tag);

        for (var i = 0; i < activeTags.length; i++) {
            if (normalizeTag(activeTags[i]) === normalized) {
                return i;
            }
        }

        return -1;
    }

    function isSearchInput(el) {
        return safeMatches(el, getInputSelector());
    }

    function findInputForKeyword(keyword) {
        var active = document.activeElement;
        if (isSearchInput(active) && (!keyword || active.value === keyword)) {
            return active;
        }

        var inputs = safeQuerySelectorAll(document, getInputSelector());
        for (var i = 0; i < inputs.length; i++) {
            if (!keyword || inputs[i].value === keyword) {
                return inputs[i];
            }
        }

        return isSearchInput(active) ? active : null;
    }

    function ensureResultsAccessibility(resultsContainer) {
        if (!resultsContainer) {
            return;
        }

        if (!resultsContainer.hasAttribute("aria-live")) {
            resultsContainer.setAttribute("aria-live", "polite");
        }

        if (!resultsContainer.hasAttribute("aria-busy")) {
            resultsContainer.setAttribute("aria-busy", "false");
        }
    }

    function setResultsBusy(resultsContainer, isBusy) {
        if (!resultsContainer) {
            return;
        }

        ensureResultsAccessibility(resultsContainer);
        resultsContainer.setAttribute("aria-busy", isBusy ? "true" : "false");
    }

    function focusWithoutScroll(el) {
        if (!el || typeof el.focus !== "function") {
            return false;
        }

        try {
            el.focus({ preventScroll: true });
        } catch (error) {
            el.focus();
        }

        return true;
    }

    function restoreChipFocus(resultsContainer, tag) {
        if (!resultsContainer || !tag) {
            return false;
        }

        var chips = safeQuerySelectorAll(resultsContainer, ".ssc-chip[data-tag]");
        for (var i = 0; i < chips.length; i++) {
            if (normalizeTag(chips[i].getAttribute("data-tag")) === normalizeTag(tag)) {
                return focusWithoutScroll(chips[i]);
            }
        }

        return false;
    }

    function markSearchUiActive(input, resultsContainer) {
        if (input && document.activeElement !== input) {
            focusWithoutScroll(input);
        }

        if (resultsContainer && resultsContainer.classList) {
            ensureResultsAccessibility(resultsContainer);
            resultsContainer.classList.add("ssc-search-active");
            resultsContainer.style.display = "";
            resultsContainer.removeAttribute("hidden");
            resultsContainer.setAttribute("aria-hidden", "false");
            resultsContainer.setAttribute("aria-expanded", "true");
        }

        var root = getSearchRoot(input || resultsContainer);
        if (root && root.classList) {
            root.classList.add("ssc-search-root-active");
            root.setAttribute("aria-expanded", "true");
        }
    }

    function closeSearchUi(input, resultsContainer) {
        var state = getState(input || resultsContainer);
        clearTimeout(state.debounceTimer);
        state.debounceTimer = null;

        if (state.currentXhr && state.currentXhr.readyState !== 4) {
            try {
                state.currentXhr.abort();
            } catch (error) {
                // Ignore abort failures from mocked or legacy transports.
            }
        }
        state.currentXhr = null;
        state.requestId += 1;

        if (resultsContainer) {
            state.lastResultsContainer = resultsContainer;
        }

        if (resultsContainer && resultsContainer.classList) {
            resultsContainer.classList.remove("ssc-loading");
            setResultsBusy(resultsContainer, false);
            resultsContainer.classList.remove("ssc-search-active");
            resultsContainer.innerHTML = "";
            state.lastRenderedHtml = "";
            resultsContainer.style.display = "none";
            resultsContainer.setAttribute("hidden", "hidden");
            resultsContainer.setAttribute("aria-hidden", "true");
            resultsContainer.setAttribute("aria-expanded", "false");
        }

        var root = getSearchRoot(input || resultsContainer);
        if (root && root.classList) {
            root.classList.remove("ssc-search-root-active");
            root.setAttribute("aria-expanded", "false");
        }
    }

    function findSearchDismissControl(target) {
        if (!target || !target.closest) {
            return null;
        }

        return safeClosest(target, [
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

    function sendAnalyticsWithFetch(url, data) {
        if (!window.fetch) {
            return;
        }

        try {
            window.fetch(url, {
                method: "POST",
                credentials: "same-origin",
                keepalive: true,
                body: data
            }).catch(function () {
                // Analytics should never interrupt search UX.
            });
        } catch (error) {
            // Analytics should never interrupt search UX.
        }
    }

    function trackEvent(payload, source) {
        try {
            if (!analyticsEnabled()) {
                return;
            }

            var state = source ? getState(source) : null;
            var url = config.ajaxUrl || "/wp-admin/admin-ajax.php";
            var data = new FormData();
            data.append("action", "sschp_track_event");
            data.append("nonce", config.analytics.nonce);
            data.append("sschp_lang", config.lang || "");
            data.append("visitor_id", getVisitorId());
            data.append("event_type", payload.eventType || "");
            data.append("search_query", payload.searchQuery || "");
            data.append("chip_label", payload.chipLabel || "");
            data.append("post_type", payload.postType || config.postType || "");
            data.append("product_category", payload.productCategory || config.productCategory || "");
            data.append("page_url", window.location.href);
            data.append("result_count", String(typeof payload.resultCount === "number" ? payload.resultCount : -1));
            data.append("active_tag_count", String(state ? state.activeTags.length : 0));
            data.append("result_object_id", String(payload.resultObjectId || 0));
            data.append("result_position", String(payload.resultPosition || 0));

            if (Array.isArray(payload.chipLabels)) {
                payload.chipLabels.forEach(function (label) {
                    if (label) {
                        data.append("chip_labels[]", label);
                    }
                });
            }

            if (window.navigator && typeof window.navigator.sendBeacon === "function") {
                try {
                    if (window.navigator.sendBeacon(url, data)) {
                        return;
                    }
                } catch (error) {
                    // Fall through to fetch.
                }
            }

            sendAnalyticsWithFetch(url, data);
        } catch (error) {
            // Analytics should never interrupt search UX.
        }
    }

    function normalizeImpressionLabels(labels) {
        var seen = {};
        var normalized = [];

        labels.forEach(function (label) {
            var value = String(label || "").trim();
            var key = normalizeTag(value);

            if (!value || seen[key]) {
                return;
            }

            seen[key] = true;
            normalized.push(key);
        });

        normalized.sort();
        return normalized;
    }

    function shouldTrackImpressionBatch(labels, context, source) {
        var normalizedLabels = normalizeImpressionLabels(labels);

        if (!normalizedLabels.length) {
            return false;
        }

        var state = getState(source || document.documentElement);
        var query = context && context.searchQuery ? String(context.searchQuery) : "";
        var key = query + "|" + normalizedLabels.join("|");
        var now = Date.now ? Date.now() : new Date().getTime();

        if (state.lastImpressionKey === key && now - state.lastImpressionAt < IMPRESSION_DEDUP_MS) {
            return false;
        }

        state.lastImpressionKey = key;
        state.lastImpressionAt = now;

        return true;
    }

    function shouldTrackSearchEvent(query, resultCount, source) {
        var state = getState(source || document.documentElement);
        var key = String(query || "") + "|" + String(resultCount) + "|" + state.activeTags.map(normalizeTag).sort().join("|");
        var now = Date.now ? Date.now() : new Date().getTime();

        if (state.lastSearchEventKey === key && now - state.lastSearchEventAt < SEARCH_EVENT_DEDUP_MS) {
            return false;
        }

        state.lastSearchEventKey = key;
        state.lastSearchEventAt = now;

        return true;
    }

    function trackChipImpressions(labels, context, source) {
        if (!labels || !labels.length || !analyticsEnabled()) {
            return;
        }

        if (!shouldTrackImpressionBatch(labels, context, source)) {
            return;
        }

        trackEvent({
            eventType: "chip_impression",
            searchQuery: context && context.searchQuery ? context.searchQuery : "",
            resultCount: context && typeof context.resultCount === "number" ? context.resultCount : -1,
            postType: context && context.postType ? context.postType : "",
            productCategory: context && context.productCategory ? context.productCategory : "",
            chipLabels: labels
        }, source);
    }

    function trackResultClick(link) {
        if (!link || !analyticsEnabled()) {
            return;
        }

        var postId = parseInt(link.getAttribute("data-sschp-post-id"), 10);
        if (!postId || isNaN(postId)) {
            return;
        }

        var state = getState(link);
        var position = parseInt(link.getAttribute("data-sschp-result-position"), 10);
        if (isNaN(position)) {
            position = 0;
        }

        trackEvent({
            eventType: "result_click",
            searchQuery: state.lastQuery || getCurrentSearchQuery(),
            resultObjectId: postId,
            resultPosition: position,
            postType: link.getAttribute("data-sschp-post-type") || config.postType || "",
            productCategory: config.productCategory || ""
        }, link);
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
        var shortcodeInput = safeQuerySelector(document, ".ssc-search__input");
        if (shortcodeInput && shortcodeInput.value) {
            return shortcodeInput.value;
        }

        return getQueryParam("s");
    }

    function getArchiveResultCount() {
        var posts = safeQuerySelectorAll(document, "main .wp-block-query.alignfull .wp-block-post-template > li.wp-block-post");
        if (posts.length) {
            return posts.length;
        }

        var wooProducts = safeQuerySelectorAll(document, "main ul.products > li.product");
        if (wooProducts.length) {
            return wooProducts.length;
        }

        return -1;
    }

    /**
     * Send search request with active tags.
     */
    function doSearch(input, resultsContainer, meta) {
        var state = getState(input || resultsContainer);
        var requestAction = safeClosest(input, ".ssc-search") ? getSearchAction() : (getThemeAction() || getSearchAction());

        if (!input || input.value.length < (config.minChars || 2)) {
            closeSearchUi(input, resultsContainer);
            return;
        }

        state.lastQuery = input.value;
        state.lastResultsContainer = resultsContainer || null;
        state.requestId += 1;
        var requestId = state.requestId;

        if (state.currentXhr && state.currentXhr.readyState !== 4) {
            try {
                state.currentXhr.abort();
            } catch (error) {
                // Ignore abort failures from mocked or legacy transports.
            }
        }

        // Show loading
        if (resultsContainer) {
            resultsContainer.classList.add("ssc-loading");
            setResultsBusy(resultsContainer, true);
        }

        debugLog("Managed AJAX request prepared", {
            action: requestAction,
            transport: "XMLHttpRequest",
            keywordField: getKeywordField(),
            inputFound: !!input,
            resultContainerFound: !!resultsContainer,
            activeTagCount: state.activeTags.length
        });

        var formData = new FormData();
        var keywordField = getKeywordField();
        formData.append("action", requestAction);
        formData.append(keywordField, input.value);
        if (keywordField !== "keyword") {
            formData.append("keyword", input.value);
        }
        formData.append("sschp_managed", "1");
        formData.append("post_type", config.postType || "post");
        formData.append("f-form--submitted", "true");
        formData.append("sschp_lang", config.lang || "");

        if (config.nonce) {
            formData.append("sschp_nonce", config.nonce);
        }

        state.activeTags.forEach(function (t) {
            formData.append("active_tags[]", t);
        });

        var xhr = new XMLHttpRequest();
        state.currentXhr = xhr;
        xhr._sscManagedRequest = true;
        xhr.open("POST", getConfiguredAjaxUrl(), true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (requestId !== state.requestId) {
                    return;
                }

                state.currentXhr = null;

                if (resultsContainer) {
                    resultsContainer.classList.remove("ssc-loading");
                    setResultsBusy(resultsContainer, false);
                }
                if (xhr.status === 200 && resultsContainer) {
                    if (state.lastRenderedHtml !== xhr.responseText) {
                        resultsContainer.innerHTML = xhr.responseText;
                        state.lastRenderedHtml = xhr.responseText;
                    }
                    markSearchUiActive(input, resultsContainer);

                    if (state.pendingFocusTag) {
                        restoreChipFocus(resultsContainer, state.pendingFocusTag);
                        state.pendingFocusTag = "";
                    }
                } else if (xhr.status !== 200) {
                    state.pendingFocusTag = "";
                }

                if (xhr.status === 200) {
                    var resultCount = parseInt(xhr.getResponseHeader("X-SSC-Results-Count"), 10);
                    if (isNaN(resultCount)) {
                        resultCount = -1;
                    }

                    if (shouldTrackSearchEvent(input.value, resultCount, input)) {
                        trackEvent({
                            eventType: "search",
                            searchQuery: input.value,
                            resultCount: resultCount
                        }, input);
                    }

                    if (resultsContainer) {
                        var visibleChips = Array.prototype.map.call(
                            safeQuerySelectorAll(resultsContainer, ".ssc-chips .ssc-chip[data-tag]"),
                            function (chipNode) {
                                return chipNode.getAttribute("data-tag") || "";
                            }
                        ).filter(Boolean);

                        trackChipImpressions(visibleChips, {
                            searchQuery: input.value,
                            resultCount: resultCount
                        }, input);
                    }

                    if (meta && meta.trigger === "chip" && meta.chipLabel) {
                        trackEvent({
                            eventType: "chip_click",
                            searchQuery: input.value,
                            chipLabel: meta.chipLabel,
                            resultCount: resultCount
                        }, input);
                    }
                }
            }
        };

        xhr.onabort = function () {
            if (requestId === state.requestId && resultsContainer) {
                resultsContainer.classList.remove("ssc-loading");
                setResultsBusy(resultsContainer, false);
            }
        };

        xhr.send(formData);
    }

    /**
     * Debounced search - waits for user to stop typing.
     */
    function debouncedSearch(input, resultsContainer) {
        var state = getState(input || resultsContainer);
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(function () {
            state.debounceTimer = null;
            doSearch(input, resultsContainer, { trigger: "search" });
        }, getDebounceMs());
    }

    /**
     * Find the results container relative to an element.
     */
    function findResults(el) {
        if (!el || !el.closest || safeClosest(el, ".ssc-loop-filters")) {
            return null;
        }

        var selector = getResultsSelector();
        var root = getSearchRoot(el);
        var results = safeMatches(root, selector) ? root : null;

        if (!results) {
            results = safeQuerySelector(root, selector);
        }

        if (!results && root && safeMatches(root.nextElementSibling, selector)) {
            results = root.nextElementSibling;
        }

        if (!results && root && root.parentElement) {
            results = safeQuerySelector(root.parentElement, selector);
        }

        if (results) {
            ensureResultsAccessibility(results);
            return results;
        }

        return null;
    }

    /**
     * Find the search input relative to an element.
     */
    function findInput(el) {
        if (!el || !el.closest || safeClosest(el, ".ssc-loop-filters")) {
            return null;
        }

        var selector = getInputSelector();
        var root = getSearchRoot(el);
        var scopedInput = safeMatches(root, selector) ? root : null;

        if (!scopedInput) {
            scopedInput = safeQuerySelector(root, selector);
        }

        if (!scopedInput && root && root.parentElement) {
            scopedInput = safeQuerySelector(root.parentElement, selector);
        }

        if (scopedInput) {
            return scopedInput;
        }

        return isSearchInput(document.activeElement) ? document.activeElement : null;
    }

    function applyTagSelection(source, tag, options, meta) {
        var input = findInput(source);
        var results = findResults(source);

        if (!input || !results) {
            return false;
        }

        var state = getState(input);
        options = options || {};

        if (options.replacePrefix) {
            var normalizedPrefix = normalizeTag(options.replacePrefix);
            state.activeTags = state.activeTags.filter(function (activeTag) {
                return normalizeTag(activeTag).indexOf(normalizedPrefix) !== 0;
            });
        }

        if (tag) {
            var idx = findActiveTagIndex(state.activeTags, tag);

            if (idx > -1 && options.mode !== "replace") {
                state.activeTags.splice(idx, 1);
            } else if (idx === -1) {
                state.activeTags.push(tag);
            }
        }

        if (tag && meta && meta.preserveFocus) {
            state.pendingFocusTag = tag;
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
        var chip = safeClosest(e.target, ".ssc-chip");
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

        applyTagSelection(chip, tag, {}, { chipLabel: tag, preserveFocus: true });
    }

    /**
     * Keep chip clicks inside the search UI. Some themes close autocomplete
     * panels on document pointer/click handlers or input blur.
     */
    document.addEventListener("mousedown", function (e) {
        var chip = safeClosest(e.target, ".ssc-chip");
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
        var chip = safeClosest(e.target, ".ssc-chip");
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

    function toUrl(url) {
        var value = String(url || "");

        try {
            return new URL(value, window.location.href);
        } catch (error) {
            try {
                return new URL(value, window.location.origin && window.location.origin !== "null" ? window.location.origin : "http://localhost");
            } catch (fallbackError) {
                return null;
            }
        }
    }

    function sameAjaxPath(left, right) {
        if (!left || !right) {
            return false;
        }

        try {
            return decodeURIComponent(left.pathname) === decodeURIComponent(right.pathname);
        } catch (error) {
            return left.pathname === right.pathname;
        }
    }

    function isAdminAjaxUrl(url) {
        var parsed = toUrl(url);
        var configured = toUrl(getConfiguredAjaxUrl());

        if (!parsed) {
            return false;
        }

        if (configured && sameAjaxPath(parsed, configured)) {
            return true;
        }

        return /\/admin-ajax\.php$/i.test(parsed.pathname);
    }

    function entriesFromParams(params) {
        var entries = [];

        if (!params || !params.forEach) {
            return entries;
        }

        params.forEach(function (value, key) {
            entries.push([key, value]);
        });

        return entries;
    }

    function entriesFromObject(object) {
        var entries = [];

        if (!object || typeof object !== "object") {
            return entries;
        }

        Object.keys(object).forEach(function (key) {
            var value = object[key];

            if (Array.isArray(value)) {
                value.forEach(function (item) {
                    entries.push([key, item]);
                });
            } else if (value !== null && typeof value !== "object") {
                entries.push([key, value]);
            }
        });

        return entries;
    }

    function getBodyInfo(body) {
        var info = {
            body: body,
            bodyType: "none",
            entries: [],
            parsedObject: null,
            supported: false
        };

        if (!body) {
            return info;
        }

        if (window.FormData && body instanceof FormData) {
            info.bodyType = "formdata";
            info.entries = Array.from(body.entries());
            info.supported = true;
            return info;
        }

        if (window.URLSearchParams && body instanceof URLSearchParams) {
            info.bodyType = "urlsearchparams";
            info.entries = entriesFromParams(body);
            info.supported = true;
            return info;
        }

        if (typeof body === "string") {
            var trimmed = body.trim();

            if (trimmed.charAt(0) === "{" || trimmed.charAt(0) === "[") {
                try {
                    info.parsedObject = JSON.parse(trimmed);
                    info.bodyType = "json";
                    info.entries = entriesFromObject(info.parsedObject);
                    info.supported = info.parsedObject && !Array.isArray(info.parsedObject) && typeof info.parsedObject === "object";
                    return info;
                } catch (error) {
                    info.bodyType = "string";
                    return info;
                }
            }

            info.bodyType = "urlencoded-string";
            info.parsedObject = new URLSearchParams(body);
            info.entries = entriesFromParams(info.parsedObject);
            info.supported = true;
            return info;
        }

        info.bodyType = "unsupported";
        return info;
    }

    function getEntry(entries, key) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i][0] === key) {
                return String(entries[i][1] || "");
            }
        }

        return "";
    }

    function hasEntry(entries, key) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i][0] === key) {
                return true;
            }
        }

        return false;
    }

    function hasActiveTagEntries(entries) {
        return hasEntry(entries, "active_tags[]") || hasEntry(entries, "active_tags");
    }

    function getKeywordFromEntries(entries) {
        var fields = [getKeywordField(), "keyword", "s", "query", "search"];
        var seen = {};

        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (!field || seen[field]) {
                continue;
            }

            seen[field] = true;

            var value = getEntry(entries, field);
            if (value) {
                return value;
            }
        }

        return "";
    }

    function appendParams(params, state, entries, options) {
        var result = {
            activeTagsAttached: false,
            nonceAttached: false,
            managedAttached: false
        };

        options = options || {};

        if (!params.has("sschp_managed")) {
            params.append("sschp_managed", "1");
            result.managedAttached = true;
        }

        if (config.nonce && !params.has("sschp_nonce")) {
            params.append("sschp_nonce", config.nonce);
            result.nonceAttached = true;
        }

        if (config.lang && !params.has("sschp_lang")) {
            params.append("sschp_lang", config.lang);
        }

        if (!options.skipAction && !params.has("action")) {
            params.append("action", getThemeAction() || getSearchAction());
        }

        if (options.keyword && !params.has(getKeywordField()) && !params.has("keyword")) {
            params.append(getKeywordField(), options.keyword);
        }

        if (!hasActiveTagEntries(entries)) {
            state.activeTags.forEach(function (tag) {
                params.append("active_tags[]", tag);
                result.activeTagsAttached = true;
            });
        }

        return result;
    }

    function appendObjectFields(object, state, entries, keyword, options) {
        var result = {
            activeTagsAttached: false,
            nonceAttached: false,
            managedAttached: false
        };

        options = options || {};

        if (!object.sschp_managed) {
            object.sschp_managed = "1";
            result.managedAttached = true;
        }

        if (config.nonce && !object.sschp_nonce) {
            object.sschp_nonce = config.nonce;
            result.nonceAttached = true;
        }

        if (config.lang && !object.sschp_lang) {
            object.sschp_lang = config.lang;
        }

        if (keyword && !object[getKeywordField()] && !object.keyword) {
            object[getKeywordField()] = keyword;
        }

        if (!options.skipActiveTags && !hasActiveTagEntries(entries)) {
            object.active_tags = state.activeTags.slice();
            result.activeTagsAttached = object.active_tags.length > 0;
        }

        return result;
    }

    function updateIntegrationDiagnostics(data) {
        window.sschpDiagnostics = Object.assign(window.sschpDiagnostics || {}, {
            lastDetectedRequestMethod: data.method || "",
            transport: data.transport || "",
            bodyType: data.bodyType || "",
            actionDetected: !!data.actionDetected,
            nonceAttached: !!data.nonceAttached,
            keywordFound: !!data.keywordFound,
            resultContainerFound: !!data.resultContainerFound,
            modified: !!data.modified
        });
    }

    function trackIntegrationResponse(response, keyword, input) {
        if (!keyword || keyword.length < (config.minChars || 2)) {
            return;
        }

        var resultCount = -1;

        if (response && response.headers && typeof response.headers.get === "function") {
            resultCount = parseInt(response.headers.get("X-SSC-Results-Count"), 10);
        } else if (response && typeof response.getResponseHeader === "function") {
            resultCount = parseInt(response.getResponseHeader("X-SSC-Results-Count"), 10);
        }

        if (isNaN(resultCount)) {
            resultCount = -1;
        }

        trackEvent({
            eventType: "search",
            searchQuery: keyword,
            resultCount: resultCount
        }, input);
    }

    function prepareIntegratedRequest(options) {
        var method = String(options.method || "GET").toUpperCase();
        var url = options.url || getConfiguredAjaxUrl();
        var parsedUrl = toUrl(url);
        var bodyInfo = getBodyInfo(options.body);
        var urlEntries = parsedUrl ? entriesFromParams(parsedUrl.searchParams) : [];
        var entries = bodyInfo.entries.concat(urlEntries);
        var action = getEntry(entries, "action");
        var themeAction = getThemeAction();
        var actionDetected = !!themeAction && action === themeAction;
        var keyword = getKeywordFromEntries(entries);
        var requestInput = findInputForKeyword(keyword);
        var requestState = getState(requestInput || document.documentElement);
        var resultContainerFound = !!(requestInput && findResults(requestInput));
        var diagnostic = {
            method: method,
            transport: options.transport || "",
            bodyType: bodyInfo.bodyType,
            actionDetected: actionDetected,
            nonceAttached: hasEntry(entries, "sschp_nonce"),
            keywordFound: !!keyword,
            resultContainerFound: resultContainerFound,
            modified: false
        };
        var result = {
            url: url,
            body: options.body,
            bodyChanged: false,
            urlChanged: false,
            modified: false,
            keyword: keyword,
            input: requestInput,
            diagnostic: diagnostic
        };

        if (!isAdminAjaxUrl(url)) {
            return result;
        }

        if (!themeAction || !actionDetected) {
            updateIntegrationDiagnostics(diagnostic);
            return result;
        }

        requestState.lastQuery = keyword;

        debugLog("Theme AJAX request detected", {
            action: action || "",
            transport: options.transport || "",
            method: method,
            bodyType: bodyInfo.bodyType,
            keywordField: getKeywordField(),
            inputFound: !!requestInput,
            resultContainerFound: resultContainerFound
        });

        if (bodyInfo.bodyType === "formdata" || bodyInfo.bodyType === "urlsearchparams") {
            var liveEntries = bodyInfo.bodyType === "formdata" ? Array.from(options.body.entries()) : entriesFromParams(options.body);
            var appendResult = appendParams(options.body, requestState, liveEntries.concat(urlEntries), { skipAction: true, keyword: keyword });
            result.bodyChanged = true;
            result.modified = true;
            diagnostic.nonceAttached = diagnostic.nonceAttached || appendResult.nonceAttached;
        } else if (bodyInfo.bodyType === "urlencoded-string") {
            var params = new URLSearchParams(options.body);
            appendResult = appendParams(params, requestState, entriesFromParams(params), { skipAction: true, keyword: keyword });
            result.body = params.toString();
            result.bodyChanged = true;
            result.modified = true;
            diagnostic.nonceAttached = diagnostic.nonceAttached || appendResult.nonceAttached;
        } else if (bodyInfo.bodyType === "json" && bodyInfo.supported) {
            appendResult = appendObjectFields(bodyInfo.parsedObject, requestState, bodyInfo.entries.concat(urlEntries), keyword, {
                skipActiveTags: !!(options.allowUrlMutation && parsedUrl)
            });
            result.body = JSON.stringify(bodyInfo.parsedObject);
            result.bodyChanged = true;
            result.modified = true;
            diagnostic.nonceAttached = diagnostic.nonceAttached || appendResult.nonceAttached;

            if (options.allowUrlMutation && parsedUrl) {
                appendParams(parsedUrl.searchParams, requestState, bodyInfo.entries.concat(urlEntries), { keyword: keyword });
                result.url = parsedUrl.toString();
                result.urlChanged = true;
            }
        } else if (options.allowUrlMutation && parsedUrl) {
            appendResult = appendParams(parsedUrl.searchParams, requestState, urlEntries, { keyword: keyword });
            result.url = parsedUrl.toString();
            result.urlChanged = true;
            result.modified = true;
            diagnostic.nonceAttached = diagnostic.nonceAttached || appendResult.nonceAttached;
        }

        diagnostic.modified = result.modified;
        updateIntegrationDiagnostics(diagnostic);

        return result;
    }

    /**
     * Intercept existing AJAX search requests (from theme search scripts)
     * to append active tags.
     */
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url) {
        var args = Array.prototype.slice.call(arguments);
        var prepared = prepareIntegratedRequest({
            transport: "xhr",
            method: method,
            url: url,
            body: null,
            allowUrlMutation: true
        });

        this._sschpRequestMethod = String(method || "GET").toUpperCase();
        this._sschpRequestUrl = prepared.url;

        if (prepared.urlChanged) {
            args[1] = prepared.url;
        }

        return origOpen.apply(this, args);
    };

    XMLHttpRequest.prototype.send = function (data) {
        if (this._sscManagedRequest) {
            return origSend.apply(this, arguments);
        }

        var method = this._sschpRequestMethod || "GET";
        var prepared = prepareIntegratedRequest({
            transport: "xhr",
            method: method,
            url: this._sschpRequestUrl || getConfiguredAjaxUrl(),
            body: data,
            allowUrlMutation: false
        });
        var args = Array.prototype.slice.call(arguments);

        if (prepared.bodyChanged) {
            args[0] = prepared.body;
        }

        if (prepared.modified) {
            this.addEventListener("loadend", function () {
                if (this.status !== 200) {
                    return;
                }

                trackIntegrationResponse(this, prepared.keyword, prepared.input);
            }, { once: true });
        }

        return origSend.apply(this, args);
    };

    if (window.fetch) {
        var origFetch = window.fetch;

        window.fetch = function (input, init) {
            var requestUrl = "";
            var requestMethod = "GET";
            var requestBody = null;
            var nextInput = input;
            var nextInit = init ? Object.assign({}, init) : {};
            var isRequestInput = typeof Request !== "undefined" && input instanceof Request;

            if (typeof input === "string" || input instanceof URL) {
                requestUrl = String(input);
            } else if (isRequestInput || (input && input.url)) {
                requestUrl = input.url;
                requestMethod = input.method || requestMethod;
            }

            if (isRequestInput) {
                if (!nextInit.method) {
                    nextInit.method = input.method;
                }
                if (!nextInit.headers) {
                    nextInit.headers = input.headers;
                }
                if (!nextInit.credentials) {
                    nextInit.credentials = input.credentials;
                }
                if (!nextInit.mode) {
                    nextInit.mode = input.mode;
                }
                if (!nextInit.cache) {
                    nextInit.cache = input.cache;
                }
                if (!nextInit.redirect) {
                    nextInit.redirect = input.redirect;
                }
                if (!nextInit.referrer) {
                    nextInit.referrer = input.referrer;
                }
                if (!nextInit.referrerPolicy) {
                    nextInit.referrerPolicy = input.referrerPolicy;
                }
                if (!nextInit.integrity) {
                    nextInit.integrity = input.integrity;
                }
                if (!nextInit.keepalive) {
                    nextInit.keepalive = input.keepalive;
                }
                if (!nextInit.signal) {
                    nextInit.signal = input.signal;
                }
            }

            if (nextInit.method) {
                requestMethod = nextInit.method;
            }

            if (Object.prototype.hasOwnProperty.call(nextInit, "body")) {
                requestBody = nextInit.body;
            }

            var prepared = prepareIntegratedRequest({
                transport: "fetch",
                method: requestMethod,
                url: requestUrl,
                body: requestBody,
                allowUrlMutation: true
            });

            if (!prepared.modified) {
                return origFetch.apply(this, arguments);
            }

            if (
                prepared.urlChanged &&
                isRequestInput &&
                !Object.prototype.hasOwnProperty.call(nextInit, "body") &&
                requestMethod !== "GET" &&
                requestMethod !== "HEAD"
            ) {
                return origFetch.apply(this, arguments);
            }

            if (prepared.urlChanged) {
                nextInput = prepared.url;
            }

            if (prepared.bodyChanged) {
                nextInit.body = prepared.body;
            }

            return origFetch.call(this, nextInput, nextInit).then(function (response) {
                trackIntegrationResponse(response, prepared.keyword, prepared.input);
                return response;
            });
        };
    }

    document.addEventListener("click", function (e) {
        var resultLink = safeClosest(e.target, "[data-sschp-result][data-sschp-post-id]");
        if (!resultLink) {
            return;
        }

        trackResultClick(resultLink);
    }, true);

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
        getState(input || control).activeTags = [];
        closeSearchUi(input, results);
        window.setTimeout(function () {
            closeSearchUi(input, results);
        }, 0);
    }, true);

    document.addEventListener("reset", function (e) {
        if (!safeMatches(e.target, ".ssc-search__form, .js-ssc-search, .js-search")) {
            return;
        }

        var input = findInput(e.target);
        var results = findResults(e.target);
        getState(input || e.target).activeTags = [];
        closeSearchUi(input, results);
    }, true);

    /**
     * Handle live search input changes from typing, paste, clear buttons,
     * mobile keyboards, speech input, autofill, and script-dispatched input.
     */
    function handleSearchInputChange(input) {
        var results = findResults(input);
        var isGuaranteedSearch = !!safeClosest(input, ".ssc-search");

        if (!isGuaranteedSearch && !themeEnhancementEnabled()) {
            updateIntegrationDiagnostics({
                method: "DOM",
                transport: "input",
                bodyType: "none",
                actionDetected: false,
                nonceAttached: false,
                keywordFound: !!input.value,
                resultContainerFound: !!results,
                modified: false
            });
            return;
        }

        if (!isGuaranteedSearch && !results) {
            updateIntegrationDiagnostics({
                method: "DOM",
                transport: "input",
                bodyType: "none",
                actionDetected: false,
                nonceAttached: false,
                keywordFound: !!input.value,
                resultContainerFound: false,
                modified: false
            });
            return;
        }

        if (input.value.length >= (config.minChars || 2)) {
            debouncedSearch(input, results);
        } else if (results) {
            getState(input).activeTags = [];
            closeSearchUi(input, results);
        }
    }

    document.addEventListener("input", function (e) {
        var input = e.target;
        if (!safeMatches(input, getInputSelector())) return;

        handleSearchInputChange(input);
    });

    document.addEventListener("keydown", function (e) {
        var input = e.target;
        if (!safeMatches(input, getInputSelector())) return;

        if (e.key === "Escape" || e.key === "Esc") {
            closeSearchUi(input, findResults(input));
            return;
        }

        if (e.key === "Enter" && !safeClosest(input, "form")) {
            doSearch(input, findResults(input), { trigger: "submit" });
        }
    });

    /**
     * Prevent built-in form submission (use AJAX instead).
     */
    document.addEventListener("submit", function (e) {
        if (safeMatches(e.target, ".ssc-search__form, .js-ssc-search")) {
            e.preventDefault();
            var input = safeQuerySelector(e.target, getInputSelector()) || safeQuerySelector(e.target, 'input[type="search"]');
            var results = findResults(input);
            if (input) {
                doSearch(input, results, { trigger: "submit" });
            }
        }
    });

    function handleReady() {
        debugLog("Frontend selector scan", {
            inputSelector: getInputSelector(),
            resultSelector: getResultsSelector(),
            inputCount: safeQuerySelectorAll(document, getInputSelector()).length,
            resultContainerCount: safeQuerySelectorAll(document, getResultsSelector()).length,
            action: getSearchAction(),
            themeAction: getThemeAction(),
            keywordField: getKeywordField()
        });

        var archiveChipLabels = Array.prototype.map.call(
            safeQuerySelectorAll(document, ".ssc-loop-filters .ssc-chip[data-tag]"),
            function (chipNode) {
                return chipNode.getAttribute("data-tag") || "";
            }
        ).filter(Boolean);

        trackChipImpressions(archiveChipLabels, {
            searchQuery: getCurrentSearchQuery(),
            resultCount: getArchiveResultCount()
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", handleReady);
    } else {
        handleReady();
    }
})();
