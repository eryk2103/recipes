(function () {
    const input = document.getElementById('discover-search');
    const dropdown = document.getElementById('autocomplete');
    const filtersEl = document.getElementById('active-filters');
    const cards = Array.from(document.querySelectorAll('.recipe-card'));

    if (!input) return;

    const activeFilters = [];
    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (!q) {
            hideDropdown();
            return;
        }
        debounceTimer = setTimeout(() => fetchSuggestions(q), 220);
    });

    input.addEventListener('blur', () => {
        setTimeout(hideDropdown, 200);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideDropdown();
            input.blur();
        }
    });

    async function fetchSuggestions(q) {
        const enc = encodeURIComponent(q);
        const [recipes, tags, ingredients] = await Promise.all([
            fetch('/api/recipes/public?search=' + enc).then(r => r.json()),
            fetch('/api/recipe-tags?search=' + enc).then(r => r.json()),
            fetch('/api/ingredients?search=' + enc).then(r => r.json()),
        ]);
        renderDropdown(recipes, tags, ingredients);
    }

    function renderDropdown(recipes, tags, ingredients) {
        if (!recipes.length && !tags.length && !ingredients.length) {
            dropdown.innerHTML = '<div class="autocomplete__empty">No results</div>';
            dropdown.hidden = false;
            return;
        }

        let html = '';
        if (recipes.length) html += buildSection('Recipe', recipes.map(r => ({ label: r.title, type: 'recipe', id: r.id })));
        if (tags.length) html += buildSection('Tag', tags.map(t => ({ label: t.name, type: 'tag', value: t.name })));
        if (ingredients.length) html += buildSection('Ingredient', ingredients.map(i => ({ label: i.name, type: 'ingredient', value: i.name })));

        dropdown.innerHTML = html;
        dropdown.hidden = false;

        dropdown.querySelectorAll('.autocomplete__item').forEach(btn => {
            btn.addEventListener('mousedown', e => e.preventDefault());
            btn.addEventListener('click', () => {
                if (btn.dataset.type === 'recipe') {
                    window.location.href = '/recipes/' + btn.dataset.id;
                } else {
                    addFilter(btn.dataset.type, btn.dataset.value);
                    input.value = '';
                    hideDropdown();
                }
            });
        });
    }

    function buildSection(label, items) {
        const rows = items.map(item => {
            if (item.type === 'recipe') {
                return `<button class="autocomplete__item" data-type="recipe" data-id="${esc(item.id)}">${esc(item.label)}</button>`;
            }
            return `<button class="autocomplete__item" data-type="${esc(item.type)}" data-value="${esc(item.value)}">${esc(item.label)}</button>`;
        }).join('');
        return `<div class="autocomplete__section"><div class="autocomplete__label">${label}</div>${rows}</div>`;
    }

    function hideDropdown() {
        dropdown.hidden = true;
        dropdown.innerHTML = '';
    }

    function addFilter(type, value) {
        const key = type + ':' + value.toLowerCase();
        if (activeFilters.find(f => f.key === key)) return;
        activeFilters.push({ type, value, key });
        renderFilters();
        filterCards();
    }

    function removeFilter(key) {
        const idx = activeFilters.findIndex(f => f.key === key);
        if (idx !== -1) activeFilters.splice(idx, 1);
        renderFilters();
        filterCards();
    }

    function renderFilters() {
        if (!activeFilters.length) {
            filtersEl.hidden = true;
            filtersEl.innerHTML = '';
            return;
        }
        filtersEl.hidden = false;
        filtersEl.innerHTML = activeFilters.map(f =>
            `<span class="chip chip--filter">
                <span class="chip__type">${esc(f.type)}</span>
                ${esc(f.value)}
                <button class="chip__remove" data-key="${esc(f.key)}" aria-label="Remove">&times;</button>
            </span>`
        ).join('');
        filtersEl.querySelectorAll('.chip__remove').forEach(btn => {
            btn.addEventListener('click', () => removeFilter(btn.dataset.key));
        });
    }

    function filterCards() {
        const tagFilters = activeFilters.filter(f => f.type === 'tag').map(f => f.value.toLowerCase());
        const ingFilters = activeFilters.filter(f => f.type === 'ingredient').map(f => f.value.toLowerCase());

        let anyVisible = false;
        cards.forEach(card => {
            const cardTags = (card.dataset.tags || '').split(',').filter(Boolean);
            const cardIngs = (card.dataset.ingredients || '').split(',').filter(Boolean);
            const visible = tagFilters.every(t => cardTags.includes(t)) && ingFilters.every(i => cardIngs.includes(i));
            card.style.display = visible ? '' : 'none';
            if (visible) anyVisible = true;
        });

        document.getElementById('no-results').hidden = anyVisible;
    }

    function esc(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
})();
