# One-Time Checkout Offer for Phoenix Cart

Version **1.1.2** · Tested CE Phoenix Cart **1.1.0.6 / 1.1.0.8** · PHP **8.1–8.3**

Present discounted products at the payment step of checkout. This independent Phoenix implementation follows [PrestaChamps' published feature description](https://shop.prestachamps.com/en/prestashop-modules/118-one-time-checkout-offer.html). No PrestaShop module code or assets are included.

## Features

- Unlimited basket-value tiers, inclusive minimum and exclusive maximum limits.
- Highest priority wins overlaps; lowest tier ID breaks equal-priority ties.
- Multiple products per tier, with fixed prices or percentage discounts.
- Eligibility includes product VAT, delivery and delivery VAT, in store base currency.
- Inline display or an automatic modal with close, Escape, backdrop dismissal and a reopen button.
- Colours for the panel, text, cards, borders and buttons; solid/outline buttons and shadows.
- Configurable corners, border width, spacing, image height, font size, columns and modal width.
- Header-free modal with a corner close button, plus independent left/centre/right alignment for modal text/buttons and product rows.
- Responsive cards, images and product option selectors.
- One-click addition followed by delivery recalculation.
- Acceptance bound to the customer, currency and exact basket contents.
- Server-side eligibility, stock, options and CSRF checks.
- Discounted order lines, taxes and totals using Phoenix's own order builder.
- Admin installation, editable rules, enable/disable controls and uninstallation.
- BusyBee-branded admin workspace with a linked local logo, status indicator, selected-tier highlighting and clearer product forms.
- English language files, tested installable ZIP and PHP 8.1/8.3 CI.

## Installation

See [INSTALL.md](INSTALL.md). No Phoenix core edits or order-total module installation are required.

## Offer lifecycle

One unit per offered product. Products already in the basket are hidden. Fixed prices exclude tax and include selected option surcharges; percentages apply to the current normal/special price including options. Offers never increase the normal price.

First acceptance locks the eligible tier for that exact basket. Adding an offer or recalculating delivery cannot move the customer into a different tier. Remaining products from that tier can also be accepted. Confirmation and order processing recalculate prices on the server. Disabled/deleted tiers or product rules no longer grant discounts.

Changing basket contents, quantity, options, customer or currency invalidates acceptance. Products remain at their normal prices and checkout must be reviewed again. Completing the order empties the basket and expires acceptance. There is no global special, coupon or catalogue price change. Historical orders retain their saved prices after uninstall.

Choose **Reports → Checkout Offers → Setup → Offer display** and save. Inline is the default. Modal opens once per eligible basket in the current browser tab; customers can dismiss it or reopen it with **View checkout offers**. Expand **Appearance** to customise either display. Existing installations receive the new settings when Setup is saved, preserving tiers and products.

Under Appearance, **Modal text and button alignment** and **Modal product row alignment** each offer Left, Centre and Right. Product row alignment positions incomplete rows within the configured columns. Alignment affects the modal only. The former accent setting and coloured top border have been removed; saving Setup cleans up the obsolete setting.

JavaScript moves inline offers above payment methods. Without JavaScript or native dialog support, offers remain inline and product addition still works. Modal fields stay inside Phoenix's payment form. Themes must retain the standard payment form and hooks. Alternate checkouts and payment gateways that bypass Phoenix order construction require integration testing.

## Validation

```powershell
php tests/run.php ../tmp/PhoenixCart-reference
php tests/admin_render.php ../tmp/PhoenixCart-reference install
php tests/admin_render.php ../tmp/PhoenixCart-reference setup
php tests/admin_render.php ../tmp/PhoenixCart-reference tier
node --test tests/storefront.test.js
node --check ext/checkout_offer/checkout_offer.js
php tests/run.php ../tmp/PhoenixCart-reference --render
php tests/run.php ../tmp/PhoenixCart-reference --render --modal --styled
node tests/browser.cjs
node tests/admin_browser.cjs
git diff --check
powershell -File scripts/build_package.ps1 -Version 1.1.2
```

The harness uses real Phoenix order/tax/currency classes with deterministic database/cart fixtures, without defining legacy image constants. CI repeats checks on PHP 8.1 and 8.3 against pinned Phoenix 1.1.0.6 and 1.1.0.8 references. Browser checks require Playwright 1.62.1, Bootstrap 5.3.8 and Chromium (`npm install --no-save --package-lock=false playwright@1.62.1 bootstrap@5.3.8`, then `npx playwright install chromium`). No live store or payment capture was tested. See [TESTING.md](TESTING.md) for staging checks.

GPL-3.0-or-later. See [LICENSE](LICENSE).
