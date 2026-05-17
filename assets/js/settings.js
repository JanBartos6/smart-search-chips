(function () {
    function val(name) {
        var el = document.querySelector('[name="' + name + '"]');
        return el ? el.value : null;
    }

    function diagnosticConfig() {
        return (window.sschpSettingsDiagnostics && window.sschpSettingsDiagnostics.diagnostics) || {};
    }

    function diagnosticStrings() {
        return (window.sschpSettingsDiagnostics && window.sschpSettingsDiagnostics.strings) || {};
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

    function countMatches(selector) {
        if (!isValidSelector(selector)) {
            return 0;
        }

        try {
            return document.querySelectorAll(selector).length;
        } catch (error) {
            return 0;
        }
    }

    function appendDiagnostic(container, label, pass, detail) {
        var row = document.createElement('div');
        row.className = 'ssc-compat-result ssc-compat-result--' + (pass ? 'pass' : 'warn');

        var badge = document.createElement('strong');
        badge.textContent = pass ? (diagnosticStrings().pass || 'Pass') : (diagnosticStrings().warning || 'Warning');

        var body = document.createElement('span');
        body.textContent = label + ': ' + detail;

        row.appendChild(badge);
        row.appendChild(body);
        container.appendChild(row);
    }

    function copyText(text, button) {
        var strings = diagnosticStrings();

        function setStatus(message) {
            if (!button) {
                return;
            }

            button.setAttribute('data-sschp-copy-status', message);
            button.textContent = message;
            window.setTimeout(function () {
                button.textContent = button.getAttribute('data-sschp-copy-label') || 'Copy shortcode';
                button.removeAttribute('data-sschp-copy-status');
            }, 1500);
        }

        if (!text) {
            setStatus(strings.copyFailed || 'Copy failed.');
            return;
        }

        if (window.navigator && window.navigator.clipboard && window.navigator.clipboard.writeText) {
            window.navigator.clipboard.writeText(text).then(function () {
                setStatus(strings.copied || 'Copied.');
            }).catch(function () {
                setStatus(strings.copyFailed || 'Copy failed.');
            });
            return;
        }

        try {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            setStatus(strings.copied || 'Copied.');
        } catch (error) {
            setStatus(strings.copyFailed || 'Copy failed.');
        }
    }

    function runCompatibilityTest() {
        var output = document.getElementById('sschp-compat-results');
        if (!output) {
            return;
        }

        var config = diagnosticConfig();
        var strings = diagnosticStrings();
        var inputSelector = val('sschp_search_input_selector') || config.inputSelector || '';
        var resultSelector = val('sschp_result_container_selector') || config.resultSelector || '';
        var ajaxAction = val('sschp_override_ajax') || '';
        var keywordField = val('sschp_ajax_keyword_field') || config.keywordField || '';
        var inputValid = isValidSelector(inputSelector);
        var resultValid = isValidSelector(resultSelector);
        var defaultSelectorsValid = !!(config.defaultInputValid && config.defaultResultValid);
        var keywordSane = /^[a-z0-9_-]+$/i.test(keywordField);
        var inputMatches = countMatches(inputSelector);
        var resultMatches = countMatches(resultSelector);

        output.innerHTML = '';
        appendDiagnostic(output, 'Search input selector', inputValid, inputValid ? (strings.selectorValid || 'Selector syntax is valid.') : (strings.selectorInvalid || 'Selector is invalid.'));
        appendDiagnostic(output, 'Result container selector', resultValid, resultValid ? (strings.selectorValid || 'Selector syntax is valid.') : (strings.selectorInvalid || 'Selector is invalid.'));
        appendDiagnostic(output, 'Default selectors', defaultSelectorsValid, strings.defaultSelectorsValid || 'Default selectors are valid.');
        appendDiagnostic(output, 'AJAX action', true, ajaxAction ? (strings.actionConfigured || 'Custom AJAX action is configured.') : (strings.actionDefault || 'Using built-in action.'));
        appendDiagnostic(output, 'Keyword field', keywordSane, keywordSane ? (strings.keywordSane || 'Keyword field looks valid.') : (strings.keywordInvalid || 'Keyword field looks invalid.'));
        appendDiagnostic(output, 'WooCommerce', !!config.woocommerceActive, config.woocommerceActive ? 'WooCommerce is active.' : 'WooCommerce is not active.');
        appendDiagnostic(output, 'Relevanssi', !!config.relevanssiActive, config.relevanssiActive ? 'Relevanssi is active.' : 'Relevanssi is not active.');
        appendDiagnostic(output, 'Shortcode', !!config.shortcodeAvailable, config.shortcodeAvailable ? 'The [sschp_search] shortcode is available.' : 'Shortcode registration was not detected.');
        appendDiagnostic(output, 'Current page input matches', inputMatches > 0, (strings.inputMatches || 'Matching search inputs found on this page:') + ' ' + inputMatches);
        appendDiagnostic(output, 'Current page result matches', resultMatches > 0, (strings.resultMatches || 'Matching result containers found on this page:') + ' ' + resultMatches);

        if (resultMatches < 1) {
            appendDiagnostic(output, 'Shortcode recommendation', false, strings.resultMissingRecommendation || 'No result container found. Use [sschp_search] or configure the result container selector.');
        }
    }

    function renderPreview() {
        var color = val('sschp_chip_color') || '#e74c3c';
        var textColor = val('sschp_chip_text_color') || '#ffffff';
        var inactiveColor = val('sschp_chip_inactive_color') || '#ffffff';
        var inactiveText = val('sschp_chip_inactive_text_color') || '#333333';
        var radius = (val('sschp_chip_radius') || '16') + 'px';
        var fontSize = (val('sschp_chip_font_size') || '13') + 'px';
        var padX = (val('sschp_chip_padding_x') || '12') + 'px';
        var padY = (val('sschp_chip_padding_y') || '4') + 'px';

        var container = document.getElementById('ssc-preview-chips');
        if (!container) return;

        var chips = [
            { label: 'Active chip (3)', active: true },
            { label: 'Inactive chip (7)', active: false },
            { label: 'Another chip (5)', active: false }
        ];

        container.innerHTML = '';
        chips.forEach(function (c) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = c.label;
            btn.style.cssText = 'cursor:pointer;white-space:nowrap;font-family:inherit;'
                + 'border-radius:' + radius + ';'
                + 'font-size:' + fontSize + ';'
                + 'padding:' + padY + ' ' + padX + ';'
                + 'border:1px solid ' + color + ';'
                + (c.active
                    ? 'background:' + color + ';color:' + textColor + ';'
                    : 'background:' + inactiveColor + ';color:' + inactiveText + ';');
            container.appendChild(btn);
        });
    }

    var inputs = ['sschp_chip_color', 'sschp_chip_text_color', 'sschp_chip_inactive_color', 'sschp_chip_inactive_text_color', 'sschp_chip_radius', 'sschp_chip_font_size', 'sschp_chip_padding_x', 'sschp_chip_padding_y'];
    inputs.forEach(function (name) {
        var el = document.querySelector('[name="' + name + '"]');
        if (el) {
            el.addEventListener('input', renderPreview);
            el.addEventListener('change', renderPreview);
        }
    });

    renderPreview();

    var compatButton = document.getElementById('sschp-run-compat-test');
    if (compatButton) {
        compatButton.addEventListener('click', runCompatibilityTest);
    }

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-sschp-copy]') : null;
        if (!button) {
            return;
        }

        event.preventDefault();

        if (!button.getAttribute('data-sschp-copy-label')) {
            button.setAttribute('data-sschp-copy-label', button.textContent);
        }

        copyText(button.getAttribute('data-sschp-copy'), button);
    });

    // Wire up delete-data confirmation (replaces inline onclick)
    var deleteBtn = document.getElementById('ssc-delete-data-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function (e) {
            var msg = deleteBtn.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    }

    var panel = document.getElementById('ssc-preview-panel');
    var right = document.getElementById('ssc-settings-right');
    if (panel && right && window.innerWidth > 960) {
        var rect = right.getBoundingClientRect();
        var origTop = rect.top + window.scrollY;
        var origLeft = rect.left;
        var availableWidth = document.documentElement.clientWidth - rect.left - 20;
        var origWidth = Math.min(rect.width, availableWidth);

        function resetPanel() {
            panel.removeAttribute('style');
        }

        function fixPanel() {
            panel.style.position = 'fixed';
            panel.style.top = '48px';
            panel.style.left = origLeft + 'px';
            panel.style.width = origWidth + 'px';
            panel.style.boxSizing = 'border-box';
            panel.style.zIndex = '100';
            panel.style.background = '#fff';
            panel.style.border = '1px solid #ddd';
            panel.style.borderRadius = '8px';
            panel.style.padding = '20px';
            panel.style.boxShadow = '0 1px 4px rgba(0,0,0,0.06)';
        }

        function onScroll() {
            if (window.innerWidth <= 960) { resetPanel(); return; }
            var scrollTop = window.scrollY || document.documentElement.scrollTop;
            if (scrollTop + 48 > origTop) {
                fixPanel();
            } else {
                resetPanel();
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', function () {
            resetPanel();
            var r = right.getBoundingClientRect();
            origTop = r.top + window.scrollY;
            origLeft = r.left;
            origWidth = r.width;
            onScroll();
        });
    }
})();
