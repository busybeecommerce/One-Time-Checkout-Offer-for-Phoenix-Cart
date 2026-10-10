'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async function () {
    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage({ reducedMotion: 'reduce' });
        const bootstrap = fs.readFileSync(process.env.CHECKOUT_OFFER_BOOTSTRAP_CSS || require.resolve('bootstrap/dist/css/bootstrap.min.css'), 'utf8');
        const css = fs.readFileSync('ext/checkout_offer/checkout_offer.css', 'utf8');
        const js = fs.readFileSync('ext/checkout_offer/checkout_offer.js', 'utf8');
        await page.route('http://appearance.test/**', route => {
            if (route.request().url().endsWith('.png')) return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="140"><rect width="200" height="140" fill="green"/></svg>' });
            const fixture = new URL(route.request().url()).pathname.slice(1);
            return route.fulfill({ contentType: 'text/html', body: '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + bootstrap + css + '</style>' + fs.readFileSync('build/storefront-' + fixture + '.html', 'utf8') + '<script>' + js + '</script>' });
        });
        for (const fixture of ['inline-extended', 'modal-extended', 'inline-red-extended', 'modal-red-extended']) {
            await page.goto('http://appearance.test/' + fixture);
            const section = page.locator('#checkout-offer');
            await section.evaluate(node => { node.dataset.productLayout = 'row'; });
            assert.equal(await section.getAttribute('data-button-size'), 'large');
            assert.equal(await section.getAttribute('data-button-width'), 'full');
            assert.equal(await section.locator('img').first().evaluate(node => getComputedStyle(node).objectFit), 'cover');
            const button = section.locator('.checkout-offer-add').first();
            assert.equal(await button.evaluate(node => getComputedStyle(node).borderRadius), '20px');
            assert.equal(await section.locator('.checkout-offer-prices').first().evaluate(node => getComputedStyle(node).textAlign), 'right');
            assert.notEqual(await button.evaluate(node => getComputedStyle(node).boxShadow), 'none');
            assert.notEqual(await section.locator('.checkout-offer-card').first().evaluate(node => getComputedStyle(node).boxShadow), 'none');
            for (const [width, columns] of [[740, 3], [540, 2], [375, 2]]) {
                await page.setViewportSize({ width, height: 1000 });
                const bounds = await section.locator('.checkout-offer-grid').evaluate(node => ({ columns: getComputedStyle(node).gridTemplateColumns.split(' ').length, right: node.getBoundingClientRect().right }));
                assert.equal(bounds.columns, columns, fixture + ' columns at ' + width);
                assert.ok(bounds.right <= width, 'Grid stays within viewport');
                assert.ok(await button.evaluate(node => Math.abs(node.getBoundingClientRect().width - node.closest('.checkout-offer-card').clientWidth + 2 * parseFloat(getComputedStyle(node.closest('.checkout-offer-card')).paddingLeft)) < 2), 'Full-width button spans card content');
            }
            await page.setViewportSize({ width: 1280, height: 1000 });
            await button.hover();
            await page.waitForFunction(() => getComputedStyle(document.querySelector('.checkout-offer-add')).backgroundColor === 'rgb(35, 69, 103)');
            assert.equal(await button.evaluate(node => getComputedStyle(node).backgroundColor), 'rgb(35, 69, 103)');
            assert.equal(await button.evaluate(node => getComputedStyle(node).color), 'rgb(171, 205, 239)');
            await section.evaluate(node => { node.dataset.buttonWidth = 'auto'; node.dataset.buttonAlignment = 'right'; node.dataset.priceAlignment = 'left'; node.style.setProperty('--co-price-alignment', 'left'); node.dataset.cardShadow = 'none'; node.dataset.buttonShadow = 'none'; });
            assert.equal(await section.locator('.checkout-offer-prices').first().evaluate(node => getComputedStyle(node).textAlign), 'left');
            assert.equal(await button.evaluate(node => getComputedStyle(node).justifySelf), 'end');
            assert.equal(await button.evaluate(node => getComputedStyle(node).boxShadow), 'none');
            assert.equal(await section.locator('.checkout-offer-card').first().evaluate(node => getComputedStyle(node).boxShadow), 'none');
            const sizes = [];
            for (const size of ['small', 'medium', 'large']) {
                await section.evaluate((node, value) => { node.dataset.buttonSize = value; }, size);
                sizes.push(await button.evaluate(node => parseFloat(getComputedStyle(node).fontSize)));
            }
            assert.ok(sizes[0] < sizes[1] && sizes[1] < sizes[2], 'Button sizes increase');
            await section.evaluate(node => { node.style.setProperty('--co-image-fit', 'contain'); node.dataset.productLayout = 'stacked'; });
            assert.equal(await section.locator('img').first().evaluate(node => getComputedStyle(node).objectFit), 'contain');
            assert.equal(await section.locator('.checkout-offer-grid').evaluate(node => getComputedStyle(node).flexDirection), 'column');
            for (const alignment of ['left', 'center', 'right']) {
                await section.evaluate((node, value) => { node.dataset.buttonAlignment = value; }, alignment);
                const aligned = await button.evaluate(node => {
                    const button = node.getBoundingClientRect();
                    const content = node.closest('.checkout-offer-card-content').getBoundingClientRect();
                    return { left: button.left - content.left, right: content.right - button.right };
                });
                if (alignment === 'left') assert.ok(Math.abs(aligned.left) < 1, 'Stacked button aligns left');
                if (alignment === 'right') assert.ok(Math.abs(aligned.right) < 1, 'Stacked button aligns right');
                if (alignment === 'center') assert.ok(Math.abs(aligned.left - aligned.right) < 1, 'Stacked button centers');
            }
            await page.screenshot({ path: 'build/appearance-' + fixture + '.png', fullPage: true });
        }
        console.log('Extended appearance browser checks passed: responsive columns, image fit, alignment, buttons, hover colours and shadows.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
