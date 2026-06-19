(function () {
    const visibilityBtns = Array.from(document.querySelectorAll('[data-visibility]'));
    let visibilityFilter = 'all';

    const search = window.initSearchFilter({
        inputId: 'recipe-search',
        recipeSearchUrl: '/api/recipes',
        extraVisibilityMatch: (card) => {
            const isPublic = card.dataset.public === 'true';
            const isSaved = card.dataset.saved === 'true';
            return visibilityFilter === 'all'
                || (visibilityFilter === 'public' && isPublic && !isSaved)
                || (visibilityFilter === 'private' && !isPublic && !isSaved)
                || (visibilityFilter === 'saved' && isSaved);
        },
    });

    if (!search) return;

    visibilityBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            visibilityFilter = btn.dataset.visibility;
            visibilityBtns.forEach(b => b.classList.toggle('chip--active', b === btn));
            search.filterCards();
        });
    });
})();
