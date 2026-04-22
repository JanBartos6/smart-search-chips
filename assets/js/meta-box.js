(function () {
    var container = document.getElementById('ssc-tags-container');
    var input = document.getElementById('ssc-tag-input');
    var hidden = document.getElementById('ssc-tags-hidden');
    var addBtn = document.getElementById('ssc-add-tag');

    if (!container || !input || !hidden || !addBtn) {
        return;
    }

    function updateHidden() {
        var tags = [];
        container.querySelectorAll('.ssc-meta-box__tag').forEach(function (el) {
            var btn = el.querySelector('.ssc-meta-box__remove');
            if (btn) tags.push(btn.getAttribute('data-tag'));
        });
        hidden.value = tags.join('; ');
    }

    function addTag(text) {
        text = text.trim();
        if (!text) return;

        var existing = hidden.value.split(';').map(function (t) { return t.trim().toLowerCase(); });
        if (existing.indexOf(text.toLowerCase()) > -1) return;

        var span = document.createElement('span');
        span.className = 'ssc-meta-box__tag';
        span.textContent = text + ' ';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ssc-meta-box__remove';
        btn.setAttribute('data-tag', text);
        btn.textContent = '\u00d7';
        span.appendChild(btn);
        container.appendChild(span);
        updateHidden();
        input.value = '';
        input.focus();
    }

    addBtn.addEventListener('click', function () { addTag(input.value); });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag(input.value);
        }
    });

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('ssc-meta-box__remove')) {
            e.target.parentElement.remove();
            updateHidden();
        }
    });
})();
