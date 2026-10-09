'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async function () {
    const bootstrap = fs.readFileSync(process.env.CHECKOUT_OFFER_BOOTSTRAP_CSS || require.resolve('bootstrap/dist/css/bootstrap.min.css'), 'utf8');
    const browser = await chromium.launch({ headless: true, ...(process.env.CHECKOUT_OFFER_BROWSER ? { channel: process.env.CHECKOUT_OFFER_BROWSER } : {}) });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('https://example.test/**', route => {
        const url = route.request().url();
        if (url.includes('checkout_offer_admin.css')) {
            return route.fulfill({ contentType: 'text/css', body: fs.readFileSync('ext/checkout_offer/checkout_offer_admin.css') });
        }
        if (url.endsWith('busybee-logo.png')) {
            return route.fulfill({ contentType: 'image/png', body: fs.readFileSync('images/checkout_offer/busybee-logo.png') });
        }
        const scenario = url.split('/').pop();
        const html = fs.readFileSync('build/admin-' + scenario + '.html', 'utf8')
            .replace('<html>', '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + bootstrap + '</style></head>')
            .replace('<body>', '<body><button class="btn btn-primary" id="outside">Phoenix control</button>');
        return route.fulfill({ contentType: 'text/html', body: html });
    });
    for (const scenario of ['install', 'setup', 'tier']) {
        await page.goto('https://example.test/admin/' + scenario);
        const logo = page.locator('.co-admin-logo');
        assert.equal(await logo.getAttribute('href'), 'https://busybeecommerce.co.uk');
        assert.equal(await logo.locator('img').evaluate(node => node.complete && node.naturalWidth), 1500);
        assert.equal(await page.locator('#outside').evaluate(node => getComputedStyle(node).backgroundColor), 'rgb(13, 110, 253)');
        assert.ok(await page.locator('.co-admin-header h1').evaluate(node => parseFloat(getComputedStyle(node).fontSize) <= 32));
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        await page.screenshot({ path: 'build/admin-' + scenario + '-desktop.png', fullPage: true });
    }
    assert.equal(await page.locator('[aria-current="page"]').textContent(), 'Medium basket');
    await page.locator('.co-admin-appearance summary').click();
    assert.equal(await page.locator('[name="appearance[content_alignment]"]').isVisible(), true);
    await page.locator('[name="display_mode"]').selectOption('modal');
    await page.locator('[name="appearance[content_alignment]"]').selectOption('center');
    await page.locator('[name="appearance[template]"][value="red"]').check();
    await page.evaluate(() => document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault();
        window.submission = Object.fromEntries(new FormData(event.target, event.submitter));
    })));
    await page.locator('form:has(input[value="settings"]) button').click();
    let submission = await page.evaluate(() => window.submission);
    assert.equal(submission.formid, 'admin-render-token');
    assert.equal(submission.action, 'settings');
    assert.equal(submission.display_mode, 'modal');
    assert.equal(submission['appearance[content_alignment]'], 'center');
    assert.equal(submission['appearance[template]'], 'red');
    assert.equal(Object.hasOwn(submission, 'accent'), false);
    const productForm = page.locator('form:has(input[value="save_product"])').first();
    await productForm.locator('button').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission.products_id, '2');
    assert.equal(submission.tier_id, '1');
    assert.equal(submission.item_id, '1');
    assert.equal(submission.mode, 'percent');
    assert.equal(submission.value, '25');
    await page.setViewportSize({ width: 375, height: 812 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    for (const control of await page.locator('.co-admin-appearance input, .co-admin-appearance select').all()) {
        assert.ok(await control.evaluate(node => node.getBoundingClientRect().right <= innerWidth));
    }
    await page.screenshot({ path: 'build/admin-tier-mobile.png', fullPage: true });
    assert.deepEqual(errors, []);
    await browser.close();
    console.log('Admin browser checks passed: local linked logo, scoped styling, responsive layout, appearance and product submission.');
})().catch(error => { console.error(error); process.exitCode = 1; });
