# One-Time Checkout Offer for Phoenix Cart

Version **1.7.18** · Tested CE Phoenix Cart **1.1.0.6 / 1.1.0.8** · PHP **8.1–8.3**

Present discounted products at the payment step of checkout. This independent Phoenix implementation follows [PrestaChamps' published feature description](https://shop.prestachamps.com/en/prestashop-modules/118-one-time-checkout-offer.html). No PrestaShop module code or assets are included.

## Features

- Unlimited basket-value tiers, inclusive minimum and exclusive maximum limits.
- Highest priority wins overlaps; lowest tier ID breaks equal-priority ties.
- Multiple products per tier, with fixed prices or percentage discounts.
- Eligibility includes product VAT, delivery and delivery VAT, in store base currency.
- Inline display or an automatic modal with close, Escape, backdrop dismissal and a continue-checkout button.
- Colours for the panel, text, cards, borders and buttons; solid/outline buttons and shadows.
- Configurable corners, border width, spacing, image height, font size, columns and modal width.
- Header-free modal with a corner close button, plus independent left/centre/right alignment for inline/modal content and product rows.
- Ten selectable designs: Red special offer, BusyBee honey, Midnight, Fresh green, Ocean blue, Soft plum, Slate minimal, Warm coral, Clean teal and Champagne templates with banners, compact product rows, savings badges and price-labelled Add buttons.
- Clean solid-colour banners and readable typography; modals keep the continue-checkout button without separate footer strips.
- Custom heading, description, badge, button labels, savings label and inline note with predefined wording as a per-field fallback.
- Heading/description size, colour, weight and normal/italic styling with design-preserving automatic defaults.
- Compact, centred modal close icon and thin border.
- Balanced row cards with aligned prices and Add buttons, including products with options.
- Responsive cards, images and product option selectors.
- Row or Stacked product layout for every template, with responsive wrapping and aligned admin appearance fields.
- One-click addition followed by delivery recalculation.
- Acceptance bound to the customer, currency and exact basket contents.
- Server-side eligibility, stock, options and CSRF checks.
- Discounted order lines, taxes and totals using Phoenix's own order builder.
- Admin installation, editable rules, enable/disable controls and uninstallation.
- BusyBee-branded tabbed admin workspace with white header text, a linked logo, status indicator and selected-tier highlighting. Setup, templates, appearance, text, tiers/products, maintenance and User manual have separate panels; unsaved edits survive tab switches.
- English language files, tested installable ZIP and PHP 8.1/8.3 CI.

## Installation

See [INSTALL.md](INSTALL.md). No Phoenix core edits or order-total module installation are required.

For step-by-step administration, pricing examples, appearance controls and troubleshooting, open **Catalog → Checkout Offers → User manual** for grouped topics, a three-step quick start, a selected-guide reading pane and local search, or read [USER_MANUAL.md](USER_MANUAL.md) from the release ZIP.

## Offer lifecycle

One unit per offered product. Products already in the basket are hidden. Fixed prices exclude tax and include selected option surcharges; percentages apply to the current normal/special price including options. Offers never increase the normal price.

First acceptance locks the eligible tier for that exact basket. Adding an offer or recalculating delivery cannot move the customer into a different tier. Remaining products from that tier can also be accepted. Confirmation and order processing recalculate prices on the server. Disabled/deleted tiers or product rules no longer grant discounts.

Changing basket contents, quantity, options, customer or currency invalidates acceptance. Products remain at their normal prices and checkout must be reviewed again. Completing the order empties the basket and expires acceptance. There is no global special, coupon or catalogue price change. Historical orders retain their saved prices after uninstall.

Choose **Catalog → Checkout Offers → Setup → Offer display** and save. Inline is the default. Modal opens on every entry (including a cached history return) or reload of an eligible payment page. Customers can dismiss it with ×, Escape, the backdrop or **No thanks, continue checkout**; it stays closed until the next page entry. Focus returns to the first usable payment form control. There is no launcher or browser-storage suppression. Open the **Appearance** tab to customise either display. Existing installations receive the new settings when Setup is saved, preserving tiers and products.

Under Appearance, **Image, text and button alignment** and **Product row alignment** each offer Left, Centre and Right in inline and modal displays. Product row alignment positions incomplete rows within the configured columns. In styled Stacked layouts, Left keeps the compact image-left layout; Centre and Right put the image above the details at the chosen position. Row cards share layout tracks so their prices, savings and buttons remain aligned when options or text wrap. The former accent setting and coloured top border have been removed; saving Setup cleans up the obsolete setting.

Choose a design in the **Offer template** tab, then save. **No template / Custom** unselects the predefined design and restores your manual colour/button/column settings when Setup is saved. This choice retains the existing Classic configuration identifier, so saved settings remain compatible. Styled templates supply their own colours, solid buttons and thumbnail sizes. Choose Product layout: Row places cards side by side using Desktop columns; Stacked places one full-width card on each row. Rows wrap responsively. Existing Classic rows and styled stacks are preserved until a layout is saved. Spacing, corners, text size, modal width and content alignment remain configurable. Templates work inline and in modals; the classic modal remains header-free while styled designs include a promotional banner. Savings and button prices update with selected options, tax display and currency, and also render without JavaScript.

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
foreach ($template in @('red','honey','midnight','green','ocean','plum','slate','coral','teal','champagne')) {
  php tests/run.php ../tmp/PhoenixCart-reference --render --template=$template
  php tests/run.php ../tmp/PhoenixCart-reference --render --modal --template=$template
}
node tests/templates_browser.cjs
node tests/alignment_browser.cjs
git diff --check
powershell -File scripts/build_package.ps1 -Version 1.7.18
```

The harness uses real Phoenix order/tax/currency classes with deterministic database/cart fixtures, without defining legacy image constants. CI repeats checks on PHP 8.1 and 8.3 against pinned Phoenix 1.1.0.6 and 1.1.0.8 references. Browser checks require Playwright 1.62.1, Bootstrap 5.3.8 and Chromium (`npm install --no-save --package-lock=false playwright@1.62.1 bootstrap@5.3.8`, then `npx playwright install chromium`). No live store or payment capture was tested. See [TESTING.md](TESTING.md) for staging checks.

GPL-3.0-or-later. See [LICENSE](LICENSE).
