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

    function indexControlConfig() {
        return settingsConfig().indexControl || {};
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

    function formatTestSearchRangeNumber(value) {
        var number = typeof value === 'number' ? value : parseFloat(value);
        if (isNaN(number)) {
            return '';
        }

        return String(Math.round(number * 1000) / 1000).replace(/\.0+$/, '');
    }

    function formatTestSearchRangeSummary(chip) {
        var range = chip && chip.range ? chip.range : {};
        var min = range.selectedMin !== undefined ? range.selectedMin : range.min;
        var max = range.selectedMax !== undefined ? range.selectedMax : range.max;
        var minLabel = formatTestSearchRangeNumber(min);
        var maxLabel = formatTestSearchRangeNumber(max);
        var unit = range.unit || '';

        if (!minLabel || !maxLabel) {
            return '';
        }

        return minLabel + ' - ' + maxLabel + (unit ? ' ' + unit : '');
    }

    function renderTestSearchChip(parent, chip) {
        var isRange = chip && chip.type === 'range' && chip.range;
        var chipEl = document.createElement('span');
        chipEl.className = 'ssc-test-search-chip' + (isRange ? ' ssc-test-search-chip--range' : '');

        appendText(chipEl, 'span', 'ssc-test-search-chip__label', (chip.label || chip.value || '') + ' (' + (chip.count || 0) + ')');

        if (isRange) {
            appendText(chipEl, 'span', 'ssc-test-search-chip__badge', diagnosticStrings().rangeChip || 'Range');
            var summary = formatTestSearchRangeSummary(chip);
            if (summary) {
                appendText(chipEl, 'span', 'ssc-test-search-chip__detail', summary);
            }
        }

        parent.appendChild(chipEl);
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
                renderTestSearchChip(chips, chip);
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

    function setIndexMessage(message, isError) {
        var output = document.querySelector('[data-sschp-index-message]');
        if (!output) {
            return;
        }

        output.className = 'ssc-index-action-result' + (isError ? ' ssc-index-action-result--error' : '');
        output.textContent = message || '';
    }

    function renderIndexProgress(progress) {
        if (!progress) {
            return;
        }

        var labels = indexControlConfig().labels || {};
        var strings = diagnosticStrings();
        var status = progress.status || 'not_started';
        var statusEl = document.querySelector('[data-sschp-index-status]');
        var progressEl = document.querySelector('[data-sschp-index-progress]');
        var rowsEl = document.querySelector('[data-sschp-index-rows]');
        var updatedEl = document.querySelector('[data-sschp-index-updated]');
        var percent = parseInt(progress.percent, 10);
        var processed = parseInt(progress.processed, 10);
        var total = parseInt(progress.total, 10);
        var rows = parseInt(progress.rows, 10);

        if (statusEl) {
            statusEl.textContent = labels[status] || status;
        }

        if (progressEl) {
            progressEl.textContent = (isNaN(percent) ? 0 : percent) + '% ('
                + (isNaN(processed) ? 0 : processed) + '/'
                + (isNaN(total) ? 0 : total) + ')';
        }

        if (rowsEl) {
            rowsEl.textContent = isNaN(rows) ? '0' : String(rows);
        }

        if (updatedEl) {
            updatedEl.textContent = progress.updatedAt || strings.notYetCompleted || 'Not yet completed';
        }
    }

    function setIndexButtonsDisabled(disabled) {
        document.querySelectorAll('[data-sschp-index-action]').forEach(function (button) {
            button.disabled = disabled;
        });
    }

    function runIndexControl(button) {
        var config = settingsConfig();
        var indexConfig = indexControlConfig();
        var strings = diagnosticStrings();
        var ajaxTarget = config.ajaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '');
        var operation = button ? button.getAttribute('data-sschp-index-action') : '';

        if (!ajaxTarget || !operation) {
            setIndexMessage(strings.indexActionFailed || 'The local index action could not run.', true);
            return;
        }

        var body = new FormData();
        body.append('action', 'sschp_search_index_control');
        body.append('nonce', indexConfig.nonce || '');
        body.append('operation', operation);

        setIndexMessage(strings.indexRunning || 'Updating local index...', false);
        setIndexButtonsDisabled(true);

        window.fetch(ajaxTarget, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.success === false) {
                throw new Error((payload && payload.data && payload.data.message) || (strings.indexActionFailed || 'The local index action could not run.'));
            }

            var data = payload.data || payload;
            renderIndexProgress(data.progress);
            setIndexMessage(data.message || '', false);
        }).catch(function (error) {
            setIndexMessage(error && error.message ? error.message : (strings.indexActionFailed || 'The local index action could not run.'), true);
        }).finally(function () {
            setIndexButtonsDisabled(false);
        });
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

    function initSettingsTabs() {
        var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-sschp-settings-tab]'));
        var cards = Array.prototype.slice.call(document.querySelectorAll('[data-sschp-settings-section]'));
        var tabsByKey = {};

        if (!tabs.length || !cards.length) {
            return;
        }

        tabs.forEach(function (tab) {
            var key = tab.getAttribute('data-sschp-settings-tab') || '';
            if (key) {
                tabsByKey[key] = tab;
            }
        });

        function keyFromHash() {
            var hash = (window.location.hash || '').replace(/^#sschp-settings-/, '');
            return tabsByKey[hash] ? hash : '';
        }

        function cardMatches(card, key) {
            var sections = (card.getAttribute('data-sschp-settings-section') || '').split(/\s+/);
            return sections.indexOf(key) !== -1;
        }

        function activateSettingsTab(key, updateHash) {
            if (!tabsByKey[key]) {
                key = 'start';
            }

            tabs.forEach(function (tab) {
                var isActive = tab === tabsByKey[key];
                tab.classList.toggle('is-active', isActive);
                if (isActive) {
                    tab.setAttribute('aria-current', 'page');
                } else {
                    tab.removeAttribute('aria-current');
                }
            });

            cards.forEach(function (card) {
                card.hidden = !cardMatches(card, key);
            });

            if (updateHash && window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#sschp-settings-' + key);
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                activateSettingsTab(tab.getAttribute('data-sschp-settings-tab') || 'start', true);
            });
        });

        window.addEventListener('hashchange', function () {
            activateSettingsTab(keyFromHash() || 'start', false);
        });

        activateSettingsTab(keyFromHash() || 'start', false);
    }

    function initAnalyticsDigestDayPicker() {
        var frequency = document.querySelector('[data-sschp-digest-frequency]');
        var weeklyWrap = document.querySelector('[data-sschp-digest-day-weekly]');
        var monthlyWrap = document.querySelector('[data-sschp-digest-day-monthly]');
        var weekdaySelect = document.querySelector('[data-sschp-digest-weekday-select]');
        var monthdayInputs = Array.prototype.slice.call(document.querySelectorAll('[data-sschp-digest-monthday-input]'));

        if (!frequency || !weeklyWrap || !monthlyWrap || !weekdaySelect || !monthdayInputs.length) {
            return;
        }

        function syncDigestDay() {
            var isMonthly = frequency.value === 'monthly';

            weeklyWrap.hidden = isMonthly;
            monthlyWrap.hidden = !isMonthly;
            weekdaySelect.disabled = isMonthly;
            monthdayInputs.forEach(function (input) {
                input.disabled = !isMonthly;
            });
        }

        frequency.addEventListener('change', syncDigestDay);
        weekdaySelect.addEventListener('change', syncDigestDay);
        monthdayInputs.forEach(function (input) {
            input.addEventListener('change', syncDigestDay);
        });
        syncDigestDay();
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
    initSettingsTabs();
    initAnalyticsDigestDayPicker();

    var compatButton = document.getElementById('sschp-run-compat-test');
    if (compatButton) {
        compatButton.addEventListener('click', runCompatibilityTest);
    }

    var testSearchButton = document.getElementById('sschp-run-test-search');
    if (testSearchButton) {
        testSearchButton.addEventListener('click', runTestSearch);
    }

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-sschp-index-action]') : null;
        if (!button) {
            return;
        }

        event.preventDefault();
        runIndexControl(button);
    });

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
