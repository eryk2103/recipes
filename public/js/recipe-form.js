document.addEventListener('DOMContentLoaded', () => {
    function renumberSteps() {
        document.querySelectorAll('.step-row').forEach((row, index) => {
            const number = row.querySelector('.step-row__n');
            if (number) {
                number.textContent = index + 1;
            }
        });
    }

    function setPositions(name) {
        const list = document.querySelector(`[data-collection="${name}"]`);
        list.querySelectorAll(':scope > *').forEach((row, index) => {
            const position = row.querySelector('input[name$="[position]"]');
            if (position) {
                position.value = index;
            }
        });
    }

    function bindRemove(row, onRemove) {
        row.querySelector('.row-remove').addEventListener('click', () => {
            row.remove();
            if (onRemove) {
                onRemove();
            }
        });
    }

    function setupCollection(name, onChange) {
        const list = document.querySelector(`[data-collection="${name}"]`);
        const template = document.querySelector(`template[data-prototype="${name}"]`);
        const addButton = document.querySelector(`[data-add="${name}"]`);

        list.querySelectorAll(':scope > *').forEach((row) => bindRemove(row, onChange));

        addButton.addEventListener('click', () => {
            const index = parseInt(list.dataset.index, 10);
            const html = template.innerHTML.replace(/__name__/g, index);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;

            list.appendChild(row);
            list.dataset.index = index + 1;
            bindRemove(row, onChange);

            if (onChange) {
                onChange();
            }
        });
    }

    setupCollection('ingredients', () => setPositions('ingredients'));
    setupCollection('steps', () => {
        renumberSteps();
        setPositions('steps');
    });
    setupCollection('tags');

    setPositions('ingredients');
    setPositions('steps');
});
