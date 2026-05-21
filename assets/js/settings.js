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

    function settingsConfig() {
        return window.sschpSettingsDiagnostics || {};
    }

    function testSearchConfig() {
        return settingsConfig().testSearch || {};
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

    function appendText(parent, tagName, className, text) {
        var element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        element.textContent = text;
        parent.appendChild(element);
        return element;
    }

    function renderTestSearchData(output, data) {
        var strings = diagnosticStrings();
        output.innerHTML = '';

        var summary = document.createElement('div');
        summary.className = 'ssc-test-search-summary';
        output.appendChild(summary);

        [
            [strings.path || 'Path', data.pathLabel || data.path || ''],
            [strings.results || 'Results', String(data.resultCount || 0)],
            [strings.taggedResults || 'Tagged results', String(data.taggedResultCount || 0)]
        ].forEach(function (item) {
            var stat = document.createElement('div');
            var label = document.createElement('span');
            var value = document.createElement('strong');
            label.textContent = item[0];
            value.textContent = item[1];
            stat.appendChild(label);
            stat.appendChild(value);
            summary.appendChild(stat);
        });

        if (data.postTypes && data.postTypes.length) {
            appendText(output, 'p', 'description', 'Post types: ' + data.postTypes.join(', '));
        }

        if (data.explanations && data.explanations.length) {
            var explanationPanel = document.createElement('div');
            explanationPanel.className = 'ssc-test-search-panel ssc-test-search-panel--explanations';
            appendText(explanationPanel, 'h3', '', strings.explanations || 'Explanations');
            var explanationList = document.createElement('ul');
            data.explanations.forEach(function (explanation) {
                var item = document.createElement('li');
                var severity = explanation.severity || 'info';
                if (['info', 'warning', 'error'].indexOf(severity) === -1) {
                    severity = 'info';
                }
                item.className = 'ssc-test-search-explanation ssc-test-search-explanation--' + severity;
                appendText(item, 'strong', '', explanation.message || explanation.code || '');
                if (explanation.suggestedFix) {
                    appendText(item, 'span', '', (strings.suggestedFix || 'Suggested fix') + ': ' + explanation.suggestedFix);
                }
                explanationList.appendChild(item);
            });
            explanationPanel.appendChild(explanationList);
            output.appendChild(explanationPanel);
        } else if (data.warnings && data.warnings.length) {
            var warningPanel = document.createElement('div');
            warningPanel.className = 'ssc-test-search-panel ssc-test-search-panel--warnings';
            appendText(warningPanel, 'h3', '', strings.warnings || 'Warnings');
            var warningList = document.createElement('ul');
            data.warnings.forEach(function (warning) {
                appendText(warningList, 'li', '', warning);
            });
            warningPanel.appendChild(warningList);
            output.appendChild(warningPanel);
        }

        var chipsPanel = document.createElement('div');
        chipsPanel.className = 'ssc-test-search-panel';
        appendText(chipsPanel, 'h3', '', strings.generatedChips || 'Generated chips');
        var chips = document.createElement('div');
        chips.className = 'ssc-test-search-chips';
        if (data.chips && data.chips.length) {
            data.chips.forEach(function (chip) {
                var chipEl = document.createElement('span');
                chipEl.className = 'ssc-test-search-chip';
                chipEl.textContent = (chip.label || chip.value || '') + ' (' + (chip.count || 0) + ')';
                chips.appendChild(chipEl);
            });
        } else {
            appendText(chips, 'p', 'description', strings.noChips || 'No chips generated.');
        }
        chipsPanel.appendChild(chips);
        output.appendChild(chipsPanel);

        var resultsPanel = document.createElement('div');
        resultsPanel.className = 'ssc-test-search-panel';
        appendText(resultsPanel, 'h3', '', strings.firstResults || 'First results');
        if (data.results && data.results.length) {
            var list = document.createElement('ol');
            list.className = 'ssc-test-search-result-list';
            data.results.forEach(function (result) {
                var item = document.createElement('li');
                var title = document.createElement('strong');
                title.textContent = result.title || ('#' + result.id);
                item.appendChild(title);

                var meta = [];
                if (result.postType) {
                    meta.push(result.postType);
                }
                if (result.id) {
                    meta.push('#' + result.id);
                }
                if (result.tags && result.tags.length) {
                    meta.push('tags: ' + result.tags.join(', '));
                }
                appendText(item, 'span', '', meta.join(' | '));
                list.appendChild(item);
            });
            resultsPanel.appendChild(list);
        } else {
            appendText(resultsPanel, 'p', 'description', strings.noResults || 'No preview results returned.');
        }
        output.appendChild(resultsPanel);
    }

    function renderTestSearchError(output, message) {
        output.innerHTML = '';
        appendDiagnostic(output, diagnosticStrings().searchFailed || 'Search failed', false, message || (diagnosticStrings().searchFailed || 'The test search could not run.'));
    }

    function runTestSearch() {
        var output = document.getElementById('sschp-test-search-results');
        var queryInput = document.getElementById('sschp-test-search-query');
        var button = document.getElementById('sschp-run-test-search');
        var config = settingsConfig();
        var testConfig = testSearchConfig();
        var strings = diagnosticStrings();
        var ajaxTarget = config.ajaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '');

        if (!output || !queryInput || !button) {
            return;
        }

        if (!ajaxTarget) {
            renderTestSearchError(output, strings.searchFailed || 'The test search could not run.');
            return;
        }

        var query = (queryInput.value || '').trim();
        var body = new FormData();
        body.append('action', 'sschp_admin_test_search');
        body.append('nonce', testConfig.nonce || '');
        body.append('query', query);

        document.querySelectorAll('[data-sschp-test-post-type]:checked').forEach(function (checkbox) {
            body.append('post_types[]', checkbox.value);
        });

        output.innerHTML = '';
        appendText(output, 'p', 'description', strings.runningSearch || 'Running test search...');
        button.disabled = true;

        window.fetch(ajaxTarget, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.success === false) {
                throw new Error((payload && payload.data && payload.data.message) || (strings.searchFailed || 'The test search could not run.'));
            }

            renderTestSearchData(output, payload.data || payload);
        }).catch(function (error) {
            renderTestSearchError(output, error && error.message ? error.message : (strings.searchFailed || 'The test search could not run.'));
        }).finally(function () {
            button.disabled = false;
        });
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

    var testSearchButton = document.getElementById('sschp-run-test-search');
    if (testSearchButton) {
        testSearchButton.addEventListener('click', runTestSearch);
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
