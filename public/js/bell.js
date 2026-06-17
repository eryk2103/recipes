(function () {
    const btn = document.getElementById('bell-btn');
    const menu = document.getElementById('bell-menu');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('bell-menu--open');
    });
    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target)) {
            menu.classList.remove('bell-menu--open');
        }
    });
})();
