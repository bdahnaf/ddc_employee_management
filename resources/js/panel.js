// DDC Workforce — signed-in area and sign-in page behaviour (no dependencies)

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// ---- Mobile navigation ----
const bar = $('[data-bar]');
$('[data-burger]')?.addEventListener('click', (e) => {
    const open = bar.classList.toggle('nav-open');
    e.currentTarget.setAttribute('aria-expanded', String(open));
});

// ---- Drop-down menus (Manage, account) ----
$$('[data-menu]').forEach((menu) => {
    const btn = $('[data-menu-btn]', menu);
    btn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = !menu.classList.contains('is-open');
        $$('[data-menu].is-open').forEach((m) => m.classList.remove('is-open'));
        menu.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', String(open));
    });
});
document.addEventListener('click', (e) => {
    $$('[data-menu].is-open').forEach((m) => {
        if (!m.contains(e.target)) m.classList.remove('is-open');
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        $$('[data-menu].is-open').forEach((m) => m.classList.remove('is-open'));
        bar?.classList.remove('nav-open');
    }
});

// ---- Show / hide password ----
$$('[data-pw-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const input = $('[data-pw]', btn.parentElement);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});

// ---- Ask before destructive actions ----
$$('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
});

// ---- Fade out success messages ----
$$('[data-notice]').forEach((el) => {
    setTimeout(() => {
        el.classList.add('is-hiding');
        setTimeout(() => el.remove(), 450);
    }, 5000);
});

// ---- Directory: choose Division / Department, auto-apply place selects ----
const where = $('.where[data-autosubmit]');
if (where) {
    const boxes = $$('[data-pick-box]', where);
    const showBox = (name) => {
        boxes.forEach((box) => {
            const on = box.dataset.pickBox === name;
            box.hidden = !on;
            $('select', box).disabled = !on;
        });
    };

    $$('[data-pick]', where).forEach((tab) => {
        tab.addEventListener('click', () => {
            $$('.tab', where).forEach((t) => t.classList.remove('is-on'));
            tab.classList.add('is-on');
            showBox(tab.dataset.pick);
            $(`[data-pick-box="${tab.dataset.pick}"] select`, where)?.focus();
        });
    });

    // Picking a place or changing a filter applies it straight away.
    $$('.where-pick select, .filters select', where).forEach((sel) => {
        sel.addEventListener('change', () => {
            if (sel.name === 'place' && !sel.value) return;
            where.requestSubmit();
        });
    });
}

// ---- HQ / Project switch shows the right field ----
$$('[data-posting-type]').forEach((group) => {
    const root = group.closest('form');
    const sync = () => {
        const type = $('input[name="posting_type"]:checked', group)?.value;
        $$('[data-show-for]', root).forEach((el) => {
            el.hidden = el.dataset.showFor !== type;
        });
    };
    group.addEventListener('change', sync);
    sync();
});

// ---- Dialogs (Transfer) ----
$$('[data-open]').forEach((btn) => {
    btn.addEventListener('click', () => document.getElementById(btn.dataset.open)?.showModal());
});
$$('dialog').forEach((dialog) => {
    $$('[data-close]', dialog).forEach((b) => b.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close(); // click on the backdrop
    });
    if (dialog.hasAttribute('data-open-now')) dialog.showModal(); // reopen after a validation error
});

// ---- Employee form: photo preview + live gross salary ----
const photoInput = $('[data-photo-input]');
photoInput?.addEventListener('change', () => {
    const file = photoInput.files?.[0];
    const preview = $('[data-photo-preview]');
    if (!file || !preview) return;
    const img = document.createElement('img');
    img.alt = '';
    img.src = URL.createObjectURL(file);
    preview.replaceChildren(img);
});

const grossOut = $('[data-gross]');
if (grossOut) {
    const parts = $$('[data-salary-part]');
    const update = () => {
        const total = parts.reduce((sum, el) => sum + (parseFloat(el.value) || 0), 0);
        grossOut.textContent = total.toLocaleString('en-IN');
    };
    parts.forEach((el) => el.addEventListener('input', update));
}
