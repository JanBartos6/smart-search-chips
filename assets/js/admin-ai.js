/**
 * Smart Search Chips - Admin AI tagging tools
 *
 * @package SmartSearchChips
 * @version 1.0.0
 */

(function () {
    "use strict";

    var config = window.sscAiAdmin || {};
    var models = config.models || {};
    var latestEstimate = null;

    function $(id) {
        return document.getElementById(id);
    }

    function selectedProvider() {
        var el = $("ssc-ai-provider");
        return el ? el.value : "openai";
    }

    function selectedProviderLabel() {
        var el = $("ssc-ai-provider");
        if (!el) return selectedProvider();
        var option = el.options[el.selectedIndex];
        return option ? option.textContent : selectedProvider();
    }

    function selectedModel() {
        var el = $("ssc-ai-model");
        return el ? el.value : "";
    }

    function selectedPostId() {
        var el = $("ssc-ai-post-id");
        return el ? el.value : "";
    }

    function selectedPostLabel() {
        var el = $("ssc-ai-post-id");
        if (!el) return "";
        var option = el.options[el.selectedIndex];
        return option ? option.textContent : "";
    }

    function selectedTargetField() {
        var checked = document.querySelector('input[name="ssc_ai_target_field"]:checked');
        if (!checked) return "built_in";
        return checked.value;
    }

    function selectedTargetLabel() {
        var checked = document.querySelector('input[name="ssc_ai_target_field"]:checked');
        if (!checked || !checked.parentNode) return selectedTargetField();
        return checked.parentNode.textContent.replace(/\s+/g, " ").trim();
    }

    function checkedValues(name) {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="' + name + '"]:checked')).map(function (el) {
            return el.value;
        });
    }

    function currentFormData(action) {
        var data = new FormData();
        data.append("action", action);
        data.append("nonce", config.nonce || "");
        data.append("provider", selectedProvider());
        data.append("model", selectedModel());
        data.append("custom_read_fields", $("ssc-ai-custom-read-fields") ? $("ssc-ai-custom-read-fields").value : "");
        data.append("target_field", selectedTargetField());
        data.append("max_tags", $("ssc-ai-max-tags") ? $("ssc-ai-max-tags").value : "8");
        data.append("language", $("ssc-ai-language") ? $("ssc-ai-language").value : "");
        data.append("custom_instruction", $("ssc-ai-custom-instruction") ? $("ssc-ai-custom-instruction").value : "");

        checkedValues("ssc_ai_source_fields[]").forEach(function (field) {
            data.append("source_fields[]", field);
        });

        if (selectedPostId()) {
            data.append("post_id", selectedPostId());
        }

        return data;
    }

    function setStatus(targetId, text, isError) {
        var target = $(targetId);
        if (!target) return;
        target.textContent = text || "";
        target.style.color = isError ? "#b32d2e" : "#1d6f42";
    }

    function setBusy(button, busy) {
        if (!button) return;
        if (busy) {
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.textContent = config.strings && config.strings.working ? config.strings.working : "Working...";
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    }

    function renderModelOptions(provider, modelList) {
        var modelSelect = $("ssc-ai-model");
        if (!modelSelect || !modelList || !modelList.length) return;

        var previous = modelSelect.value || modelSelect.getAttribute("data-selected") || "";
        modelSelect.innerHTML = "";

        modelList.forEach(function (model, index) {
            var option = document.createElement("option");
            option.value = model.id;
            option.textContent = model.label + (model.description ? " - " + model.description : "");
            if (previous === model.id || (!previous && index === 0)) {
                option.selected = true;
            }
            modelSelect.appendChild(option);
        });

        models[provider] = modelList;
    }

    function fetchModels() {
        var provider = selectedProvider();
        var modelData = new FormData();
        modelData.append("action", "ssc_ai_list_models");
        modelData.append("nonce", config.nonce || "");
        modelData.append("provider", provider);

        setStatus("ssc-ai-model-status", "", false);

        return fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: modelData
        }).then(function (response) {
            return response.json();
        }).then(function (json) {
            if (!json || !json.success || !json.data || !json.data.models) {
                throw new Error(json && json.data && json.data.message ? json.data.message : "Model discovery failed.");
            }

            renderModelOptions(provider, json.data.models);
            setStatus(
                "ssc-ai-model-status",
                json.data.fallback
                    ? (config.strings && config.strings.fallbackModels ? config.strings.fallbackModels : "Using fallback models.")
                    : (config.strings && config.strings.liveModels ? config.strings.liveModels : "Live models loaded."),
                json.data.fallback
            );
        }).catch(function () {
            if (models[provider] && models[provider].length) {
                renderModelOptions(provider, models[provider]);
                setStatus(
                    "ssc-ai-model-status",
                    config.strings && config.strings.fallbackModels ? config.strings.fallbackModels : "Using fallback models.",
                    true
                );
            }
        });
    }

    function providerPricing() {
        var provider = selectedProvider();
        var model = selectedModel();
        var available = models[provider] || [];

        for (var i = 0; i < available.length; i += 1) {
            if (available[i].id === model) {
                return available[i];
            }
        }

        return null;
    }

    function renderEstimate(data) {
        var target = $("ssc-ai-estimate-output");
        if (!target) return;

        var pricing = providerPricing();
        var lines = [];
        lines.push("<p><strong>" + escapeHtml(data.postTitle) + "</strong></p>");
        lines.push("<p>Provider: " + escapeHtml(data.providerLabel) + "</p>");
        lines.push("<p>Model: " + escapeHtml(data.model) + "</p>");
        lines.push("<p>Sources: " + escapeHtml(data.sourceSummary) + "</p>");
        lines.push("<p>Estimated input tokens: " + escapeHtml(String(data.inputTokens)) + "</p>");
        lines.push("<p>Estimated output tokens: " + escapeHtml(String(data.outputTokens)) + "</p>");
        lines.push("<p>Approximate cost: $" + Number(data.estimatedCost).toFixed(4) + " to $" + Number(data.estimatedRange).toFixed(4) + "</p>");
        lines.push("<p>Target field: " + escapeHtml(data.targetField) + "</p>");

        if (pricing && typeof pricing.input_cost !== "undefined") {
            lines.push("<p class=\"description\">" + escapeHtml(pricing.label) + ": $" + pricing.input_cost + "/1M input, $" + pricing.output_cost + "/1M output tokens.</p>");
        }

        target.innerHTML = lines.join("");
        latestEstimate = data;
    }

    function renderPreview(data, saved) {
        var target = $("ssc-ai-preview-output");
        if (!target) return;

        var html = [];
        html.push("<p><strong>" + escapeHtml(data.postTitle) + "</strong></p>");
        html.push("<p>Target field: " + escapeHtml(data.targetField) + "</p>");
        html.push("<p>Sources: " + escapeHtml(data.sourceSummary) + "</p>");
        html.push("<div class=\"ssc-ai-tags\">");
        (data.tags || []).forEach(function (tag) {
            html.push("<span>" + escapeHtml(tag) + "</span>");
        });
        html.push("</div>");
        if (saved) {
            html.push("<p style=\"margin-top:10px;color:#1d6f42;\"><strong>" + escapeHtml(config.strings && config.strings.saved ? config.strings.saved : "Generated tags saved.") + "</strong></p>");
        }

        target.innerHTML = html.join("");
    }

    function renderRequestSummary(data) {
        var target = $("ssc-ai-request-output");
        if (!target || !data || !data.requestSummary) return;

        var summary = data.requestSummary;
        var html = [];
        html.push("<p><strong>Provider:</strong> " + escapeHtml(summary.provider_label) + "</p>");
        html.push("<p><strong>Model:</strong> " + escapeHtml(summary.model) + "</p>");
        html.push("<p><strong>Content type:</strong> " + escapeHtml(summary.post_type) + "</p>");
        html.push("<p><strong>Fields sent:</strong> " + escapeHtml((summary.source_labels || []).join(", ")) + "</p>");
        html.push("<p><strong>Target field:</strong> " + escapeHtml(summary.target_field_label) + "</p>");
        html.push("<p><strong>Max tags:</strong> " + escapeHtml(String(summary.max_tags)) + "</p>");
        html.push("<p><strong>Language:</strong> " + escapeHtml(summary.language || "") + "</p>");
        html.push("<p><strong>Custom instruction:</strong> " + escapeHtml(summary.has_custom_instruction ? "Yes" : "No") + "</p>");

        if (summary.custom_read_fields && summary.custom_read_fields.length) {
            html.push("<p><strong>Extra custom fields:</strong> " + escapeHtml(summary.custom_read_fields.join(", ")) + "</p>");
        }

        html.push("<p>" + escapeHtml(summary.data_notice) + "</p>");
        html.push("<p>" + escapeHtml(summary.cost_notice) + "</p>");

        target.innerHTML = html.join("");
    }

    function renderError(targetId, message) {
        var target = $(targetId);
        if (!target) return;
        target.innerHTML = "<p style=\"color:#b32d2e;\">" + escapeHtml(message) + "</p>";
    }

    function buildConsentMessage(action) {
        var title = config.strings && config.strings.confirmPreview ? config.strings.confirmPreview : "Request";
        if (action === "ssc_ai_generate_tags") {
            title = config.strings && config.strings.confirmGenerate ? config.strings.confirmGenerate : "Generate request";
        } else if (action === "ssc_ai_test_connection") {
            title = config.strings && config.strings.confirmConnection ? config.strings.confirmConnection : "Connection test";
        }

        var lines = [title + ":"];
        lines.push("Provider: " + selectedProviderLabel());
        lines.push("Model: " + selectedModel());

        if (action !== "ssc_ai_test_connection") {
            lines.push("Item: " + selectedPostLabel());
            lines.push("Source fields: " + checkedValues("ssc_ai_source_fields[]").join(", "));
            lines.push("Target field: " + selectedTargetLabel());
            if (latestEstimate && latestEstimate.estimatedCost) {
                lines.push(
                    "Estimated cost: $" +
                    Number(latestEstimate.estimatedCost).toFixed(4) +
                    " to $" +
                    Number(latestEstimate.estimatedRange).toFixed(4)
                );
            }
        } else {
            lines.push("This sends a tiny direct request from WordPress to the selected provider.");
        }

        lines.push("");
        lines.push(config.strings && config.strings.confirmationWarning ? config.strings.confirmationWarning : "You are responsible for provider-side spend limits.");

        return window.confirm(lines.join("\n"));
    }

    function request(action, button, onSuccess, statusTarget) {
        var requiresItem = action === "ssc_ai_estimate_cost" || action === "ssc_ai_preview_tags" || action === "ssc_ai_generate_tags";

        if (requiresItem && !selectedPostId()) {
            var message = config.strings && config.strings.selectItem ? config.strings.selectItem : "Choose a post or product first.";
            if (statusTarget) {
                setStatus(statusTarget, message, true);
            }
            if (action !== "ssc_ai_estimate_cost") {
                renderError("ssc-ai-preview-output", message);
            } else {
                renderError("ssc-ai-estimate-output", message);
            }
            return;
        }

        if ((action === "ssc_ai_preview_tags" || action === "ssc_ai_generate_tags" || action === "ssc_ai_test_connection") && !buildConsentMessage(action)) {
            return;
        }

        setBusy(button, true);
        if (statusTarget) setStatus(statusTarget, "", false);

        var data = currentFormData(action);
        if (action === "ssc_ai_generate_tags" && $("ssc-ai-overwrite") && $("ssc-ai-overwrite").checked) {
            data.append("overwrite", "1");
        }

        fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: data
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
            if (action === "ssc_ai_estimate_cost") {
                renderError("ssc-ai-estimate-output", error.message);
            } else if (action === "ssc_ai_preview_tags" || action === "ssc_ai_generate_tags") {
                renderError("ssc-ai-preview-output", error.message);
            }
        }).finally(function () {
            setBusy(button, false);
        });
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
        var provider = $("ssc-ai-provider");
        var estimateButton = $("ssc-ai-estimate");
        var previewButton = $("ssc-ai-preview");
        var generateButton = $("ssc-ai-generate");
        var testButton = $("ssc-ai-test-connection");
        var resetSelectors = [
            "#ssc-ai-model",
            "#ssc-ai-max-tags",
            "#ssc-ai-language",
            "#ssc-ai-custom-read-fields",
            "#ssc-ai-custom-instruction",
            "#ssc-ai-post-id",
            "#ssc-ai-overwrite",
            'input[name="ssc_ai_target_field"]',
            'input[name="ssc_ai_source_fields[]"]'
        ];

        fetchModels();

        if (provider) {
            provider.addEventListener("change", function () {
                latestEstimate = null;
                fetchModels();
            });
        }

        resetSelectors.forEach(function (selector) {
            Array.prototype.slice.call(document.querySelectorAll(selector)).forEach(function (el) {
                el.addEventListener("change", function () {
                    latestEstimate = null;
                });
                el.addEventListener("input", function () {
                    latestEstimate = null;
                });
            });
        });

        if (testButton) {
            testButton.addEventListener("click", function () {
                request("ssc_ai_test_connection", testButton, function (data) {
                    setStatus("ssc-ai-test-result", data.message, false);
                }, "ssc-ai-test-result");
            });
        }

        if (estimateButton) {
            estimateButton.addEventListener("click", function () {
                request("ssc_ai_estimate_cost", estimateButton, function (data) {
                    renderEstimate(data);
                    renderRequestSummary(data);
                });
            });
        }

        if (previewButton) {
            previewButton.addEventListener("click", function () {
                request("ssc_ai_preview_tags", previewButton, function (data) {
                    renderEstimate(data);
                    renderPreview(data, false);
                    renderRequestSummary(data);
                });
            });
        }

        if (generateButton) {
            generateButton.addEventListener("click", function () {
                request("ssc_ai_generate_tags", generateButton, function (data) {
                    renderEstimate(data);
                    renderPreview(data, true);
                    renderRequestSummary(data);
                });
            });
        }

        if ($("ssc-ai-post-id") && estimateButton) {
            $("ssc-ai-post-id").addEventListener("change", function () {
                estimateButton.click();
            });
        }
    });
})();
