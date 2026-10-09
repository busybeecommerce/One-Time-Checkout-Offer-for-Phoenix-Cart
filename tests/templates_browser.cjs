'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async function () {
    const bootstrap = fs.readFileSync(process.env.CHECKOUT_OFFER_BOOTSTRAP_CSS || require.resolve('bootstrap/dist/css/bootstrap.min.css'), 'utf8');
    const browser = await chromium.launch({ headless: true, ...(process.env.CHECKOUT_OFFER_BROWSER ? { channel: process.env.CHECKOUT_OFFER_BROWSER } : {}) });
    const context = await browser.newContext();
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const css = fs.readFileSync('ext/checkout_offer/checkout_offer.css', 'utf8');
    const js = fs.readFileSync('ext/checkout_offer/checkout_offer.js', 'utf8');
    await context.route('http://templates.test/**', route => {
        if (route.request().url().endsWith('.png')) {
            return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="20" y="10" width="60" height="80" rx="8" fill="#334155"/></svg>' });
        }
        const fixture = new URL(route.request().url()).pathname.slice(1);
        const html = fs.readFileSync('build/storefront-' + fixture + '.html', 'utf8');
        return route.fulfill({ contentType: 'text/html', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + bootstrap + 'body{font:16px Arial;margin:24px}' + css + '</style>' + html + '<script>' + js + '</script>' });
    });
    for (const theme of ['red', 'honey', 'midnight', 'green']) {
        for (const mode of ['inline', 'modal']) {
            await page.setViewportSize({ width: 1280, height: 900 });
            await page.goto('http://templates.test/' + mode + '-' + theme);
            if (mode === 'modal') await page.locator('#checkout-offer-modal').evaluate(node => Promise.all(node.getAnimations().map(animation => animation.finished)));
            const section = page.locator('#checkout-offer');
            assert.equal(await section.getAttribute('data-template'), theme);
            assert.equal(await page.locator('.checkout-offer-badge').isVisible(), true);
            assert.equal(await page.locator('.checkout-offer-note').isVisible(), true);
            assert.equal(await page.locator('.checkout-offer-card').first().evaluate(node => node.getBoundingClientRect().width > node.parentElement.getBoundingClientRect().width * .9), true);
            const product = page.locator('[data-offer-card]').nth(1);
            assert.equal(await product.locator('[data-offer-saving]').textContent(), '£8.40');
            assert.equal(await product.locator('[data-button-price]').textContent(), '£9.60');
            assert.equal(await product.locator('button').evaluate(node => node.form.id), 'check_form');
            assert.ok(await product.locator('button').evaluate(node => node.getBoundingClientRect().width < node.parentElement.getBoundingClientRect().width * .85));
            await page.evaluate(() => document.querySelector('form').addEventListener('submit', event => {
                event.preventDefault(); window.submission = Object.fromEntries(new FormData(event.target, event.submitter));
            }));
            await product.locator('button').click();
            const submission = await page.evaluate(() => window.submission);
            assert.equal(submission.formid, 'secret');
            assert.equal(submission.checkout_offer_product, '3');
            assert.equal(submission['checkout_offer_options[3][4]'], '8');
            if (mode === 'modal') {
                assert.equal(await section.evaluate(node => getComputedStyle(node).paddingTop), '0px');
                await page.locator('#checkout-offer-modal').evaluate(node => node.style.setProperty('--co-modal-width', '560px'));
                for (const width of [1280, 375]) {
                    await page.setViewportSize({ width, height: 900 });
                    for (const alignment of ['left', 'center', 'right']) {
                        await section.evaluate((node, value) => {
                            node.dataset.contentAlignment = value;
                            node.style.setProperty('--co-content-alignment', value);
                        }, alignment);
                        await page.locator('#checkout-offer-modal').evaluate((node, value) => node.style.setProperty('--co-content-alignment', value), alignment);
                        const bounds = await product.evaluate(node => {
                            const card = node.getBoundingClientRect();
                            const image = node.querySelector('img').getBoundingClientRect();
                            const button = node.querySelector('button').getBoundingClientRect();
                            const style = getComputedStyle(node);
                            return { imageCenter: image.left + image.width / 2, buttonCenter: button.left + button.width / 2,
                                center: card.left + card.width / 2, left: card.left + parseFloat(style.paddingLeft) + 1,
                                right: card.right - parseFloat(style.paddingRight) - 1, imageLeft: image.left, imageRight: image.right,
                                buttonRight: button.right, textAlign: getComputedStyle(node.querySelector('h3')).textAlign };
                        });
                        assert.equal(bounds.textAlign, alignment);
                        assert.ok(await product.locator('button').evaluate(node => node.getBoundingClientRect().width < node.parentElement.getBoundingClientRect().width * .85));
                        if (alignment === 'center') {
                            assert.ok(Math.abs(bounds.imageCenter - bounds.center) < 2);
                            assert.ok(Math.abs(bounds.buttonCenter - bounds.center) < 2);
                        }
                        if (alignment === 'left') assert.ok(Math.abs(bounds.imageLeft - bounds.left) < 2);
                        if (alignment === 'right') {
                            assert.ok(Math.abs(bounds.imageRight - bounds.right) < 2);
                            assert.ok(Math.abs(bounds.buttonRight - bounds.right) < 2);
                        }
                        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
                    }
                }
                await section.evaluate(node => { node.dataset.contentAlignment = 'center'; node.style.setProperty('--co-content-alignment', 'center'); });
                await page.locator('#checkout-offer-modal').evaluate(node => node.style.setProperty('--co-content-alignment', 'center'));
                await page.setViewportSize({ width: 1280, height: 900 });
            }
            await page.locator('.checkout-offer-image').evaluateAll(images => Promise.all(images.map(image => image.decode())));
            await page.screenshot({ path: 'build/template-' + theme + '-' + mode + '.png', fullPage: true });
            await page.setViewportSize({ width: 375, height: 812 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            await page.locator('.checkout-offer-image').evaluateAll(images => Promise.all(images.map(image => image.decode())));
            await page.screenshot({ path: 'build/template-' + theme + '-' + mode + '-mobile.png', fullPage: true });
            if (mode === 'modal') {
                await page.getByRole('button', { name: 'Close checkout offers' }).click();
                assert.equal(await page.locator('#checkout-offer-modal').evaluate(node => node.open), false);
                assert.equal(await page.getByRole('button', { name: 'View checkout offers' }).count(), 0);
                await page.reload();
                assert.equal(await page.locator('#checkout-offer-modal').evaluate(node => node.open), true);
            }
        }
    }
    const staticContext = await browser.newContext({ javaScriptEnabled: false });
    await staticContext.route('http://templates.test/**', route => route.fulfill({ contentType: 'text/html', body: '<meta charset="utf-8"><style>' + css + '</style>' + fs.readFileSync('build/storefront-modal-red.html', 'utf8') }));
    const staticPage = await staticContext.newPage();
    await staticPage.goto('http://templates.test/modal-red');
    assert.equal(await staticPage.locator('#checkout-offer').isVisible(), true);
    assert.equal(await staticPage.locator('[data-offer-saving]').nth(1).textContent(), '£8.40');
    assert.deepEqual(errors, []);
    await browser.close();
    console.log('Template browser checks passed: all four designs in inline/modal, mobile, price/savings, submission, dismiss/reload and no-JavaScript rendering.');
})().catch(error => { console.error(error); process.exitCode = 1; });
