(function () {
    function val(name) {
        var el = document.querySelector('[name="' + name + '"]');
        return el ? el.value : null;
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
