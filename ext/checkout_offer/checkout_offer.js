'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('checkout-offer');
    if (!section) {
        return;
    }
    const form = section.closest('form');
    if (!form) {
        return;
    }
    // The hook is inside the payment form; move its block before payment methods.
    form.prepend(section);
    if (section.dataset.displayMode === 'modal') {
        const dialog = document.createElement('dialog');
        if (typeof dialog.showModal === 'function') {
            dialog.id = 'checkout-offer-modal';
            dialog.style.cssText = section.style.cssText;
            dialog.setAttribute('aria-labelledby', 'checkout-offer-title');
            const launcher = document.createElement('button');
            launcher.type = 'button';
            launcher.className = 'btn btn-outline-primary mb-3';
            launcher.textContent = section.dataset.openLabel;
            launcher.setAttribute('aria-haspopup', 'dialog');
            launcher.setAttribute('aria-controls', dialog.id);
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'checkout-offer-modal-close';
            close.textContent = '×';
            close.setAttribute('aria-label', section.dataset.closeLabel);
            const footer = document.createElement('div');
            footer.className = 'checkout-offer-modal-footer';
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'btn btn-outline-secondary btn-sm';
            dismiss.textContent = section.dataset.dismissLabel;
            footer.append(dismiss);
            dialog.append(close, section, footer);
            // Keep every offer field and submit button inside the original payment form.
            form.prepend(dialog);
            form.prepend(launcher);
            launcher.addEventListener('click', function () { dialog.showModal(); });
            close.addEventListener('click', function () { dialog.close(); });
            dismiss.addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('close', function () { launcher.focus(); });
            dialog.addEventListener('click', function (event) {
                if (event.target !== dialog) {
                    return;
                }
                const bounds = dialog.getBoundingClientRect();
                if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
                    dialog.close();
                }
            });
            const key = 'checkout-offer-viewed:' + section.dataset.offerKey;
            let viewed = false;
            try {
                viewed = sessionStorage.getItem(key) === '1';
                sessionStorage.setItem(key, '1');
            } catch (error) {
                // Storage may be disabled; the modal remains usable without remembering dismissal.
            }
            if (!viewed) {
                dialog.showModal();
            }
        }
    }
    section.querySelectorAll('[data-offer-card]').forEach(function (card) {
        const currency = JSON.parse(card.dataset.currency);
        function format(price) {
            const tax = card.dataset.taxIncluded === '1' ? 1 + Number(card.dataset.tax) / 100 : 1;
            const scale = Math.pow(10, Number(currency.decimal_places));
            // Phoenix rounds the unit price before converting its currency.
            const unit = Math.round((price * tax + Number.EPSILON) * scale) / scale;
            const converted = Math.round((unit * Number(currency.value) + Number.EPSILON) * scale) / scale;
            const parts = converted.toFixed(Number(currency.decimal_places)).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousands_point);
            return currency.symbol_left + parts.join(currency.decimal_point) + currency.symbol_right;
        }
        function refresh() {
            let normal = Number(card.dataset.base);
            card.querySelectorAll('[data-offer-option]').forEach(function (select) {
                normal += Number(select.selectedOptions[0].dataset.adjustment);
            });
            normal = Math.max(0, normal);
            const value = Number(card.dataset.value);
            const offer = Math.round(Math.min(normal, card.dataset.mode === 'fixed' ? value : normal * (1 - value / 100)) * 10000) / 10000;
            card.querySelector('[data-normal-price]').textContent = format(normal);
            card.querySelector('[data-offer-price]').textContent = format(offer);
        }
        card.querySelectorAll('[data-offer-option]').forEach(function (select) {
            select.addEventListener('change', refresh);
        });
        refresh();
    });
});
