'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');
let browser;

(async function () {
    const bootstrap = fs.readFileSync(process.env.CHECKOUT_OFFER_BOOTSTRAP_CSS || require.resolve('bootstrap/dist/css/bootstrap.min.css'), 'utf8');
    const css = fs.readFileSync('ext/checkout_offer/checkout_offer.css', 'utf8');
    const js = fs.readFileSync('ext/checkout_offer/checkout_offer.js', 'utf8');
    browser = await chromium.launch({ headless: true, ...(process.env.CHECKOUT_OFFER_BROWSER ? { channel: process.env.CHECKOUT_OFFER_BROWSER } : {}) });
    const page = await browser.newPage();
    await page.route('http://alignment.test/**', route => {
        if (route.request().url().endsWith('.png')) return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><circle cx="40" cy="40" r="30" fill="green"/></svg>' });
        const fixture = new URL(route.request().url()).pathname.slice(1);
        return route.fulfill({ contentType: 'text/html', body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + bootstrap + 'body{margin:24px}' + css + '</style>' + fs.readFileSync('build/storefront-' + fixture + '.html', 'utf8') + '<script>' + js + '</script>' });
    });
    let cases = 0;
    for (const theme of ['classic', 'red', 'honey', 'midnight', 'green', 'ocean', 'plum', 'slate', 'coral', 'teal', 'champagne']) {
        for (const mode of ['inline', 'modal']) {
            await page.goto('http://alignment.test/' + mode + (theme === 'classic' ? '' : '-' + theme));
            if (mode === 'modal') await page.locator('dialog').evaluate(node => Promise.all(node.getAnimations().map(animation => animation.finished)));
            for (const width of [1736, 1280, 768, 575, 375]) {
                await page.setViewportSize({ width, height: 1000 });
                for (const alignment of ['left', 'center', 'right']) {
                    await page.locator('#checkout-offer').evaluate((node, value) => {
                        node.dataset.contentAlignment = value;
                        node.dataset.productLayout = 'row';
                        node.style.setProperty('--co-content-alignment', value);
                        node.style.setProperty('--co-products-alignment', value);
                        node.style.setProperty('--co-columns', '4');
                    }, alignment);
                    const section = page.locator('#checkout-offer');
                    assert.equal(await section.locator('h2').evaluate(node => getComputedStyle(node).textAlign), alignment);
                    const cards = await section.locator('.checkout-offer-card').evaluateAll(nodes => nodes.map(node => {
                        const style = getComputedStyle(node);
                        const card = node.getBoundingClientRect();
                        const button = node.querySelector('button').getBoundingClientRect();
                        const price = node.querySelector('.checkout-offer-prices').getBoundingClientRect();
                        const grid = node.parentElement.getBoundingClientRect();
                        const gridStyle = getComputedStyle(node.parentElement);
                        return { top: card.top, priceTop: price.top, buttonBottom: button.bottom,
                            textAlign: getComputedStyle(node.querySelector('h3')).textAlign,
                            imagePosition: getComputedStyle(node.querySelector('img')).objectPosition,
                            left: card.left + parseFloat(style.paddingLeft) + 1,
                            right: card.right - parseFloat(style.paddingRight) - 1,
                            center: (card.left + card.right) / 2,
                            buttonLeft: button.left, buttonRight: button.right, buttonCenter: (button.left + button.right) / 2,
                            gridLeft: grid.left + parseFloat(gridStyle.paddingLeft),
                            gridRight: grid.right - parseFloat(gridStyle.paddingRight), cardLeft: card.left, cardRight: card.right };
                    }));
                    for (const card of cards) {
                        assert.equal(card.textAlign, alignment);
                        assert.equal(card.imagePosition, { left: '0% 50%', center: '50% 50%', right: '100% 50%' }[alignment]);
                        if (alignment === 'left') assert.ok(Math.abs(card.buttonLeft - card.left) < 2);
                        if (alignment === 'center') assert.ok(Math.abs(card.buttonCenter - card.center) < 2);
                        if (alignment === 'right') assert.ok(Math.abs(card.buttonRight - card.right) < 2);
                    }
                    if (Math.abs(cards[0].top - cards[1].top) < 1) {
                        assert.ok(Math.abs(cards[0].priceTop - cards[1].priceTop) < 1, 'Prices align with and without options');
                        assert.ok(Math.abs(cards[0].buttonBottom - cards[1].buttonBottom) < 1, 'Buttons align with and without options');
                        const leftGap = cards[0].cardLeft - cards[0].gridLeft;
                        const rightGap = cards[1].gridRight - cards[1].cardRight;
                        if (alignment === 'left') assert.ok(leftGap < 2);
                        if (alignment === 'center') assert.ok(Math.abs(leftGap - rightGap) < 2);
                        if (alignment === 'right') assert.ok(rightGap < 2);
                    }
                    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
                    cases++;
                }
            }
        }
    }
    await browser.close();
    console.log('Alignment browser checks passed: ' + cases + ' Classic/template, inline/modal, responsive alignment cases.');
})().catch(async error => {
    console.error(error);
    if (browser) await browser.close();
    process.exitCode = 1;
});
