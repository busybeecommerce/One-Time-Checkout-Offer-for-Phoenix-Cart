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
    if (!search) return;
    const query = search.querySelector('input');
    const status = search.querySelector('[role="status"]');
    const tasks = Array.from(manual.querySelectorAll('.co-manual-quick, .co-manual-task'));
    const originalOpen = new Map();
    function filterTasks() {
        const term = query.value.trim().toLocaleLowerCase();
        let count = 0;
        tasks.forEach(function (task) {
            if (!originalOpen.has(task)) originalOpen.set(task, task.open);
            const matches = !term || task.textContent.toLocaleLowerCase().includes(term);
            task.hidden = !matches;
            if (task.tagName === 'DETAILS') task.open = term ? matches : originalOpen.get(task);
            if (matches) count += 1;
        });
        if (!term) originalOpen.clear();
        status.textContent = term ? count + ' matching sections. Clear search to show all tasks.' : 'Search headings, field names and guidance.';
    }
    manual.addEventListener('click', function (event) {
        const link = event.target.closest('a[href^="#"]');
        if (!link) return;
        const target = document.getElementById(link.hash.slice(1));
        if (!target || !manual.contains(target)) return;
        event.preventDefault();
        window.history.replaceState(null, '', link.hash);
        query.value = '';
        filterTasks();
        const task = target.closest('details');
        if (task) {
            task.open = true;
            task.querySelector('summary').focus({ preventScroll: true });
        } else target.focus({ preventScroll: true });
        target.scrollIntoView();
    });
    query.addEventListener('input', filterTasks);
    search.hidden = false;
    filterTasks();
}
