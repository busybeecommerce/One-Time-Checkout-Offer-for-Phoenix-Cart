'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async function () {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHECKOUT_OFFER_BROWSER ? { channel: process.env.CHECKOUT_OFFER_BROWSER } : {}) });
    const context = await browser.newContext();
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const bootstrap = fs.readFileSync(process.env.CHECKOUT_OFFER_BOOTSTRAP_CSS || require.resolve('bootstrap/dist/css/bootstrap.min.css'), 'utf8');
    const css = fs.readFileSync('ext/checkout_offer/checkout_offer.css', 'utf8');
    const script = fs.readFileSync('ext/checkout_offer/checkout_offer.js', 'utf8');
    await context.route('http://checkout-offer.test/**', route => {
        if (route.request().url().endsWith('.png')) {
            return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="140"><circle cx="100" cy="70" r="55" fill="#87b840"/></svg>' });
        }
        const mode = route.request().url().includes('inline') ? 'inline' : 'modal';
        const html = fs.readFileSync('build/storefront-' + mode + (route.request().url().includes('custom') ? '-custom' : '') + '.html', 'utf8');
        return route.fulfill({ contentType: 'text/html', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + bootstrap + 'body{font:16px Arial;margin:24px}.btn{padding:8px 12px;cursor:pointer}select{max-width:100%}.me-2{margin-right:8px}' + css + '</style>' + html + '<script>' + script + '</script>' });
    });
    await page.goto('http://checkout-offer.test/modal');
    const dialog = page.locator('#checkout-offer-modal');
    await dialog.evaluate(node => Promise.all(node.getAnimations().map(animation => animation.finished)));
    assert.equal(await dialog.evaluate(node => node.open), true);
    assert.equal(await dialog.evaluate(node => Math.round(node.getBoundingClientRect().width)), 800);
    assert.equal(await page.locator('#checkout-offer').evaluate(node => getComputedStyle(node).backgroundColor), 'rgb(238, 246, 255)');
    assert.equal(await page.locator('.checkout-offer-image').first().evaluate(node => getComputedStyle(node).height), '180px');
    assert.equal(await page.locator('[name="checkout_offer_product"]').first().evaluate(node => node.form.id), 'check_form');
    assert.equal(await page.locator('[data-offer-option]').evaluate(node => node.form.id), 'check_form');
    assert.equal(await page.locator('.checkout-offer-modal-controls').count(), 0);
    assert.equal(await page.locator('.checkout-offer-modal-close').evaluate(node => getComputedStyle(node).position), 'absolute');
    const closeBounds = await page.locator('.checkout-offer-modal-close').evaluate(node => {
        const button = node.getBoundingClientRect();
        const icon = node.firstElementChild.getBoundingClientRect();
        return { width: button.width, height: button.height, border: getComputedStyle(node).borderTopWidth,
            x: icon.left + icon.width / 2 - button.left - button.width / 2,
            y: icon.top + icon.height / 2 - button.top - button.height / 2 };
    });
    assert.equal(closeBounds.width, 28);
    assert.equal(closeBounds.height, 28);
    assert.equal(closeBounds.border, '1px');
    assert.ok(Math.abs(closeBounds.x) < .5 && Math.abs(closeBounds.y) < .5);
    assert.equal(await page.locator('#checkout-offer').evaluate(node => getComputedStyle(node).borderTopWidth), '1px');
    for (const alignment of ['left', 'center', 'right']) {
        await page.locator('#checkout-offer').evaluate((node, value) => {
            node.style.setProperty('--co-content-alignment', value);
            node.style.setProperty('--co-products-alignment', value);
            node.querySelectorAll('[data-offer-card]')[1].hidden = true;
        }, alignment);
        await dialog.evaluate((node, value) => node.style.setProperty('--co-content-alignment', value), alignment);
        assert.equal(await page.locator('#checkout-offer h2').evaluate(node => getComputedStyle(node).textAlign), alignment);
        assert.equal(await page.locator('.checkout-offer-modal-footer').count(), 0);
        assert.equal(await page.locator('.checkout-offer-dismiss').evaluate(node => node.parentElement.id), 'checkout-offer');
        assert.equal(await page.locator('.checkout-offer-dismiss').evaluate(node => getComputedStyle(node.parentElement).textAlign), alignment);
        const bounds = await page.locator('.checkout-offer-grid').evaluate(node => {
            const grid = node.getBoundingClientRect();
            const card = node.firstElementChild.getBoundingClientRect();
            return { left: card.left - grid.left, right: grid.right - card.right, width: grid.width, cardWidth: card.width };
        });
        if (alignment === 'left') assert.ok(bounds.left < 1);
        if (alignment === 'center') assert.ok(Math.abs(bounds.left - bounds.right) < 1);
        if (alignment === 'right') assert.ok(bounds.right < 1);
        assert.ok(bounds.cardWidth < bounds.width * .6);
    }
    await page.locator('#checkout-offer').evaluate(node => {
        node.style.setProperty('--co-content-alignment', 'center');
        node.style.setProperty('--co-products-alignment', 'center');
        node.querySelectorAll('[data-offer-card]')[1].hidden = false;
    });
    await dialog.evaluate(node => node.style.setProperty('--co-content-alignment', 'center'));
    await page.locator('#checkout-offer').evaluate(node => { node.dataset.productLayout = 'stacked'; });
    assert.ok(await page.locator('.checkout-offer-card').first().evaluate(node => node.getBoundingClientRect().width > node.parentElement.getBoundingClientRect().width * .95));
    await page.locator('#checkout-offer').evaluate(node => { node.dataset.productLayout = 'row'; });
    const rowFooters = await page.locator('.checkout-offer-card').evaluateAll(nodes => nodes.map(node => ({
        button: node.querySelector('button').getBoundingClientRect().bottom,
        price: node.querySelector('.checkout-offer-prices').getBoundingClientRect().top
    })));
    assert.ok(Math.abs(rowFooters[0].button - rowFooters[1].button) < 1);
    assert.ok(Math.abs(rowFooters[0].price - rowFooters[1].price) < 1);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    assert.equal(await dialog.evaluate(node => getComputedStyle(node).animationName), 'none');
    await page.keyboard.press('Tab');
    assert.equal(await page.evaluate(() => document.querySelector('dialog').contains(document.activeElement)), true);
    await page.screenshot({ path: 'build/modal-desktop.png', fullPage: true });
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.activeElement.name === 'payment');
    assert.equal(await dialog.evaluate(node => node.open), false);
    assert.equal(await page.getByRole('button', { name: 'View checkout offers' }).count(), 0);
    await page.reload();
    await page.getByRole('button', { name: 'No thanks, continue checkout' }).click();
    await page.waitForFunction(() => document.activeElement.name === 'payment');
    assert.equal(await dialog.evaluate(node => node.open), false);
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: false })));
    assert.equal(await dialog.evaluate(node => node.open), false);
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
    assert.equal(await dialog.evaluate(node => node.open), true);
    await page.reload();
    assert.equal(await dialog.evaluate(node => node.open), true);
    await page.goto('http://checkout-offer.test/modal?repeat-entry');
    assert.equal(await dialog.evaluate(node => node.open), true);
    await page.getByRole('button', { name: 'Close checkout offers' }).click();
    await page.waitForTimeout(100);
    assert.equal(await dialog.evaluate(node => node.open), false);
    await page.reload();
    await page.evaluate(() => document.querySelector('form').addEventListener('submit', event => {
        event.preventDefault();
        window.submission = Object.fromEntries(new FormData(event.target, event.submitter));
        window.submitAction = event.submitter.formAction;
    }));
    await page.locator('button[value="3"]').click();
    const submission = await page.evaluate(() => window.submission);
    assert.equal(submission.formid, 'secret');
    assert.equal(submission.payment, 'cod');
    assert.equal(submission.checkout_offer_product, '3');
    assert.equal(submission['checkout_offer_options[3][4]'], '8');
    assert.equal(await page.evaluate(() => window.submitAction), 'http://checkout-offer.test/checkout_payment.php');
    await page.setViewportSize({ width: 375, height: 812 });
    assert.ok(await dialog.evaluate(node => node.getBoundingClientRect().width <= 343));
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: 'build/modal-mobile.png', fullPage: true });
    await page.mouse.click(2, 2);
    assert.equal(await dialog.evaluate(node => node.open), false);
    await page.goto('http://checkout-offer.test/inline');
    assert.equal(await page.locator('#checkout-offer').evaluate(node => node.parentElement.firstElementChild === node), true);
    assert.equal(await page.locator('dialog').count(), 0);
    await page.setViewportSize({ width: 1440, height: 1000 });
    const inlineCards = await page.locator('.checkout-offer-card').evaluateAll(nodes => nodes.map(node => {
        const card = node.getBoundingClientRect();
        const price = node.querySelector('.checkout-offer-prices').getBoundingClientRect();
        const button = node.querySelector('button').getBoundingClientRect();
        return { bottom: button.bottom, priceCentre: price.top + price.height / 2, buttonCentre: button.top + button.height / 2, width: card.width, buttonWidth: button.width };
    }));
    assert.ok(Math.abs(inlineCards[0].bottom - inlineCards[1].bottom) < 1, 'Inline card buttons remain aligned with product options');
    assert.ok(inlineCards.every(card => Math.abs(card.priceCentre - card.buttonCentre) < 1), 'Inline price and Add button share a compact purchase row');
    assert.ok(inlineCards.every(card => card.buttonWidth < card.width * .75), 'Inline buttons stay compact');
    await page.screenshot({ path: 'build/inline-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 375, height: 812 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: 'build/inline-mobile.png', fullPage: true });
    const fallback = await context.newPage();
    await fallback.addInitScript(() => { HTMLDialogElement.prototype.showModal = undefined; });
    await fallback.goto('http://checkout-offer.test/modal');
    assert.equal(await fallback.locator('dialog').count(), 0);
    assert.equal(await fallback.locator('#checkout-offer').isVisible(), true);
    const blockedStorage = await context.newPage();
    await blockedStorage.addInitScript(() => { Object.defineProperty(window, 'sessionStorage', { get() { throw new Error('disabled'); } }); });
    await blockedStorage.goto('http://checkout-offer.test/modal');
    assert.equal(await blockedStorage.locator('dialog').evaluate(node => node.open), true);
    const noScript = await browser.newContext({ javaScriptEnabled: false });
    await noScript.route('http://checkout-offer.test/**', route => route.fulfill({ contentType: 'text/html', body: fs.readFileSync('build/storefront-modal.html', 'utf8') }));
    const staticPage = await noScript.newPage();
    await staticPage.goto('http://checkout-offer.test/modal');
    assert.equal(await staticPage.locator('#checkout-offer').isVisible(), true);
    await page.goto('http://checkout-offer.test/modal-custom');
    assert.equal(await page.locator('#checkout-offer-title').textContent(), 'Your <b>exclusive</b> offer');
    assert.equal(await page.locator('#checkout-offer-title b').count(), 0);
    assert.equal(await page.locator('#checkout-offer-title').evaluate(node => getComputedStyle(node).fontSize), '26px');
    assert.equal(await page.locator('#checkout-offer-title').evaluate(node => getComputedStyle(node).fontWeight), '600');
    assert.equal(await page.locator('#checkout-offer-title').evaluate(node => getComputedStyle(node).color), 'rgb(37, 74, 104)');
    assert.equal(await page.locator('.checkout-offer-description').evaluate(node => getComputedStyle(node).fontStyle), 'italic');
    assert.equal(await page.locator('.checkout-offer-description').evaluate(node => getComputedStyle(node).fontSize), '17px');
    assert.equal(await page.locator('.checkout-offer-classic-label').first().textContent(), 'Choose this offer');
    await page.screenshot({ path: 'build/modal-custom-text.png', fullPage: true });
    await page.getByRole('button', { name: 'Continue without an offer' }).click();
    assert.equal(await dialog.evaluate(node => node.open), false);
    assert.deepEqual(errors, []);
    await browser.close();
    console.log('Browser checks passed: modal, dismissal, repeat entry/reload, focus, reduced motion, form submission, mobile, storage and inline fallbacks.');
})().catch(error => { console.error(error); process.exitCode = 1; });
