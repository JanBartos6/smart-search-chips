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

    function $(id) {
        return document.getElementById(id);
    }

    function selectedProvider() {
        var el = $("ssc-ai-provider");
        return el ? el.value : "openai";
    }

    function selectedModel() {
        var el = $("ssc-ai-model");
        return el ? el.value : "";
    }

    function selectedPostId() {
        var el = $("ssc-ai-post-id");
        return el ? el.value : "";
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
        data.append("openai_key", $("ssc-ai-openai-key") ? $("ssc-ai-openai-key").value : "");
        data.append("anthropic_key", $("ssc-ai-anthropic-key") ? $("ssc-ai-anthropic-key").value : "");
        data.append("gemini_key", $("ssc-ai-gemini-key") ? $("ssc-ai-gemini-key").value : "");
        data.append("custom_read_fields", $("ssc-ai-custom-read-fields") ? $("ssc-ai-custom-read-fields").value : "");
        data.append("target_field", document.querySelector('input[name="ssc_ai_target_field"]:checked') ? document.querySelector('input[name="ssc_ai_target_field"]:checked').value : "built_in");
        data.append("max_tags", $("ssc-ai-max-tags") ? $("ssc-ai-max-tags").value : "8");
        data.append("language", $("ssc-ai-language") ? $("ssc-ai-language").value : "");
        data.append("custom_instruction", $("ssc-ai-custom-instruction") ? $("ssc-ai-custom-instruction").value : "");
        checkedValues("ssc_ai_source_fields[]").forEach(function (field) {
            data.append("source_fields[]", field);
        });

        var postId = selectedPostId();
        if (postId) {
            data.append("post_id", postId);
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

    function renderModelOptions() {
        var provider = selectedProvider();
        var modelSelect = $("ssc-ai-model");
        if (!modelSelect || !models[provider]) return;

        var previous = modelSelect.value || modelSelect.getAttribute("data-selected") || "";
        modelSelect.innerHTML = "";

        models[provider].forEach(function (model, index) {
            var option = document.createElement("option");
            option.value = model.id;
            option.textContent = model.label + " - " + model.description;
            if (previous === model.id || (!previous && index === 0)) {
                option.selected = true;
            }
            modelSelect.appendChild(option);
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
        lines.push("<p>Sources: " + escapeHtml(data.sourceSummary) + "</p>");
        lines.push("<p>Estimated input tokens: " + escapeHtml(String(data.inputTokens)) + "</p>");
        lines.push("<p>Estimated output tokens: " + escapeHtml(String(data.outputTokens)) + "</p>");
        lines.push("<p>Approximate cost: $" + Number(data.estimatedCost).toFixed(4) + " to $" + Number(data.estimatedRange).toFixed(4) + "</p>");
        if (pricing) {
            lines.push("<p class=\"description\">" + escapeHtml(pricing.label) + ": $" + pricing.input_cost + "/1M input, $" + pricing.output_cost + "/1M output tokens.</p>");
        }
        lines.push("<p>Target field: " + escapeHtml(data.targetField) + "</p>");
        target.innerHTML = lines.join("");
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

    function request(action, button, onSuccess, statusTarget) {
        var postId = selectedPostId();
        if ((action === "ssc_ai_estimate_cost" || action === "ssc_ai_preview_tags" || action === "ssc_ai_generate_tags") && !postId) {
            if (statusTarget) {
                setStatus(statusTarget, config.strings && config.strings.selectItem ? config.strings.selectItem : "Choose a post or product first.", true);
            }
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
                var message = json && json.data && json.data.message ? json.data.message : "Request failed.";
                throw new Error(message);
            }
            onSuccess(json.data);
        }).catch(function (error) {
            if (statusTarget) {
                setStatus(statusTarget, error.message, true);
            }
            if ((action === "ssc_ai_preview_tags" || action === "ssc_ai_generate_tags") && $("ssc-ai-preview-output")) {
                $("ssc-ai-preview-output").innerHTML = "<p style=\"color:#b32d2e;\">" + escapeHtml(error.message) + "</p>";
            }
            if (action === "ssc_ai_estimate_cost" && $("ssc-ai-estimate-output")) {
                $("ssc-ai-estimate-output").innerHTML = "<p style=\"color:#b32d2e;\">" + escapeHtml(error.message) + "</p>";
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

        renderModelOptions();

        if (provider) {
            provider.addEventListener("change", function () {
                renderModelOptions();
            });
        }

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
                });
            });
        }

        if (previewButton) {
            previewButton.addEventListener("click", function () {
                request("ssc_ai_preview_tags", previewButton, function (data) {
                    renderEstimate(data);
                    renderPreview(data, false);
                });
            });
        }

        if (generateButton) {
            generateButton.addEventListener("click", function () {
                request("ssc_ai_generate_tags", generateButton, function (data) {
                    renderEstimate(data);
                    renderPreview(data, true);
                });
            });
        }

        if ($("ssc-ai-post-id")) {
            $("ssc-ai-post-id").addEventListener("change", function () {
                if (estimateButton) {
                    estimateButton.click();
                }
            });
        }
    });
})();
