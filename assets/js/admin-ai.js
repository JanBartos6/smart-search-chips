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

    function $(id) {
        return document.getElementById(id);
    }

    function checkedValues(name) {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="' + name + '"]:checked')).map(function (el) {
            return el.value;
        });
    }

    function selectedPostIds() {
        var select = $("ssc-ai-post-ids");
        if (!select) return [];

        return Array.prototype.slice.call(select.options).filter(function (option) {
            return option.selected;
        }).map(function (option) {
            return option.value;
        });
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
        var resetSelectors = [
            "#ssc-ai-post-ids",
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
                el.addEventListener("change", function () {
                    restConfirmed = false;
                    setStatus("ssc-ai-rest-status", "", false);
                });
                el.addEventListener("input", function () {
                    restConfirmed = false;
                    setStatus("ssc-ai-rest-status", "", false);
                });
            });
        });

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
    });
})();
