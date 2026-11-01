document.querySelectorAll('[data-recipe-image]').forEach(image => {
    const fallback = () => {
        if (image.dataset.fallbackUsed) return;
        image.dataset.fallbackUsed = 'true';
        image.src = image.dataset.fallback;
    };
    image.addEventListener('error', fallback, { once: true });
    if (image.complete && image.naturalWidth === 0) fallback();
});

const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarBackdrop = document.querySelector('[data-sidebar-close]');
const sidebar = document.querySelector('#app-sidebar');

if (sidebarToggle && sidebar && sidebarBackdrop) {
    const setSidebar = open => {
        document.body.classList.toggle('sidebar-open', open);
        sidebarToggle.setAttribute('aria-expanded', String(open));
        sidebarBackdrop.hidden = !open;
        if (open) sidebar.querySelector('a, button')?.focus();
    };

    sidebarToggle.addEventListener('click', () => setSidebar(!document.body.classList.contains('sidebar-open')));
    sidebarBackdrop.addEventListener('click', () => setSidebar(false));
    sidebar.addEventListener('click', event => {
        if (event.target.closest('a') && window.matchMedia('(max-width: 1020px)').matches) setSidebar(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            setSidebar(false);
            sidebarToggle.focus();
        }
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1020 && document.body.classList.contains('sidebar-open')) setSidebar(false);
    });
}

document.querySelectorAll('[data-confirm-delete]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirmDelete || 'Xóa công thức này? Thao tác này không thể hoàn tác.')) event.preventDefault();
    });
});

document.querySelectorAll('[data-comment-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const editForm = document.getElementById(button.dataset.commentToggle);
        editForm.hidden = !editForm.hidden;
        button.setAttribute('aria-expanded', String(!editForm.hidden));
        if (!editForm.hidden) editForm.querySelector('textarea').focus();
    });
});

const form = document.querySelector('[data-recipe-form]');
if (form) {
    const reindex = container => {
        const kind = container.dataset.rows;
        container.querySelectorAll('[data-row]').forEach((row, index) => {
            row.querySelector('[data-number]').textContent = index + 1;
            row.querySelectorAll('[name], [id], [for], [aria-describedby]').forEach(element => {
                for (const attr of ['name', 'id', 'for', 'aria-describedby']) {
                    if (!element.hasAttribute(attr)) continue;
                    const value = element.getAttribute(attr)
                        .replace(new RegExp(`${kind}\\[[^\\]]+\\]`, 'g'), `${kind}[${index}]`)
                        .replace(new RegExp(`${kind}-(?:\\d+|__INDEX__)-`, 'g'), `${kind}-${index}-`)
                        .replace(new RegExp(`${kind}\\.(?:\\d+|__INDEX__)(?=\\.|-error)`, 'g'), `${kind}.${index}`);
                    element.setAttribute(attr, value);
                }
            });
            row.querySelector('[data-remove]').disabled = container.children.length <= 1;
        });
        form.querySelector(`[data-add="${kind}"]`).disabled = container.children.length >= Number(container.dataset.max);
    };
    form.addEventListener('click', event => {
        const add = event.target.closest('[data-add]');
        const remove = event.target.closest('[data-remove]');
        if (add) {
            const container = form.querySelector(`[data-rows="${add.dataset.add}"]`);
            if (container.children.length >= Number(container.dataset.max)) return;
            container.append(document.querySelector(`#${add.dataset.add}-template`).content.cloneNode(true));
            reindex(container);
            container.lastElementChild.querySelector('input, select, textarea').focus();
        }
        if (remove) {
            const container = remove.closest('[data-rows]');
            if (container.children.length <= 1) return;
            remove.closest('[data-row]').remove();
            reindex(container);
        }
    });
    form.addEventListener('change', event => {
        if (!event.target.matches('[data-ingredient]')) return;
        const unit = event.target.closest('[data-row]').querySelector('[data-unit-select]');
        if (!unit.value) unit.value = event.target.selectedOptions[0]?.dataset.unit || '';
    });
    form.addEventListener('submit', () => form.querySelectorAll('[data-rows]').forEach(reindex));
    form.querySelectorAll('[data-rows]').forEach(reindex);
}
