'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async function () {
    const bootstrap = fs.readFileSync(process.env.CHECKOUT_OFFER_BOOTSTRAP_CSS || require.resolve('bootstrap/dist/css/bootstrap.min.css'), 'utf8');
    const browser = await chromium.launch({ headless: true, ...(process.env.CHECKOUT_OFFER_BROWSER ? { channel: process.env.CHECKOUT_OFFER_BROWSER } : {}) });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    await page.clock.install();
    await page.clock.pauseAt(new Date());
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
        assert.equal(await page.locator('.co-admin-header').evaluate(node => getComputedStyle(node).backgroundColor), 'rgb(255, 122, 0)');
        for (const width of [1920, 1440, 375]) {
            await page.setViewportSize({ width, height: 1000 });
            const bounds = await page.evaluate(() => {
                const admin = document.querySelector('.checkout-offer-admin').getBoundingClientRect();
                return Array.from(document.querySelectorAll('.alert')).map(notice => ({ left: notice.getBoundingClientRect().left - admin.left, right: notice.getBoundingClientRect().right - admin.right }));
            });
            assert.ok(bounds.every(rect => Math.abs(rect.left) < 1 && Math.abs(rect.right) < 1), 'Notice edges match admin content at ' + width);
        }
        await page.setViewportSize({ width: 1440, height: 1000 });
        assert.equal(await page.locator('.alert-success').count(), 1);
        await page.clock.runFor(6000);
        assert.equal(await page.locator('.alert-success').evaluate(node => node.classList.contains('co-admin-notice-fading')), true);
        await page.clock.runFor(400);
        assert.equal(await page.locator('.alert-success').count(), 0);
        assert.equal(await page.locator('.alert-warning:visible, .alert-danger:visible').count(), 2);
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
        for (const group of await page.locator('.co-admin-field-group').all()) {
        const fields = await group.locator('.co-appearance-field').evaluateAll(nodes => nodes.map(node => {
            const control = node.querySelector('input, select').getBoundingClientRect();
            return { top: control.top, bottom: control.bottom };
        }));
        for (let index = 0; index + 1 < fields.length; index += 2) {
            assert.ok(Math.abs(fields[index].top - fields[index + 1].top) < 1, 'Paired field tops align at ' + width);
            assert.ok(Math.abs(fields[index].bottom - fields[index + 1].bottom) < 1, 'Paired field bottoms align at ' + width);
        }
        }
        for (const control of await page.locator('#co-panel-appearance input[type="number"], #co-panel-appearance input[type="text"]').all()) {
            assert.ok(await control.evaluate(node => node.getBoundingClientRect().width <= 161), 'Short values have compact fields');
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
    await page.locator('#co-panel-templates').getByText('No template / Custom', { exact: true }).click();
    await page.locator('form:has(input[value="settings"]) button').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission['appearance[template]'], 'classic');
    assert.equal(submission['appearance[product_layout]'], 'row');
    assert.equal(submission['appearance[button_background]'], '#0d6efd');
    assert.equal(await page.locator('[name="appearance[template]"]:checked').count(), 1);
    await page.getByRole('tab', { name: 'Custom offer text', exact: true }).click();
    const textWidths = await page.locator('#co-panel-text').evaluate(panel => ({
        panel: panel.getBoundingClientRect().width,
        short: panel.querySelector('[name="appearance[badge_text]"]').getBoundingClientRect().width,
        long: panel.querySelector('[name="appearance[description_text]"]').getBoundingClientRect().width,
    }));
    assert.ok(textWidths.short < textWidths.long / 1.8, 'Short copy fields share a row');
    assert.ok(textWidths.long < textWidths.panel * .8, 'Long copy has a readable bounded width');
    await page.screenshot({ path: 'build/admin-text-desktop.png', fullPage: true });
    await page.locator('[name="appearance[heading_text]"]').fill('An offer for you');
    await page.locator('[name="appearance[description_text]"]').fill('');
    await page.getByRole('tab', { name: 'Appearance', exact: true }).click();
    await page.locator('[name="appearance[heading_size]"]').fill('28');
    await page.locator('[name="appearance[heading_colour]"]').fill('#254a68');
    await page.locator('[name="appearance[heading_background]"]').fill('#e1edf8');
    await page.locator('[name="appearance[image_background]"]').fill('#fff4dc');
    await page.locator('form:has(input[value="settings"]) button').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission['appearance[heading_text]'], 'An offer for you');
    assert.equal(submission['appearance[description_text]'], '');
    assert.equal(submission['appearance[heading_size]'], '28');
    assert.equal(submission['appearance[heading_colour]'], '#254a68');
    assert.equal(submission['appearance[heading_background]'], '#e1edf8');
    assert.equal(submission['appearance[image_background]'], '#fff4dc');
    assert.equal(submission.admin_tab, 'appearance');
    await page.getByRole('tab', { name: 'Tiers & products', exact: true }).click();
    const productForm = page.locator('form:has(input[value="save_product"])').first();
    for (const width of [1440, 1024, 375]) {
        await page.setViewportSize({ width, height: 1000 });
        assert.equal(await page.locator('.co-admin-tier').last().evaluate(node => getComputedStyle(node).borderBottomWidth), '1px');
        const actions = await page.locator('.co-admin-actions').first().locator('button').evaluateAll(nodes => nodes.map(node => node.getBoundingClientRect().top));
        assert.ok(Math.abs(actions[0] - actions[1]) < 1, 'Save and Delete share a row at ' + width);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        if (width >= 1024) {
            const gap = await page.evaluate(() => document.querySelector('#maximum').getBoundingClientRect().left - document.querySelector('#minimum').getBoundingClientRect().right);
            assert.ok(gap >= 0 && gap <= 17, 'Basket bounds have a compact gap');
        }
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    assert.equal(await productForm.locator('.co-admin-product-picker').evaluate(node => getComputedStyle(node).display), 'grid');
    await page.screenshot({ path: 'build/admin-offers-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 375, height: 812 });
    assert.ok(await productForm.locator('[data-co-product]').evaluate(node => node.getBoundingClientRect().width > 200), 'Mobile product selector fills its row');
    await page.screenshot({ path: 'build/admin-offers-mobile.png', fullPage: true });
    await page.setViewportSize({ width: 1440, height: 1000 });
    const newProduct = page.locator('#co-save-product-0');
    assert.equal(await newProduct.locator('[data-co-category]').inputValue(), '');
    assert.equal(await newProduct.locator('[data-co-product] option').count(), 1, 'New rule starts without products');
    await newProduct.locator('[data-co-category]').selectOption('10');
    assert.equal(await newProduct.locator('[data-co-product] option').count(), 3);
    assert.equal(await productForm.locator('[data-co-category]').inputValue(), '10', 'Saved product preselects its category');
    assert.equal(await productForm.locator('[name="products_id"] option[value="2"]').count(), 1, 'Linked product appears only once');
    assert.equal(await productForm.locator('[name="products_id"] option[value="2"]').textContent(), 'Lime <fresh> (#2)');
    await productForm.locator('[data-co-category]').selectOption('20');
    assert.equal(await productForm.locator('[name="products_id"]').inputValue(), '2', 'Linked product remains selected in either category');
    assert.equal(await productForm.locator('[name="products_id"] option[value="3"]').count(), 0);
    await productForm.locator('[data-co-category]').selectOption('10');
    await productForm.locator('[name="products_id"]').selectOption('3');
    await productForm.locator('[data-co-category]').selectOption('20');
    assert.equal(await productForm.locator('[name="products_id"]').inputValue(), '', 'Category change clears an incompatible selection');
    await productForm.locator('[data-co-category]').selectOption('');
    assert.equal(await productForm.locator('[name="products_id"] option').count(), 1, 'No products until a category is selected');
    await productForm.locator('[data-co-category]').selectOption('0');
    assert.equal(await productForm.locator('[name="products_id"] option[value="4"]').count(), 1, 'Uncategorised products require their category');
    await productForm.locator('[data-co-category]').selectOption('10');
    await productForm.locator('[name="products_id"]').selectOption('2');
    await page.locator('button[form="co-save-tier"]').click();
    assert.equal(await page.evaluate(() => window.submission.action), 'save_tier');
    await page.locator('.co-admin-actions').first().getByRole('button', { name: 'Delete', exact: true }).click();
    assert.equal(await page.evaluate(() => window.submission.action), 'delete_tier');

    await page.locator('button[form="co-save-product-1"]').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission.products_id, '2');
    assert.equal(submission.tier_id, '1');
    assert.equal(submission.item_id, '1');
    assert.equal(submission.mode, 'percent');
    assert.equal(submission.value, '25');
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.getByRole('tab', { name: 'User manual', exact: true }).click();
    assert.equal(await page.locator('[data-co-panel]:visible').count(), 1);
    assert.equal(await page.locator('form:has(input[value="settings"])').isVisible(), false);
    assert.equal(await page.locator('.co-manual-task').count(), 13);
    assert.equal(await page.locator('.co-manual-nav').count(), 0, 'No duplicated task links');
    assert.equal(await page.locator('.co-manual-welcome').isVisible(), true);
    assert.equal(await page.locator('.co-manual-task[open]').count(), 0);
    assert.ok(await page.locator('#co-panel-manual').evaluate(node => node.getBoundingClientRect().height < 1000), 'Compact initial desktop workspace');
    for (const width of [1920, 1440, 800]) {
        await page.setViewportSize({ width, height: 1000 });
        const columns = await page.locator('.co-manual-grid').evaluate(node => {
            const topics = node.querySelector('.co-manual-topics').getBoundingClientRect();
            const reader = node.querySelector('.co-manual-reader').getBoundingClientRect();
            return { top: reader.top - topics.top, bottom: reader.bottom - topics.bottom };
        });
        assert.ok(Math.abs(columns.top) < 1 && Math.abs(columns.bottom) < 1, 'Manual columns match at ' + width);
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    assert.ok(await page.locator('#co-panel-manual a').evaluateAll(links => links.every(link => link.getAttribute('href').startsWith('#') && document.getElementById(link.hash.slice(1)))));
    await page.locator('#co-panel-manual').evaluate(node => node.scrollIntoView({ block: 'start' }));
    await page.screenshot({ path: 'build/admin-manual-desktop.png' });
    await page.locator('#troubleshooting summary').focus();
    await page.keyboard.press('Enter');
    assert.equal(await page.locator('#troubleshooting').evaluate(node => node.open), true);
    assert.equal(await page.locator('#co-manual-reader').evaluate(node => node === document.activeElement), true);
    assert.equal(await page.locator('#co-manual-reader table').isVisible(), true);
    assert.equal(await page.locator('#co-manual-reader').evaluate(node => {
        node.scrollTop = node.scrollHeight;
        return node.scrollTop > 0 && node.scrollHeight > node.clientHeight;
    }), true, 'Long guides remain scrollable within the matched reader');
    assert.equal(await page.locator('.co-manual-detail:visible').count(), 1);
    await page.locator('#set-basket-value-tiers summary').click();
    assert.equal(await page.locator('#troubleshooting').evaluate(node => node.open), false);
    assert.equal(await page.locator('#troubleshooting .co-manual-detail').count(), 1, 'Previous guide restored intact');
    assert.equal(await page.locator('#co-manual-reader h3').first().textContent(), 'Set basket-value tiers');
    await page.screenshot({ path: 'build/admin-manual-reading-desktop.png' });
    await page.locator('#co-manual-query').fill('stock');
    assert.equal(await page.locator('#set-basket-value-tiers').isVisible(), false);
    assert.equal(await page.locator('.co-manual-welcome').isVisible(), true);
    assert.equal(await page.locator('#troubleshooting').isVisible(), true, 'Search includes full guidance after moving it');
    await page.locator('#co-manual-query').fill('no-such-help-phrase');
    assert.match(await page.locator('.co-manual-search [role="status"]').textContent(), /^0 matching/);
    assert.equal(await page.locator('.co-manual-group:visible').count(), 0);
    await page.locator('#co-manual-query').fill('');
    assert.equal(await page.locator('.co-manual-task:visible').count(), 13);
    await page.locator('.co-manual-welcome a[href="#create-your-first-offer"]').click();
    assert.equal(await page.locator('#co-manual-reader h3').first().textContent(), 'Create your first offer');
    assert.equal(await page.locator('#co-manual-reader').evaluate(node => node === document.activeElement), true);
    assert.equal(await page.locator('.co-manual-task[open]').count(), 1);
    await page.locator('.co-manual-home').click();
    assert.equal(await page.locator('.co-manual-welcome').isVisible(), true);
    assert.equal(await page.locator('.co-manual-task[open]').count(), 0);
    await page.getByRole('tab', { name: 'Maintenance', exact: true }).click();
    assert.equal(await page.getByRole('heading', { name: 'Danger zone' }).isVisible(), true);
    assert.equal(await page.locator('.co-admin-uninstall').evaluate(node => node.open), true);
    const uninstallForm = page.locator('form:has(input[value="uninstall"])');
    assert.equal(await uninstallForm.evaluate(node => node.checkValidity()), false);
    await page.screenshot({ path: 'build/admin-maintenance-desktop.png', fullPage: true });
    await uninstallForm.locator('[name="confirm_remove"]').check();
    assert.equal(await uninstallForm.evaluate(node => node.checkValidity()), true);
    await uninstallForm.locator('button').click();
    submission = await page.evaluate(() => window.submission);
    assert.equal(submission.action, 'uninstall');
    assert.equal(submission.confirm_remove, 'yes');
    assert.equal(submission.formid, 'admin-render-token');
    assert.equal(submission.admin_tab, 'maintenance');
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
    await page.screenshot({ path: 'build/admin-maintenance-mobile.png', fullPage: true });
    await page.getByRole('tab', { name: 'User manual', exact: true }).click();
    await page.locator('#co-panel-manual').evaluate(node => node.scrollIntoView({ block: 'start' }));
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: 'build/admin-manual-mobile.png' });
    await page.locator('#add-products-and-offer-prices summary').click();
    assert.equal(await page.locator('#co-manual-reader').evaluate(node => node === document.activeElement), true);
    assert.equal(await page.locator('#co-manual-reader h3').first().textContent(), 'Add products and offer prices');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: 'build/admin-manual-reading-mobile.png' });
    await page.getByRole('tab', { name: 'Setup', exact: true }).focus();
    await page.keyboard.press('ArrowRight');
    assert.equal(await page.getByRole('tab', { name: 'Offer template', exact: true }).getAttribute('aria-selected'), 'true');
    assert.equal(await page.locator('[name="appearance[heading_text]"]').inputValue(), 'An offer for you');
    const noScriptContext = await browser.newContext({ javaScriptEnabled: false });
    await noScriptContext.route('**/*', route => {
        if (route.request().url().includes('checkout_offer_admin.css')) return route.fulfill({ contentType: 'text/css', body: fs.readFileSync('ext/checkout_offer/checkout_offer_admin.css') });
        return route.fulfill({ contentType: 'text/html', body: fs.readFileSync('build/admin-tier.html', 'utf8').replace('<html>', '<html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + bootstrap + '</style></head>') });
    });
    const fallbackPage = await noScriptContext.newPage();
    await fallbackPage.goto('https://fallback.test/');
    assert.equal(await fallbackPage.locator('[data-co-panel]:visible').count(), 7);
    assert.equal(await fallbackPage.locator('[data-co-category]').first().isDisabled(), true);
    assert.equal(await fallbackPage.locator('[data-co-product]').first().locator('option[value="2"]').count(), 1);
    assert.equal(await fallbackPage.locator('#co-save-product-0 [data-co-product] option').count(), 1, 'No-JavaScript new selector remains empty');
    assert.equal(await fallbackPage.locator('#co-panel-manual #troubleshooting').isVisible(), true);
    await fallbackPage.locator('#troubleshooting summary').click();
    assert.equal(await fallbackPage.locator('#troubleshooting table').isVisible(), true);
    assert.equal(await fallbackPage.locator('.co-manual-search').isVisible(), false);
    assert.equal(await fallbackPage.locator('.co-manual-welcome').isVisible(), true);
    assert.equal(await fallbackPage.locator('.co-manual-task').count(), 13);
    await fallbackPage.locator('#add-products-and-offer-prices summary').click();
    assert.equal(await fallbackPage.locator('#add-products-and-offer-prices .co-manual-detail').isVisible(), true);
    await fallbackPage.setViewportSize({ width: 375, height: 812 });
    assert.ok(await fallbackPage.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.setViewportSize({ width: 320, height: 812 });
    await page.getByRole('tab', { name: 'User manual', exact: true }).click();
    await page.locator('#co-manual-query').fill('pricing');
    await page.locator('#add-products-and-offer-prices summary').click();
    assert.equal(await page.locator('.co-manual-detail:visible').count(), 1);
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    assert.deepEqual(errors, []);
    await browser.close();
    console.log('Admin browser checks passed: local linked logo, scoped styling, responsive layout, appearance and product submission.');
})().catch(error => { console.error(error); process.exitCode = 1; });
