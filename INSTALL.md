# Installation

1. Back up store files and database. Use staging first.
2. Extract the release ZIP. Copy `includes/`, `ext/` and `images/` into the store root and `admin/` contents into your actual admin directory (including a renamed admin). All are new files; no Phoenix files should be replaced.
3. Open **Reports → Checkout Offers** in administration, or open `checkout_offer.php` inside your admin directory. Grant access through the store's normal administrator permissions.
4. Click **Install database tables**. Repeat installation preserves existing rules. Creates `checkout_offer_tiers`, `checkout_offer_products` and three settings.
5. Create a titled tier with minimum, optional maximum and priority. Minimum is inclusive, maximum exclusive. Amounts use base currency and include VAT and delivery. Higher priority wins. A 50–100 tier covers baskets of at least 50 and less than 100.
6. Attach active product IDs. Choose fixed prices excluding tax or percentages from 0 to 100. Fixed prices include all selected options. Ensure sufficient stock.
7. Select inline or modal under **Setup → Offer display**. Expand **Appearance** for colours, buttons, shadows, borders, spacing, image size, font size, columns and modal width. Enable offers, save Setup and run TESTING.md staging checks.

## Updating and uninstalling

To update, replace only add-on files; do not uninstall first. To uninstall, disable offers, expand Uninstall, check its confirmation and submit. Historical orders are preserved. Remove uploaded files under `admin/`, `includes/`, `ext/` and `images/` listed in `package-manifest.txt`. Documentation need not be uploaded.

For v1.1.0, upload the new `ext/checkout_offer/checkout_offer.css` along with updated add-on PHP, language and JavaScript files. Save Setup to create the new settings. Existing installations keep inline display until modal is selected. Modal opens once per basket in the current browser tab; blocked storage may cause it to open on each visit. Without JavaScript or native dialog support, offers remain inline.

For v1.1.1, upload the updated add-on files and save Setup. Appearance includes independent modal text/button and product row alignment (Left, Centre or Right). The modal's separate header strip is removed and the close × remains in its top-right corner. Accent colour styling is removed immediately; saving Setup removes the old accent setting. Existing rules and other appearance values are preserved.

For v1.1.2, also upload the new `ext/checkout_offer/checkout_offer_admin.css` and `images/checkout_offer/busybee-logo.png`. The admin page uses an orange/yellow BusyBee header with a logo linking to BusyBee Commerce. Asset URLs use Phoenix's catalogue link builder, supporting renamed admin directories and stores in subdirectories. No database update or reinstallation is required.

For v1.2.0, upload the updated add-on PHP/language, CSS and JavaScript files. Select a design under **Setup → Appearance → Offer template** and save. Existing installations default to Custom / Classic, preserving their appearance. Styled templates use their own colour palette and compact product rows; choosing Classic restores manual appearance settings. No database migration or reinstallation is required.

For v1.2.1, upload the updated add-on files, particularly the checkout offer hook, site-wide hook and stylesheet. Styled Add buttons now fit their labels. The modal image/text/button alignment setting also positions thumbnails: Centre and Right show the image above the details at the chosen position; Left retains compact image-left rows. Existing saved alignment settings apply automatically. No database changes are required.

When updating from v1.0.0, overwrite `admin/includes/boxes/checkout_offer.php` as well as uploading the new `reports_checkout_offer.php` box and its language file. The old file is intentionally retained as an empty compatibility file to remove the separate top-level menu. Existing tiers/settings are preserved.

## Integration contract

Phoenix 1.1.0.8 reference commit: `1c2161d0f1f605724efa058ecffe66f6f4bf0e55`. Requires `cartOrderBuild`, `injectAppTop`, `injectRedirects`, `injectFormDisplay`, `injectSiteStart`, `injectBodyEnd`, and the standard tokenised payment form. Uses Phoenix cart/order APIs; no core edits or custom order-total module.

Phoenix 1.1.0.6 is also tested at reference commit `69ba8de85ec03c809a14f0bb2c7ca9fa5295a347`. Offer images use the standard `images/` path; no `DIR_WS_IMAGES` constant is required. To fix the v1.0.1 checkout rendering error, replace `includes/hooks/shop/checkout_payment/checkoutOffer.php` in the store root with the v1.0.2 file. No reinstallation or settings changes are needed.

For v1.3.0, upload the updated add-on files. Eligible modal offers open on each payment-page entry or reload, with no View button. Styled designs have richer banners and larger imagery. No database changes are required; saved templates and settings remain valid.

For v1.3.1, upload the updated stylesheet, JavaScript and site-wide checkout offer hook. All predefined templates use cleaner solid banners and restrained typography. Modal footer strips are removed, while the close and continue-checkout buttons remain. Inline offers retain their availability note. No reinstallation or settings changes are required.

For v1.4.0, upload the updated add-on files, including the admin stylesheet and language file. Under **Setup → Appearance → Product layout**, choose **Row** or **Stacked** and save. Row uses **Desktop columns** and wraps to two columns on tablets and one on phones. Stacked shows full-width product cards. Both apply to inline offers and modals, for every template. Existing installations retain their current layout until saved; no reinstallation is required.

For v1.5.0, upload the updated add-on files. Appearance now offers six additional designs: Ocean blue, Soft plum, Slate minimal, Warm coral, Clean teal and Champagne. To unselect a predefined design, choose **No template / Custom** and save Setup. Your custom colours and existing layout settings are retained. No database migration or reinstallation is required.

For v1.6.0, upload the updated add-on files, including both stylesheets, JavaScript, admin language file and checkout hooks. Expand **Setup → Custom offer text** to edit wording; leave any field blank for the predefined text. In **Appearance**, adjust heading/description size, colour, weight and style. Size 0, blank colour and Automatic weight retain the design defaults. Styled Add buttons append the offer price. No database migration or reinstallation is required.

For v1.7.0, also upload the new `ext/checkout_offer/checkout_offer_admin.js`. Administration now uses tabs for Setup, Offer template, Appearance, Custom offer text, Tiers & products and Maintenance. Settings stay editable across tabs; Save stores them together. Tier links open Tiers & products, and saving returns to the active tab. Without JavaScript all sections remain available. Upload the updated storefront stylesheet and checkout hook for aligned row prices/buttons. No migration or reinstall is required.
