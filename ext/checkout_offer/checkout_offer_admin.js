'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const workspace = document.querySelector('.co-admin-workspace');
    if (!workspace) {
        return;
    }
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
        settingsForm.hidden = key === 'offers' || key === 'maintenance';
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
