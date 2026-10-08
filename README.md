# One-Time Checkout Offer for Phoenix Cart

Version **1.0.1** · CE Phoenix Cart **1.1.0.8** · PHP **8.1–8.3**

Present discounted products at the payment step of checkout. This independent Phoenix implementation follows [PrestaChamps' published feature description](https://shop.prestachamps.com/en/prestashop-modules/118-one-time-checkout-offer.html). No PrestaShop module code or assets are included.

## Features

- Unlimited basket-value tiers, inclusive minimum and exclusive maximum limits.
- Highest priority wins overlaps; lowest tier ID breaks equal-priority ties.
- Multiple products per tier, with fixed prices or percentage discounts.
- Eligibility includes product VAT, delivery and delivery VAT, in store base currency.
- Configurable accent colour, responsive cards, images and product option selectors.
- One-click addition followed by delivery recalculation.
- Acceptance bound to the customer, currency and exact basket contents.
- Server-side eligibility, stock, options and CSRF checks.
- Discounted order lines, taxes and totals using Phoenix's own order builder.
- Admin installation, editable rules, enable/disable controls and uninstallation.
- English language files, tested installable ZIP and PHP 8.1/8.3 CI.

## Installation

See [INSTALL.md](INSTALL.md). No Phoenix core edits or order-total module installation are required.

## Offer lifecycle

One unit per offered product. Products already in the basket are hidden. Fixed prices exclude tax and include selected option surcharges; percentages apply to the current normal/special price including options. Offers never increase the normal price.

First acceptance locks the eligible tier for that exact basket. Adding an offer or recalculating delivery cannot move the customer into a different tier. Remaining products from that tier can also be accepted. Confirmation and order processing recalculate prices on the server. Disabled/deleted tiers or product rules no longer grant discounts.

Changing basket contents, quantity, options, customer or currency invalidates acceptance. Products remain at their normal prices and checkout must be reviewed again. Completing the order empties the basket and expires acceptance. There is no global special, coupon or catalogue price change. Historical orders retain their saved prices after uninstall.

JavaScript moves the offer block above payment methods. Without JavaScript, it remains before Continue and product addition still works. Themes must retain the standard payment form and hooks. Alternate checkouts and payment gateways that bypass Phoenix order construction require integration testing.

## Validation

```powershell
php tests/run.php ../tmp/PhoenixCart-reference
php tests/admin_render.php ../tmp/PhoenixCart-reference install
php tests/admin_render.php ../tmp/PhoenixCart-reference setup
php tests/admin_render.php ../tmp/PhoenixCart-reference tier
node --test tests/storefront.test.js
node --check ext/checkout_offer/checkout_offer.js
git diff --check
powershell -File scripts/build_package.ps1 -Version 1.0.1
```

The harness uses real Phoenix order/tax/currency classes with deterministic database/cart fixtures. CI repeats checks on PHP 8.1 and 8.3 against the pinned reference. No live store or payment capture was tested. See [TESTING.md](TESTING.md) for staging checks.

GPL-3.0-or-later. See [LICENSE](LICENSE).
