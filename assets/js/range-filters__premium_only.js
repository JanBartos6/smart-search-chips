/**
 * Smart Search Chips Pro - numeric range chips.
 *
 * @package SmartSearchChips
 */

(function () {
    "use strict";

    var autoApplyTimers = new WeakMap();
    var AUTO_APPLY_DELAY = 350;

    function findRangeChip(target) {
        return target && target.closest ? target.closest(".ssc-range-chip") : null;
    }

    function getInput(chip, role) {
        return chip.querySelector('.ssc-range-chip__input[data-range-role="' + role + '"]');
    }

    function formatNumber(value) {
        var parsed = parseFloat(value);

        if (isNaN(parsed)) {
            return "0";
        }

        return String(Math.round(parsed * 10000) / 10000);
    }

    function buildRangeTag(chip, selectedMin, selectedMax) {
        var boundsMin = parseFloat(chip.getAttribute("data-range-bounds-min") || selectedMin);
        var boundsMax = parseFloat(chip.getAttribute("data-range-bounds-max") || selectedMax);

        if (!isNaN(boundsMin) && !isNaN(boundsMax) && parseFloat(selectedMin) <= boundsMin && parseFloat(selectedMax) >= boundsMax) {
            return "";
        }

        return [
            "sschp_range",
            chip.getAttribute("data-range-group-key") || "",
            chip.getAttribute("data-range-unit-key") || "none",
            formatNumber(selectedMin),
            formatNumber(selectedMax),
            formatNumber(boundsMin),
            formatNumber(boundsMax)
        ].join("|");
    }

    function syncChip(chip, changedRole) {
        var minInput = getInput(chip, "min");
        var maxInput = getInput(chip, "max");
        var minLabel = chip.querySelector('[data-range-role="min-label"]');
        var maxLabel = chip.querySelector('[data-range-role="max-label"]');

        if (!minInput || !maxInput) {
            return "";
        }

        var minValue = parseFloat(minInput.value);
        var maxValue = parseFloat(maxInput.value);

        if (isNaN(minValue)) {
            minValue = parseFloat(minInput.min || "0");
        }

        if (isNaN(maxValue)) {
            maxValue = parseFloat(maxInput.max || "0");
        }

        if (minValue > maxValue) {
            if (changedRole === "min") {
                maxValue = minValue;
                maxInput.value = String(maxValue);
            } else {
                minValue = maxValue;
                minInput.value = String(minValue);
            }
        }

        if (minLabel) {
            minLabel.textContent = formatNumber(minValue);
        }

        if (maxLabel) {
            maxLabel.textContent = formatNumber(maxValue);
        }

        var tag = buildRangeTag(chip, minValue, maxValue);
        chip.setAttribute("data-range-value", tag);

        return tag;
    }

    function getArchiveTags() {
        var params = new URLSearchParams(window.location.search);
        var raw = params.get("sschp_tags") || "";

        return raw.split(",").map(function (tag) {
            return tag.trim();
        }).filter(Boolean);
    }

    function applyArchiveRange(chip, tag) {
        var url = new URL(window.location.href);
        var params = url.searchParams;
        var prefix = chip.getAttribute("data-range-prefix") || "";
        var tags = getArchiveTags().filter(function (activeTag) {
            return prefix ? activeTag.indexOf(prefix) !== 0 : true;
        });

        if (tag) {
            tags.push(tag);
        }

        params.delete("paged");
        params.delete("product-page");

        if (tags.length) {
            params.set("sschp_tags", tags.join(","));
        } else {
            params.delete("sschp_tags");
        }

        window.location.href = url.toString();
    }

    function applyAjaxRange(chip, tag) {
        document.dispatchEvent(new CustomEvent("sschp:applyTag", {
            detail: {
                source: chip,
                tag: tag,
                replacePrefix: chip.getAttribute("data-range-prefix") || "",
                mode: "replace",
                chipLabel: chip.getAttribute("data-range-label") || tag
            }
        }));
    }

    function applyRange(chip, tag) {
        if ((chip.getAttribute("data-range-context") || "ajax") === "archive") {
            applyArchiveRange(chip, tag);
            return;
        }

        applyAjaxRange(chip, tag);
    }

    function scheduleAutoApply(chip, changedRole) {
        var existingTimer = autoApplyTimers.get(chip);
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        var timer = setTimeout(function () {
            autoApplyTimers.delete(chip);
            applyRange(chip, syncChip(chip, changedRole || ""));
        }, AUTO_APPLY_DELAY);

        autoApplyTimers.set(chip, timer);
    }

    document.addEventListener("input", function (event) {
        if (!event.target.matches || !event.target.matches(".ssc-range-chip__input")) {
            return;
        }

        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        var role = event.target.getAttribute("data-range-role") || "";
        syncChip(chip, role);
        scheduleAutoApply(chip, role);
    });

    document.addEventListener("change", function (event) {
        if (!event.target.matches || !event.target.matches(".ssc-range-chip__input")) {
            return;
        }

        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        scheduleAutoApply(chip, event.target.getAttribute("data-range-role") || "");
    });

    document.addEventListener("mousedown", function (event) {
        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        if (event.target.closest && event.target.closest(".ssc-range-chip__clear")) {
            event.preventDefault();
        }

        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === "function") {
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener("touchstart", function (event) {
        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === "function") {
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener("click", function (event) {
        var clearButton = event.target.closest ? event.target.closest(".ssc-range-chip__clear") : null;

        if (!clearButton) {
            return;
        }

        var chip = findRangeChip(clearButton);
        if (!chip) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === "function") {
            event.stopImmediatePropagation();
        }

        var existingTimer = autoApplyTimers.get(chip);
        if (existingTimer) {
            clearTimeout(existingTimer);
            autoApplyTimers.delete(chip);
        }

        applyRange(chip, "");
    });

    document.addEventListener("DOMContentLoaded", function () {
        Array.prototype.forEach.call(document.querySelectorAll(".ssc-range-chip"), function (chip) {
            syncChip(chip, "");
        });
    });
})();
