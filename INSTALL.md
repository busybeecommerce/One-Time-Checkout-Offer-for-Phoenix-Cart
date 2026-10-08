# Installation

1. Back up store files and database. Use staging first.
2. Extract the release ZIP. Copy `includes/` and `ext/` into the store root and `admin/` contents into your actual admin directory (including a renamed admin). All are new files; no Phoenix files should be replaced.
3. Open **Reports → Checkout Offers** in administration, or open `checkout_offer.php` inside your admin directory. Grant access through the store's normal administrator permissions.
4. Click **Install database tables**. Repeat installation preserves existing rules. Creates `checkout_offer_tiers`, `checkout_offer_products` and two settings.
5. Create a titled tier with minimum, optional maximum and priority. Minimum is inclusive, maximum exclusive. Amounts use base currency and include VAT and delivery. Higher priority wins. A 50–100 tier covers baskets of at least 50 and less than 100.
6. Attach active product IDs. Choose fixed prices excluding tax or percentages from 0 to 100. Fixed prices include all selected options. Ensure sufficient stock.
7. Choose an accent colour, enable offers and run TESTING.md staging checks.

## Updating and uninstalling

To update, replace only add-on files; do not uninstall first. To uninstall, disable offers, expand Uninstall, check its confirmation and submit. Historical orders are preserved. Remove uploaded files under `admin/`, `includes/` and `ext/` listed in `package-manifest.txt`. Documentation need not be uploaded.

When updating from v1.0.0, overwrite `admin/includes/boxes/checkout_offer.php` as well as uploading the new `reports_checkout_offer.php` box and its language file. The old file is intentionally retained as an empty compatibility file to remove the separate top-level menu. Existing tiers/settings are preserved.

## Integration contract

Phoenix 1.1.0.8 reference commit: `1c2161d0f1f605724efa058ecffe66f6f4bf0e55`. Requires `cartOrderBuild`, `injectAppTop`, `injectRedirects`, `injectFormDisplay`, `injectBodyEnd`, and the standard tokenised payment form. Uses Phoenix cart/order APIs; no core edits or custom order-total module.
