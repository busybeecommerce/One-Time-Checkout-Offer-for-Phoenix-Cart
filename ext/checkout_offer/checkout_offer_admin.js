'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const admin = document.querySelector('.checkout-offer-admin');
    if (!admin) {
        return;
    }
    admin.parentElement.querySelectorAll(':scope > .alert-success').forEach(function (notice) {
        if (!(notice.compareDocumentPosition(admin) & Node.DOCUMENT_POSITION_FOLLOWING)) {
            return;
        }
        window.setTimeout(function () {
            notice.classList.add('co-admin-notice-fading');
            window.setTimeout(function () { notice.remove(); }, 350);
        }, 6000);
    });
    const workspace = document.querySelector('.co-admin-workspace');
    if (!workspace) {
        return;
    }
    initialiseManual(workspace);
    workspace.querySelectorAll('[data-co-colour]').forEach(function (control) {
        const picker = control.querySelector('[data-co-colour-picker]');
        const hex = control.querySelector('[data-co-colour-hex]');
        const rgb = control.querySelector('[data-co-colour-rgb]');
        function showColour() {
            const valid = /^#[0-9a-f]{6}$/i.test(hex.value);
            rgb.value = valid ? 'rgb(' + [1, 3, 5].map(function (index) {
                return parseInt(hex.value.slice(index, index + 2), 16);
            }).join(', ') + ')' : '';
            picker.value = valid ? hex.value : '#ffffff';
        }
        picker.addEventListener('input', function () {
            hex.value = picker.value;
            showColour();
        });
        hex.addEventListener('input', showColour);
        showColour();
    });
    const displayMode = workspace.querySelector('#display-mode');
    const displayHelp = workspace.querySelector('#co-display-help');
    displayMode.addEventListener('change', function () {
        displayHelp.textContent = displayMode.value === 'modal' ? displayHelp.dataset.modalHelp : displayHelp.dataset.inlineHelp;
    });
    function formatMoney(input) {
        if (input.value !== '' && Number.isFinite(Number(input.value)) && Number(input.value) >= 0) {
            input.value = Number(input.value).toFixed(2);
        }
    }
    workspace.querySelectorAll('[data-co-money]').forEach(function (input) {
        input.addEventListener('blur', function () { formatMoney(input); });
    });
    workspace.querySelectorAll('[data-co-price-field]').forEach(function (field) {
        const mode = field.closest('form').querySelector('[name="mode"]');
        const input = field.querySelector('[name="value"]');
        mode.addEventListener('change', function () {
            const fixed = mode.value === 'fixed';
            field.querySelector('[data-co-price-symbol]').textContent = fixed ? field.dataset.currencySymbol : '%';
            input.step = '0.01';
            input.max = fixed ? '' : '100';
            formatMoney(input);
        });
        input.addEventListener('blur', function () {
            formatMoney(input);
        });
    });
    workspace.querySelectorAll('.co-admin-product-picker').forEach(function (picker) {
        const category = picker.querySelector('[data-co-category]');
        const product = picker.querySelector('[data-co-product]');
        const options = Array.from(picker.querySelector('[data-co-products]').content.querySelectorAll('option'));
        const placeholder = product.options[0];
        const current = options.find(function (option) { return option.value === product.value; });
        if (current) {
            category.value = String(JSON.parse(current.dataset.categories)[0]);
        }
        category.disabled = false;
        function populateProducts() {
            const selected = product.value;
            const matches = options.filter(function (option) {
                return category.value !== '' && JSON.parse(option.dataset.categories).includes(Number(category.value));
            });
            product.replaceChildren(placeholder, ...matches);
            product.value = matches.some(function (option) { return option.value === selected; }) ? selected : '';
        }
        category.addEventListener('change', populateProducts);
        if (current || !product.value) {
            populateProducts();
        }
    });
    const tabs = Array.from(workspace.querySelectorAll('[data-co-tab]'));
    const panels = Array.from(workspace.querySelectorAll('[data-co-panel]'));
    const settingsForm = workspace.querySelector('[name="action"][value="settings"]').form;
    workspace.querySelectorAll('form').forEach(function (form) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'admin_tab';
        form.append(input);
    });
    function selectTab(key) {
        tabs.forEach(function (tab) {
            const selected = tab.dataset.coTab === key;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.dataset.coPanel !== key;
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', 'co-tab-' + panel.dataset.coPanel);
        });
        settingsForm.hidden = key === 'offers' || key === 'maintenance' || key === 'manual';
        workspace.querySelectorAll('[name="admin_tab"]').forEach(function (input) { input.value = key; });
    }
    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { selectTab(tab.dataset.coTab); });
        tab.addEventListener('keydown', function (event) {
            let next = index;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = tabs.length - 1;
            else return;
            event.preventDefault();
            selectTab(tabs[next].dataset.coTab);
            tabs[next].focus();
        });
    });
    selectTab(workspace.dataset.initialTab);
    workspace.querySelector('.co-admin-tabs').hidden = false;
});


