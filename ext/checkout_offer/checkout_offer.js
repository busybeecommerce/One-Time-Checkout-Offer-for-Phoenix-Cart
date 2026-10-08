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
