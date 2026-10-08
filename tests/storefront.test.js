'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

test('offer moves above payment methods and option changes recalculate displayed prices', function () {
    const normal = {textContent: ''};
    const offer = {textContent: ''};
    let optionChange;
    const select = {
        selectedOptions: [{dataset: {adjustment: '3'}}],
        addEventListener: function (event, listener) {
            assert.equal(event, 'change');
            optionChange = listener;
        }
    };
    const card = {
        dataset: {base: '12', mode: 'percent', value: '25', tax: '20', taxIncluded: '1',
            currency: JSON.stringify({value: 1.5, symbol_left: '$', symbol_right: '', decimal_places: 2, decimal_point: '.', thousands_point: ','})},
        querySelectorAll: function () { return [select]; },
        querySelector: function (selector) { return selector === '[data-normal-price]' ? normal : offer; }
    };
    let firstChild;
    const form = {prepend: function (element) { firstChild = element; }};
    const section = {dataset: {displayMode: 'inline'}, closest: function () { return form; }, querySelectorAll: function () { return [card]; }};
    const document = {
        addEventListener: function (event, listener) { listener(); },
        getElementById: function () { return section; }
    };
    const script = fs.readFileSync(path.join(__dirname, '../ext/checkout_offer/checkout_offer.js'), 'utf8');
    vm.runInNewContext(script, {document});
    assert.equal(firstChild, section);
    assert.equal(normal.textContent, '$27.00');
    assert.equal(offer.textContent, '$20.25');
    select.selectedOptions[0].dataset.adjustment = '8';
    optionChange();
    assert.equal(normal.textContent, '$36.00');
    assert.equal(offer.textContent, '$27.00');
    card.dataset.mode = 'fixed';
    card.dataset.value = '8';
    optionChange();
    assert.equal(offer.textContent, '$14.40');
    card.dataset.taxIncluded = '0';
    optionChange();
    assert.equal(offer.textContent, '$12.00');
    card.dataset.value = '5.555';
    optionChange();
    assert.equal(offer.textContent, '$8.34');
});

test('a page without an offer block needs no payment form', function () {
    const script = fs.readFileSync(path.join(__dirname, '../ext/checkout_offer/checkout_offer.js'), 'utf8');
    vm.runInNewContext(script, {document: {addEventListener: function (event, listener) { listener(); }, getElementById: function () { return null; }}});
});
