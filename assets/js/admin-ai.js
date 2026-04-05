/**
 * Smart Search Chips - Admin AI prompt builder
 *
 * @package SmartSearchChips
 * @version 1.0.0
 */

(function () {
    "use strict";

    var config = window.sscAiAdmin || {};
    var restConfirmed = false;
    var browserState = {
        items: [],
        filters: [],
        search: "",
        activeFilter: "all",
        page: 1,
        totalItems: 0,
        totalPages: 1,
        hasMore: false,
        loading: false
    };
    var selectedItems = {};
    var searchDebounce = null;

    function $(id) {
        return document.getElementById(id);
    }

    function checkedValues(name) {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="' + name + '"]:checked')).map(function (el) {
            return el.value;
        });
    }

    function selectedPostIds() {
        return Object.keys(selectedItems);
    }

    function selectedCount() {
        return selectedPostIds().length;
    }

    function getSelectedItem(id) {
        return selectedItems[String(id)] || null;
    }

    function currentFormData(action) {
        var data = new FormData();
        data.append("action", action);
        data.append("nonce", config.nonce || "");
        data.append("custom_read_fields", $("ssc-ai-custom-read-fields") ? $("ssc-ai-custom-read-fields").value : "");
        data.append("target_field", document.querySelector('input[name="ssc_ai_target_field"]:checked') ? document.querySelector('input[name="ssc_ai_target_field"]:checked').value : "built_in");
        data.append("max_tags", $("ssc-ai-max-tags") ? $("ssc-ai-max-tags").value : "8");
        data.append("language", $("ssc-ai-language") ? $("ssc-ai-language").value : "");
        data.append("tag_style", $("ssc-ai-tag-style") ? $("ssc-ai-tag-style").value : "technical");
        data.append("custom_instruction", $("ssc-ai-custom-instruction") ? $("ssc-ai-custom-instruction").value : "");

        checkedValues("ssc_ai_source_fields[]").forEach(function (field) {
            data.append("source_fields[]", field);
        });

        selectedPostIds().forEach(function (postId) {
            data.append("post_ids[]", postId);
        });

        return data;
    }

    function currentBrowserData(page) {
        var data = new FormData();
        data.append("action", "ssc_ai_browse_items");
        data.append("nonce", config.nonce || "");
        data.append("search", browserState.search);
        data.append("post_type", browserState.activeFilter);
        data.append("page", String(page || 1));
        return data;
    }

    function setStatus(targetId, text, isError) {
        var target = $(targetId);
        if (!target) return;

        target.textContent = text || "";
        target.style.color = isError ? "#b32d2e" : "#1d6f42";
    }

    function setBusy(button, busy, busyLabel) {
        if (!button) return;
        if (busy) {
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.textContent = busyLabel || (config.strings && config.strings.working ? config.strings.working : "Working...");
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    }

    function resetRestConfirmation() {
        restConfirmed = false;
        setStatus("ssc-ai-rest-status", "", false);
        setStatus("ssc-ai-build-status", "", false);
    }

    function renderRestSummary(summary) {
        var target = $("ssc-ai-rest-output");
        if (!target) return;

        var html = [];
        html.push("<p><strong>Selected items:</strong> " + escapeHtml(String(summary.selectedCount)) + "</p>");
        html.push("<p><strong>Source fields:</strong> " + escapeHtml((summary.sourceLabels || []).join(", ")) + "</p>");
        html.push("<p><strong>Target field:</strong> " + escapeHtml(summary.targetFieldLabel) + "</p>");
        html.push("<p><strong>Write format:</strong> " + escapeHtml(summary.writeFormat) + "</p>");
        html.push("<p><strong>Language:</strong> " + escapeHtml(summary.languageMode) + "</p>");
        html.push("<p><strong>Tag style:</strong> " + escapeHtml(summary.tagStyleLabel) + "</p>");
        html.push("<p><strong>REST auth:</strong> " + escapeHtml(summary.restAuth) + "</p>");

        if (summary.customReadFields && summary.customReadFields.length) {
            html.push("<p><strong>Extra custom fields:</strong> " + escapeHtml(summary.customReadFields.join(", ")) + "</p>");
        }

        html.push("<p><strong>Routes:</strong></p><ul>");
        (summary.routes || []).forEach(function (route) {
            html.push("<li>" + escapeHtml(route.title + " [" + route.postType + "] -> " + route.route) + "</li>");
        });
        html.push("</ul>");

        if (summary.customInstruction) {
            html.push("<p><strong>Custom instruction:</strong> " + escapeHtml(summary.customInstruction) + "</p>");
        }

        target.innerHTML = html.join("");
    }

    function renderPrompt(prompt) {
        var output = $("ssc-ai-prompt-output");
        if (!output) return;
        output.value = prompt || "";
    }

    function renderError(targetId, message) {
        var target = $(targetId);
        if (!target) return;
        target.innerHTML = "<p style=\"color:#b32d2e;\">" + escapeHtml(message) + "</p>";
    }

    function request(action, button, onSuccess, statusTarget) {
        if (!selectedPostIds().length) {
            setStatus(statusTarget, config.strings && config.strings.selectItems ? config.strings.selectItems : "Select items first.", true);
            renderError("ssc-ai-rest-output", config.strings && config.strings.selectItems ? config.strings.selectItems : "Select items first.");
            return;
        }

        setBusy(button, true);
        if (statusTarget) {
            setStatus(statusTarget, "", false);
        }

        fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: currentFormData(action)
        }).then(function (response) {
            return response.json();
        }).then(function (json) {
            if (!json || !json.success) {
                throw new Error(json && json.data && json.data.message ? json.data.message : "Request failed.");
            }
            onSuccess(json.data);
        }).catch(function (error) {
            if (statusTarget) {
                setStatus(statusTarget, error.message, true);
            }
            renderError("ssc-ai-rest-output", error.message);
        }).finally(function () {
            setBusy(button, false);
        });
    }

    function loadBrowser(reset) {
        var moreButton = $("ssc-ai-browser-more");
        var summary = $("ssc-ai-browser-summary");
        var page = reset ? 1 : browserState.page + 1;

        if (browserState.loading) return;
        browserState.loading = true;

        if (summary && reset) {
            summary.textContent = config.strings && config.strings.loadingItems ? config.strings.loadingItems : "Loading items...";
        }

        setBusy(moreButton, !reset, config.strings && config.strings.loadingMore ? config.strings.loadingMore : "Loading more...");

        fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: currentBrowserData(page)
        }).then(function (response) {
            return response.json();
        }).then(function (json) {
            if (!json || !json.success) {
                throw new Error(json && json.data && json.data.message ? json.data.message : "Could not load items.");
            }

            browserState.filters = json.data.filters || [];
            browserState.page = json.data.pager ? json.data.pager.page : page;
            browserState.totalItems = json.data.pager ? json.data.pager.totalItems : 0;
            browserState.totalPages = json.data.pager ? json.data.pager.totalPages : 1;
            browserState.hasMore = json.data.pager ? !!json.data.pager.hasMore : false;
            browserState.activeFilter = json.data.pager && json.data.pager.activeFilter ? json.data.pager.activeFilter : browserState.activeFilter;

            if (reset) {
                browserState.items = json.data.items || [];
            } else {
                browserState.items = browserState.items.concat(json.data.items || []);
            }

            renderBrowser();
        }).catch(function (error) {
            if (summary) {
                summary.textContent = error.message || (config.strings && config.strings.loadItemsFailed ? config.strings.loadItemsFailed : "Could not load items.");
            }
            renderBrowserError(error.message || (config.strings && config.strings.loadItemsFailed ? config.strings.loadItemsFailed : "Could not load items."));
        }).finally(function () {
            browserState.loading = false;
            setBusy(moreButton, false);
        });
    }

    function renderBrowser() {
        var filterWrap = $("ssc-ai-browser-filters");
        var results = $("ssc-ai-browser-results");
        var summary = $("ssc-ai-browser-summary");
        var moreButton = $("ssc-ai-browser-more");

        if (filterWrap) {
            filterWrap.innerHTML = (browserState.filters || []).map(function (filter) {
                var activeClass = filter.value === browserState.activeFilter ? " is-active" : "";
                return '<button type="button" class="ssc-ai-filter' + activeClass + '" data-filter="' + escapeHtml(filter.value) + '">' +
                    '<span>' + escapeHtml(filter.label) + '</span> ' +
                    '<span class="ssc-ai-filter__count">' + escapeHtml(String(filter.count)) + '</span>' +
                "</button>";
            }).join("");
        }

        if (summary) {
            if (browserState.totalItems) {
                summary.textContent = formatString(config.strings && config.strings.browseSummary ? config.strings.browseSummary : "%1$d matching items · page %2$d of %3$d", [
                    browserState.totalItems,
                    browserState.page || 1,
                    browserState.totalPages || 1
                ]);
            } else {
                summary.textContent = config.strings && config.strings.browseEmpty ? config.strings.browseEmpty : "No items matched the current search and filter.";
            }
        }

        if (results) {
            if (!browserState.items.length) {
                results.innerHTML = '<div class="ssc-ai-browser-empty">' + escapeHtml(config.strings && config.strings.browseEmpty ? config.strings.browseEmpty : "No items matched the current search and filter.") + "</div>";
            } else {
                results.innerHTML = browserState.items.map(renderBrowserCard).join("");
            }
        }

        if (moreButton) {
            moreButton.hidden = !browserState.hasMore;
        }
    }

    function renderBrowserError(message) {
        var results = $("ssc-ai-browser-results");
        if (!results) return;
        results.innerHTML = '<div class="ssc-ai-browser-empty" style="color:#b32d2e;">' + escapeHtml(message) + "</div>";
    }

    function renderBrowserCard(item) {
        var selected = !!getSelectedItem(item.id);
        var buttonLabel = selected
            ? (config.strings && config.strings.removeItem ? config.strings.removeItem : "Remove")
            : (config.strings && config.strings.addItem ? config.strings.addItem : "Add to batch");
        var buttonClass = selected ? "button-link-delete" : "button button-secondary";
        var meta = [
            badge(item.postTypeLabel, "active"),
            badge(item.statusLabel, "soft"),
            badge(item.dateLabel, "")
        ].join("");
        var terms = (item.terms || []).map(function (term) {
            return badge(term, "");
        }).join("");

        return '<article class="ssc-ai-browser-card' + (selected ? " is-selected" : "") + '" data-id="' + escapeHtml(String(item.id)) + '">' +
            '<div>' +
                '<h3 class="ssc-ai-browser-card__title">' + escapeHtml(item.title) + "</h3>" +
                '<div class="ssc-ai-browser-card__meta">' + meta + "</div>" +
                (terms ? '<div class="ssc-ai-browser-card__terms">' + terms + "</div>" : "") +
            "</div>" +
            '<div><button type="button" class="' + buttonClass + '" data-item-toggle="' + escapeHtml(String(item.id)) + '">' + escapeHtml(buttonLabel) + "</button></div>" +
        "</article>";
    }

    function renderSelection() {
        var summary = $("ssc-ai-selection-summary");
        var wrap = $("ssc-ai-selected-items");
        var ids = selectedPostIds();

        if (summary) {
            summary.textContent = buildSelectionSummary();
        }

        if (!wrap) return;

        if (!ids.length) {
            wrap.innerHTML = '<div class="ssc-ai-browser-empty">' + escapeHtml(config.strings && config.strings.selectionEmpty ? config.strings.selectionEmpty : "No items selected yet.") + "</div>";
            return;
        }

        var groups = {};
        ids.map(function (id) {
            return selectedItems[id];
        }).sort(function (a, b) {
            if (a.postTypeLabel !== b.postTypeLabel) {
                return a.postTypeLabel.localeCompare(b.postTypeLabel);
            }
            return a.title.localeCompare(b.title);
        }).forEach(function (item) {
            var groupKey = item.postTypeLabel;
            if (!groups[groupKey]) {
                groups[groupKey] = [];
            }
            groups[groupKey].push(item);
        });

        wrap.innerHTML = Object.keys(groups).map(function (groupKey) {
            return '<section class="ssc-ai-selection-group">' +
                '<div class="ssc-ai-selection-group__title">' + escapeHtml(groupKey) + " (" + escapeHtml(String(groups[groupKey].length)) + ")</div>" +
                groups[groupKey].map(renderSelectedItem).join("") +
            "</section>";
        }).join("");
    }

    function renderSelectedItem(item) {
        var terms = (item.terms || []).slice(0, 3).map(function (term) {
            return badge(term, "");
        }).join("");

        return '<article class="ssc-ai-selection-item">' +
            '<div>' +
                '<h4 class="ssc-ai-selection-item__title">' + escapeHtml(item.title) + "</h4>" +
                '<div class="ssc-ai-selection-item__meta">' +
                    badge(item.postTypeLabel, "active") +
                    badge(item.statusLabel, "soft") +
                    badge(item.dateLabel, "") +
                "</div>" +
                (terms ? '<div class="ssc-ai-browser-card__terms" style="margin-top:8px;">' + terms + "</div>" : "") +
            "</div>" +
            '<div><button type="button" class="button-link-delete" data-selected-remove="' + escapeHtml(String(item.id)) + '">' +
                escapeHtml(config.strings && config.strings.removeItem ? config.strings.removeItem : "Remove") +
            "</button></div>" +
        "</article>";
    }

    function buildSelectionSummary() {
        var ids = selectedPostIds();
        if (!ids.length) {
            return config.strings && config.strings.selectionEmptyShort ? config.strings.selectionEmptyShort : "No items selected yet.";
        }

        var countText = formatString(config.strings && config.strings.selectionCount ? config.strings.selectionCount : "%d items selected", [ids.length]);
        var countsByType = {};

        ids.forEach(function (id) {
            var item = selectedItems[id];
            var key = item ? item.postTypeLabel : "Items";
            countsByType[key] = (countsByType[key] || 0) + 1;
        });

        return countText + " · " + Object.keys(countsByType).sort().map(function (key) {
            return countsByType[key] + " " + key;
        }).join(" · ");
    }

    function badge(label, modifier) {
        return '<span class="ssc-ai-badge' + (modifier ? " ssc-ai-badge--" + modifier : "") + '">' + escapeHtml(label) + "</span>";
    }

    function addSelectedItem(id) {
        var item = findBrowserItem(id);
        if (!item) return;
        selectedItems[String(id)] = item;
        resetRestConfirmation();
        renderSelection();
        renderBrowser();
    }

    function removeSelectedItem(id) {
        delete selectedItems[String(id)];
        resetRestConfirmation();
        renderSelection();
        renderBrowser();
    }

    function clearSelection() {
        selectedItems = {};
        resetRestConfirmation();
        renderSelection();
        renderBrowser();
    }

    function findBrowserItem(id) {
        var needle = String(id);
        return browserState.items.filter(function (item) {
            return String(item.id) === needle;
        })[0] || null;
    }

    function copyPrompt() {
        var output = $("ssc-ai-prompt-output");
        if (!output) return;

        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            setStatus("ssc-ai-build-status", config.strings && config.strings.copyFailed ? config.strings.copyFailed : "Copy failed.", true);
            return;
        }

        navigator.clipboard.writeText(output.value).then(function () {
            setStatus("ssc-ai-build-status", config.strings && config.strings.copied ? config.strings.copied : "Copied.", false);
        }).catch(function () {
            setStatus("ssc-ai-build-status", config.strings && config.strings.copyFailed ? config.strings.copyFailed : "Copy failed.", true);
        });
    }

    function formatString(template, values) {
        var text = String(template || "");
        (values || []).forEach(function (value, index) {
            var numbered = new RegExp("%" + String(index + 1) + "\\$d", "g");
            text = text.replace(numbered, String(value));
        });
        text = text.replace("%d", String(values && values.length ? values[0] : ""));
        return text;
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    document.addEventListener("DOMContentLoaded", function () {
        var allowRestButton = $("ssc-ai-allow-rest");
        var buildPromptButton = $("ssc-ai-build-prompt");
        var copyPromptButton = $("ssc-ai-copy-prompt");
        var browserMoreButton = $("ssc-ai-browser-more");
        var browserRefreshButton = $("ssc-ai-browser-refresh");
        var browserSearch = $("ssc-ai-browser-search");
        var browserFilters = $("ssc-ai-browser-filters");
        var browserResults = $("ssc-ai-browser-results");
        var selectedWrap = $("ssc-ai-selected-items");
        var clearSelectionButton = $("ssc-ai-clear-selection");
        var resetSelectors = [
            "#ssc-ai-custom-read-fields",
            "#ssc-ai-max-tags",
            "#ssc-ai-language",
            "#ssc-ai-tag-style",
            "#ssc-ai-custom-instruction",
            'input[name="ssc_ai_target_field"]',
            'input[name="ssc_ai_source_fields[]"]'
        ];

        resetSelectors.forEach(function (selector) {
            Array.prototype.slice.call(document.querySelectorAll(selector)).forEach(function (el) {
                el.addEventListener("change", resetRestConfirmation);
                el.addEventListener("input", resetRestConfirmation);
            });
        });

        if (browserSearch) {
            browserSearch.addEventListener("input", function () {
                browserState.search = browserSearch.value;
                window.clearTimeout(searchDebounce);
                searchDebounce = window.setTimeout(function () {
                    loadBrowser(true);
                }, 250);
            });
        }

        if (browserRefreshButton) {
            browserRefreshButton.addEventListener("click", function () {
                loadBrowser(true);
            });
        }

        if (browserFilters) {
            browserFilters.addEventListener("click", function (event) {
                var trigger = event.target.closest("[data-filter]");
                if (!trigger) return;

                browserState.activeFilter = trigger.getAttribute("data-filter") || "all";
                loadBrowser(true);
            });
        }

        if (browserResults) {
            browserResults.addEventListener("click", function (event) {
                var trigger = event.target.closest("[data-item-toggle]");
                if (!trigger) return;

                var id = trigger.getAttribute("data-item-toggle");
                if (!id) return;

                if (getSelectedItem(id)) {
                    removeSelectedItem(id);
                } else {
                    addSelectedItem(id);
                }
            });
        }

        if (selectedWrap) {
            selectedWrap.addEventListener("click", function (event) {
                var trigger = event.target.closest("[data-selected-remove]");
                if (!trigger) return;

                var id = trigger.getAttribute("data-selected-remove");
                if (!id) return;

                removeSelectedItem(id);
            });
        }

        if (clearSelectionButton) {
            clearSelectionButton.addEventListener("click", clearSelection);
        }

        if (browserMoreButton) {
            browserMoreButton.addEventListener("click", function () {
                loadBrowser(false);
            });
        }

        if (allowRestButton) {
            allowRestButton.addEventListener("click", function () {
                request("ssc_ai_check_rest", allowRestButton, function (data) {
                    restConfirmed = true;
                    renderRestSummary(data);
                    setStatus("ssc-ai-rest-status", config.strings && config.strings.restReady ? config.strings.restReady : "REST ready.", false);
                }, "ssc-ai-rest-status");
            });
        }

        if (buildPromptButton) {
            buildPromptButton.addEventListener("click", function () {
                if (!restConfirmed) {
                    setStatus("ssc-ai-build-status", config.strings && config.strings.confirmRest ? config.strings.confirmRest : "Confirm REST first.", true);
                    return;
                }

                request("ssc_ai_build_prompt", buildPromptButton, function (data) {
                    renderRestSummary(data.summary);
                    renderPrompt(data.prompt);
                    setStatus("ssc-ai-build-status", config.strings && config.strings.promptReady ? config.strings.promptReady : "Prompt ready.", false);
                }, "ssc-ai-build-status");
            });
        }

        if (copyPromptButton) {
            copyPromptButton.addEventListener("click", copyPrompt);
        }

        renderSelection();
        loadBrowser(true);
    });
})();
