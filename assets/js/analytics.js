/**
 * Smart Search Chips - frontend analytics transport.
 *
 * @package SmartSearchChips
 */

(function () {
    "use strict";

    var VISITOR_STORAGE_KEY = "sschp_visitor_id";

    function getConfig() {
        return window.sschpConfig || {};
    }

    function analyticsEnabled() {
        var config = getConfig();
        return !!(config.analytics && config.analytics.enabled && config.analytics.nonce);
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
            // Ignore storage failures and continue with an in-memory ID for this page.
        }

        return generated;
    }

    function getPageUrl() {
        return window.location.origin + window.location.pathname;
    }

    function sendWithFetch(url, data) {
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

    function trackEvent(payload) {
        var config;
        var url;
        var data;

        try {
            if (!analyticsEnabled()) {
                return;
            }

            config = getConfig();
            url = config.ajaxUrl || "/wp-admin/admin-ajax.php";
            data = new FormData();
            data.append("action", "sschp_track_event");
            data.append("nonce", config.analytics.nonce);
            data.append("sschp_lang", config.lang || "");
            data.append("visitor_id", getVisitorId());
            data.append("event_type", payload.eventType || "");
            data.append("search_query", payload.searchQuery || "");
            data.append("chip_label", payload.chipLabel || "");
            data.append("post_type", payload.postType || config.postType || "");
            data.append("product_category", payload.productCategory || config.productCategory || "");
            data.append("page_url", getPageUrl());
            data.append("result_count", String(typeof payload.resultCount === "number" ? payload.resultCount : -1));
            data.append("active_tag_count", String(typeof payload.activeTagCount === "number" ? payload.activeTagCount : 0));
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

            sendWithFetch(url, data);
        } catch (error) {
            // Analytics should never interrupt search UX.
        }
    }

    window.sschpAnalytics = window.sschpAnalytics || {};
    window.sschpAnalytics.trackEvent = trackEvent;
})();
