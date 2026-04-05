/**
 * Smart Search Chips - Admin AI prompt builder
 *
 * @package SmartSearchChips
 * @version 1.0.0
 */

(function () {
    "use strict";

    var config = window.sscAiAdmin || {};
    var browserState = {
        collections: [],
        search: "",
        loading: false,
        itemCache: {}
    };
    var selectedItems = {};
    var searchDebounce = null;

    function $(id) {
        return document.getElementById(id);
    }

    function selectedPostIds() {
        return Object.keys(selectedItems);
    }

    function getSelectedItem(id) {
        return selectedItems[String(id)] || null;
    }

    function getTermKey(taxonomy, termId) {
        return String(taxonomy) + ":" + String(termId);
    }

    function getItemCache(termKey) {
        if (!browserState.itemCache[termKey]) {
            browserState.itemCache[termKey] = {
                loading: false,
                loaded: false,
                expanded: false,
                items: [],
                termName: ""
            };
        }

        return browserState.itemCache[termKey];
    }

    function currentFormData(action) {
        var data = new FormData();
        data.append("action", action);
        data.append("nonce", config.nonce || "");
        data.append("custom_read_fields", $("ssc-ai-custom-read-fields") ? $("ssc-ai-custom-read-fields").value : "");
        data.append("target_field", document.querySelector('input[name="ssc_ai_target_field"]:checked') ? document.querySelector('input[name="ssc_ai_target_field"]:checked').value : "built_in");
        data.append("rest_enabled", $("ssc-ai-rest-enabled") && $("ssc-ai-rest-enabled").checked ? "1" : "0");
        data.append("max_tags", $("ssc-ai-max-tags") ? $("ssc-ai-max-tags").value : "8");
        data.append("language", $("ssc-ai-language") ? $("ssc-ai-language").value : "");
        data.append("custom_instruction", $("ssc-ai-custom-instruction") ? $("ssc-ai-custom-instruction").value : "");

        selectedPostIds().forEach(function (postId) {
            data.append("post_ids[]", postId);
        });

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
        setStatus("ssc-ai-build-status", "", false);
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

    function loadCollections() {
        var summary = $("ssc-ai-browser-summary");
        browserState.loading = true;

        if (summary) {
            summary.textContent = config.strings && config.strings.loadingTerms ? config.strings.loadingTerms : "Loading categories...";
        }

        var data = new FormData();
        data.append("action", "ssc_ai_browse_terms");
        data.append("nonce", config.nonce || "");

        fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (json) {
            if (!json || !json.success) {
                throw new Error(json && json.data && json.data.message ? json.data.message : "Could not load categories.");
            }

            browserState.collections = json.data.collections || [];
            renderCollections();
        }).catch(function (error) {
            renderCollectionsError(error.message || (config.strings && config.strings.loadTermsFailed ? config.strings.loadTermsFailed : "Could not load categories."));
        }).finally(function () {
            browserState.loading = false;
        });
    }

    function loadTermItems(taxonomy, termId, options) {
        var termKey = getTermKey(taxonomy, termId);
        var cache = getItemCache(termKey);
        options = options || {};

        if (cache.loading) {
            return;
        }

        if (cache.loaded) {
            if (options.expand !== false) {
                cache.expanded = true;
            }
            if (options.selectAll) {
                addTermItems(termKey, cache.items);
            } else {
                renderCollections();
            }
            return;
        }

        cache.loading = true;
        cache.expanded = options.expand !== false;
        renderCollections();

        var data = new FormData();
        data.append("action", "ssc_ai_load_term_items");
        data.append("nonce", config.nonce || "");
        data.append("taxonomy", taxonomy);
        data.append("term_id", String(termId));

        fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (json) {
            if (!json || !json.success) {
                throw new Error(json && json.data && json.data.message ? json.data.message : "Could not load items for this category.");
            }

            cache.loading = false;
            cache.loaded = true;
            cache.expanded = options.expand !== false;
            cache.items = json.data.items || [];
            cache.termName = json.data.termName || "";

            if (options.selectAll) {
                addTermItems(termKey, cache.items);
            } else {
                renderCollections();
            }
        }).catch(function (error) {
            cache.loading = false;
            cache.loaded = false;
            cache.expanded = false;
            renderCollectionsError(error.message || (config.strings && config.strings.loadItemsFailed ? config.strings.loadItemsFailed : "Could not load items for this category."));
        });
    }

    function renderCollections() {
        var results = $("ssc-ai-browser-results");
        var summary = $("ssc-ai-browser-summary");
        var collections = getFilteredCollections(browserState.collections, browserState.search.toLowerCase());
        var visibleTerms = countVisibleTerms(collections);

        if (summary) {
            if (!collections.length || !visibleTerms) {
                summary.textContent = config.strings && config.strings.browseEmpty ? config.strings.browseEmpty : "No categories matched the current filter.";
            } else {
                summary.textContent = formatString(config.strings && config.strings.browseSummary ? config.strings.browseSummary : "%1$d taxonomies · %2$d visible categories", [
                    collections.length,
                    visibleTerms
                ]);
            }
        }

        if (!results) {
            return;
        }

        if (!collections.length || !visibleTerms) {
            results.innerHTML = '<div class="ssc-ai-browser-empty">' + escapeHtml(config.strings && config.strings.browseEmpty ? config.strings.browseEmpty : "No categories matched the current filter.") + "</div>";
            return;
        }

        results.innerHTML = collections.map(function (collection) {
            if (!collection.terms.length) {
                return "";
            }

            return '<section class="ssc-ai-taxonomy">' +
                '<div class="ssc-ai-taxonomy__header">' +
                    '<div><strong>' + escapeHtml(collection.label) + '</strong></div>' +
                    badge(String(countVisibleTerms([collection])) + " terms", "soft") +
                "</div>" +
                '<div class="ssc-ai-taxonomy__terms">' + renderTermNodes(collection.terms) + "</div>" +
            "</section>";
        }).join("");
    }

    function renderCollectionsError(message) {
        var results = $("ssc-ai-browser-results");
        var summary = $("ssc-ai-browser-summary");

        if (summary) {
            summary.textContent = message;
        }

        if (!results) return;
        results.innerHTML = '<div class="ssc-ai-browser-empty" style="color:#b32d2e;">' + escapeHtml(message) + "</div>";
    }

    function renderTermNodes(terms) {
        return (terms || []).map(function (term) {
            return renderTermNode(term);
        }).join("");
    }

    function renderTermNode(term) {
        var termKey = getTermKey(term.taxonomy, term.id);
        var cache = getItemCache(termKey);
        var isFullySelected = cache.loaded && cache.items.length > 0 && cache.items.every(function (item) {
            return isItemSelectedBySource(item.id, termKey);
        });
        var toggleLabel = cache.expanded
            ? (config.strings && config.strings.collapseGroup ? config.strings.collapseGroup : "Hide products")
            : (config.strings && config.strings.expandGroup ? config.strings.expandGroup : "Show products");
        var selectLabel = isFullySelected
            ? (config.strings && config.strings.removeGroup ? config.strings.removeGroup : "Remove category items")
            : (config.strings && config.strings.addGroup ? config.strings.addGroup : "Add category");

        return '<div class="ssc-ai-term' + (cache.expanded ? " is-expanded" : "") + '" data-term-key="' + escapeHtml(termKey) + '">' +
            '<div class="ssc-ai-term__row" style="padding-left:' + escapeHtml(String(14 + (term.depth * 18))) + 'px;">' +
                '<div class="ssc-ai-term__main">' +
                    '<button type="button" class="ssc-ai-term__toggle" data-term-toggle="' + escapeHtml(termKey) + '" title="' + escapeHtml(toggleLabel) + '">' + (cache.expanded ? "−" : "+") + '</button>' +
                    '<div class="ssc-ai-term__title">' +
                        '<div class="ssc-ai-term__name">' + escapeHtml(term.name) + '</div>' +
                        '<div class="ssc-ai-browser-card__meta">' +
                            badge(String(term.count) + " items", "active") +
                            badge(term.taxonomy, "") +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="ssc-ai-term__actions">' +
                    '<button type="button" class="button button-secondary" data-term-select="' + escapeHtml(termKey) + '">' + escapeHtml(selectLabel) + '</button>' +
                '</div>' +
            '</div>' +
            renderTermItems(termKey) +
            (term.children && term.children.length ? '<div class="ssc-ai-term__children">' + renderTermNodes(term.children) + '</div>' : '') +
        '</div>';
    }

    function renderTermItems(termKey) {
        var cache = getItemCache(termKey);
        var content = "";

        if (cache.expanded) {
            if (cache.loading) {
                content = '<div class="ssc-ai-browser-empty">' + escapeHtml(config.strings && config.strings.loadingItems ? config.strings.loadingItems : "Loading items for this category...") + "</div>";
            } else if (!cache.loaded || !cache.items.length) {
                content = '<div class="ssc-ai-browser-empty">' + escapeHtml(config.strings && config.strings.itemsEmpty ? config.strings.itemsEmpty : "No items were found in this category.") + "</div>";
            } else {
                content = '<div class="ssc-ai-term-items">' + cache.items.map(function (item) {
                    return renderTermItem(termKey, item);
                }).join("") + "</div>";
            }
        }

        return '<div class="ssc-ai-term__items">' + content + "</div>";
    }

    function renderTermItem(termKey, item) {
        var selected = !!getSelectedItem(item.id);
        var buttonLabel = selected
            ? (config.strings && config.strings.removeItem ? config.strings.removeItem : "Remove")
            : (config.strings && config.strings.addItem ? config.strings.addItem : "Add to batch");
        var terms = (item.terms || []).slice(0, 3).map(function (term) {
            return badge(term, "");
        }).join("");

        return '<article class="ssc-ai-term-item">' +
            '<div>' +
                '<h4 class="ssc-ai-term-item__title">' + escapeHtml(item.title) + "</h4>" +
                '<div class="ssc-ai-term-item__meta">' +
                    badge(item.postTypeLabel, "active") +
                    badge(item.statusLabel, "soft") +
                    badge(item.dateLabel, "") +
                '</div>' +
                (terms ? '<div class="ssc-ai-browser-card__terms" style="margin-top:8px;">' + terms + "</div>" : "") +
            '</div>' +
            '<div><button type="button" class="' + (selected ? "button-link-delete" : "button button-secondary") + '" data-item-toggle="' + escapeHtml(String(item.id)) + '" data-item-source="' + escapeHtml(termKey) + '">' + escapeHtml(buttonLabel) + '</button></div>' +
        '</article>';
    }

    function getFilteredCollections(collections, search) {
        if (!search) {
            return collections;
        }

        return (collections || []).map(function (collection) {
            return {
                taxonomy: collection.taxonomy,
                label: collection.label,
                singular: collection.singular,
                hierarchical: collection.hierarchical,
                terms: filterTerms(collection.terms || [], search)
            };
        }).filter(function (collection) {
            return collection.terms.length > 0;
        });
    }

    function filterTerms(terms, search) {
        return (terms || []).map(function (term) {
            var filteredChildren = filterTerms(term.children || [], search);
            var cache = getItemCache(getTermKey(term.taxonomy, term.id));
            var selfMatch = term.name.toLowerCase().indexOf(search) !== -1 || term.taxonomy.toLowerCase().indexOf(search) !== -1;
            var itemMatch = cache.loaded && cache.items.some(function (item) {
                return item.title.toLowerCase().indexOf(search) !== -1;
            });

            if (!selfMatch && !itemMatch && !filteredChildren.length) {
                return null;
            }

            return {
                id: term.id,
                taxonomy: term.taxonomy,
                name: term.name,
                slug: term.slug,
                count: term.count,
                depth: term.depth,
                children: filteredChildren
            };
        }).filter(Boolean);
    }

    function countVisibleTerms(collections) {
        return (collections || []).reduce(function (total, collection) {
            return total + countTerms(collection.terms || []);
        }, 0);
    }

    function countTerms(terms) {
        return (terms || []).reduce(function (total, term) {
            return total + 1 + countTerms(term.children || []);
        }, 0);
    }

    function addItemSelection(item, sourceKey) {
        var id = String(item.id);
        if (!selectedItems[id]) {
            selectedItems[id] = {
                id: item.id,
                title: item.title,
                postType: item.postType,
                postTypeLabel: item.postTypeLabel,
                status: item.status,
                statusLabel: item.statusLabel,
                dateLabel: item.dateLabel,
                terms: item.terms || [],
                sources: {}
            };
        }

        selectedItems[id].sources[sourceKey] = true;
    }

    function addManualItem(item) {
        addItemSelection(item, "manual");
        resetRestConfirmation();
        renderSelection();
        renderCollections();
    }

    function addTermItems(termKey, items) {
        (items || []).forEach(function (item) {
            addItemSelection(item, termKey);
        });
        resetRestConfirmation();
        renderSelection();
        renderCollections();
    }

    function removeSelectedItem(id) {
        delete selectedItems[String(id)];
        resetRestConfirmation();
        renderSelection();
        renderCollections();
    }

    function removeTermItems(termKey) {
        Object.keys(selectedItems).forEach(function (id) {
            var item = selectedItems[id];
            if (!item || !item.sources[termKey]) {
                return;
            }

            delete item.sources[termKey];

            if (!Object.keys(item.sources).length) {
                delete selectedItems[id];
            }
        });
        resetRestConfirmation();
        renderSelection();
        renderCollections();
    }

    function isItemSelectedBySource(id, sourceKey) {
        var item = getSelectedItem(id);
        return !!(item && item.sources && item.sources[sourceKey]);
    }

    function findItemInCaches(id) {
        var needle = String(id);
        var keys = Object.keys(browserState.itemCache);
        var found = null;

        keys.some(function (key) {
            var cache = browserState.itemCache[key];
            if (!cache || !cache.items) {
                return false;
            }

            found = cache.items.filter(function (item) {
                return String(item.id) === needle;
            })[0] || null;

            return !!found;
        });

        return found;
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
                '</div>' +
                (terms ? '<div class="ssc-ai-browser-card__terms" style="margin-top:8px;">' + terms + "</div>" : "") +
            '</div>' +
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

    function clearSelection() {
        selectedItems = {};
        resetRestConfirmation();
        renderSelection();
        renderCollections();
    }

    function renderRestSummary(summary) {
        var target = $("ssc-ai-rest-output");
        if (!target) return;

        var html = [];
        html.push("<p><strong>Selected items:</strong> " + escapeHtml(String(summary.selectedCount)) + "</p>");
        html.push("<p><strong>Target field:</strong> " + escapeHtml(summary.targetFieldLabel) + "</p>");
        html.push("<p><strong>Write format:</strong> " + escapeHtml(summary.writeFormat) + "</p>");
        html.push("<p><strong>Max tags:</strong> " + escapeHtml(summary.maxTagsLabel || String(summary.maxTags)) + "</p>");
        html.push("<p><strong>Language:</strong> " + escapeHtml(summary.languageMode) + "</p>");
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

    function badge(label, modifier) {
        return '<span class="ssc-ai-badge' + (modifier ? " ssc-ai-badge--" + modifier : "") + '">' + escapeHtml(label) + "</span>";
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
        var buildPromptButton = $("ssc-ai-build-prompt");
        var copyPromptButton = $("ssc-ai-copy-prompt");
        var copyPromptPanelButton = $("ssc-ai-copy-prompt-panel");
        var browserRefreshButton = $("ssc-ai-browser-refresh");
        var browserSearch = $("ssc-ai-browser-search");
        var browserResults = $("ssc-ai-browser-results");
        var selectedWrap = $("ssc-ai-selected-items");
        var clearSelectionButton = $("ssc-ai-clear-selection");
        var resetSelectors = [
            "#ssc-ai-custom-read-fields",
            "#ssc-ai-max-tags",
            "#ssc-ai-language",
            "#ssc-ai-rest-enabled",
            "#ssc-ai-custom-instruction",
            'input[name="ssc_ai_target_field"]'
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
                searchDebounce = window.setTimeout(renderCollections, 180);
            });
        }

        if (browserRefreshButton) {
            browserRefreshButton.addEventListener("click", function () {
                browserState.itemCache = {};
                loadCollections();
            });
        }

        if (browserResults) {
            browserResults.addEventListener("click", function (event) {
                var toggle = event.target.closest("[data-term-toggle]");
                var select = event.target.closest("[data-term-select]");
                var itemToggle = event.target.closest("[data-item-toggle]");

                if (toggle) {
                    var termKey = toggle.getAttribute("data-term-toggle");
                    if (!termKey) return;

                    var parts = termKey.split(":");
                    var cache = getItemCache(termKey);

                    if (cache.expanded) {
                        cache.expanded = false;
                        renderCollections();
                    } else {
                        loadTermItems(parts[0], parts[1], { expand: true });
                    }
                    return;
                }

                if (select) {
                    var selectKey = select.getAttribute("data-term-select");
                    if (!selectKey) return;

                    var selectParts = selectKey.split(":");
                    var selectCache = getItemCache(selectKey);
                    var fullySelected = selectCache.loaded && selectCache.items.length > 0 && selectCache.items.every(function (item) {
                        return isItemSelectedBySource(item.id, selectKey);
                    });

                    if (fullySelected) {
                        removeTermItems(selectKey);
                    } else {
                        loadTermItems(selectParts[0], selectParts[1], { expand: true, selectAll: true });
                    }
                    return;
                }

                if (itemToggle) {
                    var itemId = itemToggle.getAttribute("data-item-toggle");
                    if (!itemId) return;

                    if (getSelectedItem(itemId)) {
                        removeSelectedItem(itemId);
                    } else {
                        var item = findItemInCaches(itemId);
                        if (item) {
                            addManualItem(item);
                        }
                    }
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

        if (buildPromptButton) {
            buildPromptButton.addEventListener("click", function () {
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

        if (copyPromptPanelButton) {
            copyPromptPanelButton.addEventListener("click", copyPrompt);
        }

        renderSelection();
        loadCollections();
    });
})();
