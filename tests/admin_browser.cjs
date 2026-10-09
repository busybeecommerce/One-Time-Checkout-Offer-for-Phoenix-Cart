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
        if (url.includes('checkout_offer_admin.js')) {
            return route.fulfill({ contentType: 'text/javascript', body: fs.readFileSync('ext/checkout_offer/checkout_offer_admin.js') });
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
        assert.equal(await page.locator('.co-admin-header h1').evaluate(node => getComputedStyle(node).color), 'rgb(255, 255, 255)');
        if (scenario !== 'install') assert.equal(await page.locator('[data-co-panel]:visible').count(), 1);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        await page.screenshot({ path: 'build/admin-' + scenario + '-desktop.png', fullPage: true });
    }
    assert.equal(await page.locator('[aria-current="page"]').textContent(), 'Medium basket');
    await page.getByRole('tab', { name: 'Setup', exact: true }).click();
    await page.locator('[name="display_mode"]').selectOption('modal');
    await page.getByRole('tab', { name: 'Appearance', exact: true }).click();
    assert.equal(await page.locator('[name="appearance[content_alignment]"]').isVisible(), true);
    await page.locator('[name="appearance[content_alignment]"]').selectOption('center');
    await page.getByRole('tab', { name: 'Offer template', exact: true }).click();
    await page.locator('[name="appearance[template]"][value="red"]').check();
    await page.getByRole('tab', { name: 'Appearance', exact: true }).click();
    await page.locator('[name="appearance[product_layout]"]').selectOption('row');
    for (const width of [1440, 1200, 768]) {
        await page.setViewportSize({ width, height: 1000 });
        const fields = await page.locator('.co-appearance-field').evaluateAll(nodes => nodes.map(node => {
            const control = node.querySelector('input, select').getBoundingClientRect();
            return { top: control.top, bottom: control.bottom };
        }));
        for (let index = 0; index + 1 < fields.length; index += 2) {
            assert.ok(Math.abs(fields[index].top - fields[index + 1].top) < 1, 'Paired field tops align at ' + width);
            assert.ok(Math.abs(fields[index].bottom - fields[index + 1].bottom) < 1, 'Paired field bottoms align at ' + width);
        }
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.screenshot({ path: 'build/admin-appearance-desktop.png', fullPage: true });
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
    assert.equal(submission['appearance[product_layout]'], 'row');
    assert.equal(Object.hasOwn(submission, 'accent'), false);
    await page.getByRole('tab', { name: 'Offer template', exact: true }).click();
    await page.getByText('No template / Custom', { exact: true }).click();
    await page.locator('form:has(input[value="settings"]) button').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission['appearance[template]'], 'classic');
    assert.equal(submission['appearance[product_layout]'], 'row');
    assert.equal(submission['appearance[button_background]'], '#0d6efd');
    assert.equal(await page.locator('[name="appearance[template]"]:checked').count(), 1);
    await page.getByRole('tab', { name: 'Custom offer text', exact: true }).click();
    await page.locator('[name="appearance[heading_text]"]').fill('An offer for you');
    await page.locator('[name="appearance[description_text]"]').fill('');
    await page.getByRole('tab', { name: 'Appearance', exact: true }).click();
    await page.locator('[name="appearance[heading_size]"]').fill('28');
    await page.locator('[name="appearance[heading_colour]"]').fill('#254a68');
    await page.locator('form:has(input[value="settings"]) button').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission['appearance[heading_text]'], 'An offer for you');
    assert.equal(submission['appearance[description_text]'], '');
    assert.equal(submission['appearance[heading_size]'], '28');
    assert.equal(submission['appearance[heading_colour]'], '#254a68');
    assert.equal(submission.admin_tab, 'appearance');
    await page.getByRole('tab', { name: 'Tiers & products', exact: true }).click();
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
    await page.getByRole('tab', { name: 'Appearance', exact: true }).click();
    for (const control of await page.locator('#co-panel-appearance input, #co-panel-appearance select').all()) {
        assert.ok(await control.evaluate(node => node.getBoundingClientRect().right <= innerWidth));
    }
    await page.screenshot({ path: 'build/admin-tier-mobile.png', fullPage: true });
    for (const tab of await page.getByRole('tab').all()) {
        await tab.click();
        assert.equal(await page.locator('[data-co-panel]:visible').count(), 1);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    }
    await page.getByRole('tab', { name: 'Setup', exact: true }).focus();
    await page.keyboard.press('ArrowRight');
    assert.equal(await page.getByRole('tab', { name: 'Offer template', exact: true }).getAttribute('aria-selected'), 'true');
    assert.equal(await page.locator('[name="appearance[heading_text]"]').inputValue(), 'An offer for you');
    const noScriptContext = await browser.newContext({ javaScriptEnabled: false });
    await noScriptContext.route('https://fallback.test/**', route => route.fulfill({ contentType: 'text/html', body: fs.readFileSync('build/admin-tier.html', 'utf8') }));
    const fallbackPage = await noScriptContext.newPage();
    await fallbackPage.goto('https://fallback.test/');
    assert.equal(await fallbackPage.locator('[data-co-panel]:visible').count(), 6);
    assert.deepEqual(errors, []);
    await browser.close();
    console.log('Admin browser checks passed: local linked logo, scoped styling, responsive layout, appearance and product submission.');
})().catch(error => { console.error(error); process.exitCode = 1; });
