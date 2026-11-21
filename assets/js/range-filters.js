/**
 * Smart Search Chips - numeric range chips.
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

    function getRangeInput(chip, role) {
        return chip.querySelector('.ssc-range-chip__input[data-range-role="' + role + '"]');
    }

    function getNumberInput(chip, role) {
        return chip.querySelector('.ssc-range-chip__number[data-range-role="' + role + '-number"]');
    }

    function clamp(value, minimum, maximum) {
        return Math.min(Math.max(value, minimum), maximum);
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

    function syncChip(chip, changedRole, source, normalizeNumber) {
        var minInput = getRangeInput(chip, "min");
        var maxInput = getRangeInput(chip, "max");
        var minNumber = getNumberInput(chip, "min");
        var maxNumber = getNumberInput(chip, "max");

        if (!minInput || !maxInput) {
            return null;
        }

        var minValue = parseFloat(minInput.value);
        var maxValue = parseFloat(maxInput.value);

        if (source === "number") {
            var changedNumber = changedRole === "min" ? minNumber : maxNumber;
            var typedValue = changedNumber ? parseFloat(changedNumber.value) : NaN;

            if (isNaN(typedValue)) {
                if (normalizeNumber && changedNumber) {
                    changedNumber.value = changedRole === "min" ? formatNumber(minValue) : formatNumber(maxValue);
                }

                return null;
            }

            if (changedRole === "min") {
                minValue = typedValue;
            } else {
                maxValue = typedValue;
            }
        }

        if (isNaN(minValue)) {
            minValue = parseFloat(minInput.min || "0");
        }

        if (isNaN(maxValue)) {
            maxValue = parseFloat(maxInput.max || "0");
        }

        var boundsMin = parseFloat(minInput.min || minValue);
        var boundsMax = parseFloat(maxInput.max || maxValue);

        minValue = clamp(minValue, boundsMin, boundsMax);
        maxValue = clamp(maxValue, boundsMin, boundsMax);

        if (minValue > maxValue) {
            if (changedRole === "min") {
                maxValue = minValue;
            } else {
                minValue = maxValue;
            }
        }

        minInput.value = formatNumber(minValue);
        maxInput.value = formatNumber(maxValue);

        if (minNumber && (source !== "number" || normalizeNumber || changedRole !== "min")) {
            minNumber.value = formatNumber(minValue);
        }

        if (maxNumber && (source !== "number" || normalizeNumber || changedRole !== "max")) {
            maxNumber.value = formatNumber(maxValue);
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

    function scheduleAutoApply(chip, changedRole, source) {
        var existingTimer = autoApplyTimers.get(chip);
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        var timer = setTimeout(function () {
            autoApplyTimers.delete(chip);
            var tag = syncChip(chip, changedRole || "", source || "range", true);

            if (tag !== null) {
                applyRange(chip, tag);
            }
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
        syncChip(chip, role, "range", true);
        scheduleAutoApply(chip, role, "range");
    });

    document.addEventListener("change", function (event) {
        if (!event.target.matches || !event.target.matches(".ssc-range-chip__input")) {
            return;
        }

        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        scheduleAutoApply(chip, event.target.getAttribute("data-range-role") || "", "range");
    });

    document.addEventListener("input", function (event) {
        if (!event.target.matches || !event.target.matches(".ssc-range-chip__number")) {
            return;
        }

        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        var role = (event.target.getAttribute("data-range-role") || "").replace("-number", "");
        syncChip(chip, role, "number", false);
    });

    document.addEventListener("change", function (event) {
        if (!event.target.matches || !event.target.matches(".ssc-range-chip__number")) {
            return;
        }

        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
        }

        var role = (event.target.getAttribute("data-range-role") || "").replace("-number", "");
        scheduleAutoApply(chip, role, "number");
    });

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Enter" || !event.target.matches || !event.target.matches(".ssc-range-chip__number")) {
            return;
        }

        event.preventDefault();
        event.target.blur();
    });

    document.addEventListener("mousedown", function (event) {
        var chip = findRangeChip(event.target);
        if (!chip) {
            return;
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

    document.addEventListener("DOMContentLoaded", function () {
        Array.prototype.forEach.call(document.querySelectorAll(".ssc-range-chip"), function (chip) {
            syncChip(chip, "", "range", true);
        });
    });
})();