function initialiseManual(workspace) {
    const manual = workspace.querySelector('.co-admin-manual');
    if (!manual) return;
    const search = manual.querySelector('.co-manual-search');
    const reader = manual.querySelector('.co-manual-reader');
    const home = manual.querySelector('.co-manual-home');
    if (!search || !reader || !home) return;
    const query = search.querySelector('input');
    const status = search.querySelector('[role="status"]');
    const welcome = reader.firstElementChild;
    const tasks = Array.from(manual.querySelectorAll('.co-manual-task'));
    const text = new Map(tasks.map(task => [task, task.textContent.toLocaleLowerCase()]));
    let selected = null;

    function showGuide(task) {
        if (selected) {
            selected.append(reader.firstElementChild);
            selected.open = false;
        }
        selected = task;
        reader.replaceChildren(task ? task.querySelector('.co-manual-detail') : welcome);
        reader.removeAttribute('aria-labelledby');
        reader.setAttribute('aria-label', 'Getting started');
        if (task) {
            task.open = true;
            reader.removeAttribute('aria-label');
            reader.setAttribute('aria-labelledby', task.querySelector('summary').id);
        }
    }
    function filterTasks() {
        const term = query.value.trim().toLocaleLowerCase();
        let count = 0;
        tasks.forEach(function (task) {
            task.hidden = !!term && !text.get(task).includes(term);
            if (!task.hidden) count += 1;
        });
        manual.querySelectorAll('.co-manual-group').forEach(function (group) {
            group.hidden = !Array.from(group.querySelectorAll('.co-manual-task')).some(task => !task.hidden);
        });
        if (selected && selected.hidden) showGuide(null);
        status.textContent = term ? count + ' matching topics. Clear search to show all.' : tasks.length + ' guides · Search all instructions';
    }
    tasks.forEach(function (task) {
        const summary = task.querySelector('summary');
        summary.setAttribute('aria-controls', reader.id);
        summary.addEventListener('click', function (event) {
            event.preventDefault();
            showGuide(task);
            reader.focus({ preventScroll: true });
            if (window.matchMedia('(max-width: 767px)').matches) {
                reader.scrollIntoView();
            }
        });
    });
    manual.addEventListener('click', function (event) {
        const link = event.target.closest('a[href^="#"]');
        if (!link) return;
        const target = document.getElementById(link.hash.slice(1));
        if (!target || !manual.contains(target)) return;
        event.preventDefault();
        query.value = '';
        filterTasks();
        const task = target.closest('.co-manual-task');
        if (task) showGuide(task);
        const focus = task ? reader : target;
        focus.focus({ preventScroll: true });
        focus.scrollIntoView();
    });
    home.addEventListener('click', function () {
        query.value = '';
        filterTasks();
        showGuide(null);
        reader.focus({ preventScroll: true });
        reader.scrollIntoView();
    });
    query.addEventListener('input', filterTasks);
    manual.classList.add('co-manual-enhanced');
    search.hidden = false;
    home.hidden = false;
    filterTasks();
}
