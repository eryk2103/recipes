(function () {
    const pageData = document.getElementById('page-data');
    if (!pageData) return;

    let isSaved = pageData.dataset.isSaved === '1';
    const recipeId = pageData.dataset.recipeId;
    const csrfToken = pageData.dataset.csrfToken;

    const btn = document.getElementById('save-btn');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        const endpoint = isSaved ? 'unsave' : 'save';
        const res = await fetch('/api/recipes/' + recipeId + '/' + endpoint, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        });
        if (res.ok) {
            isSaved = !isSaved;
            btn.textContent = isSaved ? 'Remove save' : 'Save';
            btn.classList.toggle('btn--primary', isSaved);
        }
    });
})();
