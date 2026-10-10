# User manual

For store owners and administrators. Open **Catalog → Checkout Offers → User manual** to read this guide inside administration.

The add-on shows eligible customers discounted products at the payment step of checkout. You choose the basket-value ranges, products, offer prices and presentation. Customers can add one unit of each offered product to their current order or continue without accepting an offer.

Choose a topic from the grouped guides. With JavaScript, the selected guide appears in the reading pane; search finds headings, field names and full guidance. Without JavaScript, expand a topic to read it in place. Reading help does not save or discard unsaved settings.

## Contents

1. [Install and open the add-on](#install-and-open-the-add-on)
2. [Create your first offer](#create-your-first-offer)
3. [Use the administration tabs](#use-the-administration-tabs)
4. [Set basket-value tiers](#set-basket-value-tiers)
5. [Add products and offer prices](#add-products-and-offer-prices)
6. [Choose inline or modal display](#choose-inline-or-modal-display)
7. [Choose a template](#choose-a-template)
8. [Customise appearance and alignment](#customise-appearance-and-alignment)
9. [Change the wording](#change-the-wording)
10. [Understand the customer checkout journey](#understand-the-customer-checkout-journey)
11. [Check an offer before enabling it](#check-an-offer-before-enabling-it)
12. [Troubleshooting](#troubleshooting)
13. [Disable or remove the add-on](#disable-or-remove-the-add-on)

## Quick start

1. **Build your offer** — [Create a tier and add a product](#create-your-first-offer).
2. **Make it yours** — [Choose a template](#choose-a-template) and [adjust appearance](#customise-appearance-and-alignment).
3. **Test, then enable** — [Check checkout on staging](#check-an-offer-before-enabling-it), then enable offers in Setup.

Select a guide for full instructions. Tiers/products and global settings have separate Save buttons.

## Install and open the add-on

The add-on requires Phoenix's standard checkout payment form and hooks. Installation does not require Phoenix core edits or an order-total module.

1. Back up your store files and database, and install on a staging copy first.
2. Extract the release ZIP. Copy its `includes/`, `ext/` and `images/` folders into your store root. Copy the contents of its `admin/` folder into your actual administration directory, including when you have renamed that directory.
3. Open **Catalog → Checkout Offers**. If necessary, grant the administrator access through your store's normal administrator permissions. You can also open `checkout_offer.php` inside your administration directory.
4. Click **Install database tables** if the installation screen appears.
5. Create your tiers and products before enabling offers.

Documentation files can stay on your computer; they do not need to be uploaded to the public store. See [INSTALL.md](INSTALL.md) for installation and upgrade details.

## Create your first offer

This example offers an accessory at a 10% discount when the qualifying basket value is at least 50 but less than 100 in your store's base currency.

1. Open **Tiers & products**, then choose **New tier**.
2. Enter a **Title**, such as `Basket 50–100`. This is an administrative label; the storefront heading is configured separately.
3. Enter **Minimum basket value** `50`, **Maximum basket value** `100` and **Priority** `0`. Leave the tier's **Enabled** checkbox selected and click its **Save** button.
4. With that tier selected, use **Add a product**. Choose a **Category**, then select an active **Product** from the dropdown. The Product dropdown stays empty until you choose a category.
5. Set **Pricing** to **Percentage discount**, enter `10` under **Price or percentage**, and click that product form's **Save** button.
6. Under **Setup**, choose **At the top of checkout** or **Modal popup**.
7. Choose your template, appearance and wording in their tabs. Click the settings **Save** button.
8. Test the offer on staging using a signed-in customer with an eligible basket. Once satisfied, select **Enabled** under Setup and save to activate offers. You can enable it on staging while testing.

Global offers and the individual tier must both be enabled. The offered product must be eligible and must not already be in the customer's basket.

## Use the administration tabs

| Tab | What you manage |
| --- | --- |
| Setup | Global Enabled switch and inline/modal display. |
| Offer template | The predefined design or No template / Custom. |
| Appearance | Colours, layout, alignment, dimensions, buttons, shadows and typography. |
| Custom offer text | Headings, descriptions and button/label wording. |
| Tiers & products | Basket-value ranges and the products/prices assigned to each tier. |
| Maintenance | Permanent removal of the add-on's configuration. |
| User manual | Grouped topics, compact quick start, a selected-guide reading pane and local search. |

Setup, Offer template, Appearance and Custom offer text share one settings form. You can change tabs without losing those unsaved settings, then click **Save** to store them together. Leaving or reloading the page without saving discards unsaved changes.

Tier and product forms have their own **Save** buttons. Save each edited tier or product rule separately. Saving the appearance settings does not save a tier or product form.

## Set basket-value tiers

The qualifying value includes products, product tax, delivery and delivery tax. Tier amounts use the **store base currency**, even when a customer shops in another currency. This qualifying amount is not necessarily the final payable total after other order-total adjustments.

| Field | Meaning |
| --- | --- |
| Title | A name to help you identify the tier in administration. |
| Minimum basket value (inclusive) | The lowest eligible value. A minimum of 50 includes exactly 50. |
| Maximum basket value (exclusive; blank for unlimited) | The upper boundary. A maximum of 100 excludes exactly 100. Leave blank for no upper limit. |
| Priority (highest wins) | Determines which enabled tier wins when ranges overlap. |
| Enabled | Allows this tier to be selected while global offers are enabled. |

Use non-negative basket amounts. A maximum must be greater than the minimum. Enter amounts without currency symbols. Priority must be a whole number from -100000 to 100000; higher values take precedence.

For adjacent ranges, use the same boundary: `0–50`, `50–100`, and `100–unlimited`. Exactly 50 belongs to the second range; exactly 100 belongs to the third.

When ranges overlap, the highest numeric priority wins. If priorities are equal, the lowest tier ID wins. Products come from the winning tier; the add-on does not combine all matching tiers. A winning tier with no eligible products does not show offers from a lower-priority tier.

Select a tier to edit its settings and assigned products. To pause one tier, clear its **Enabled** checkbox and save. **Delete** removes that tier and its product rules; use disable when you want to keep the configuration.

## Add products and offer prices

Save or select a tier before adding products. In **Add a product**, choose a category and active product, choose the pricing method and save. Category filtering lists products directly assigned to that category; linked products appear in each assigned category. Choose Uncategorised for products without a category. The Product dropdown stays empty until a category is selected. Saved rules automatically select a category for their product. JavaScript is required to change category/product selections. Each product can appear once within a tier. The same product may have separate rules in different tiers.

Existing product rules remain editable under the selected tier. Use a rule's **Save** button after changing its product, pricing method or value. Its **Delete** button removes only that rule.

### Fixed unit price excluding tax

Enter the final offer unit price **before tax**, in base currency. The fixed price includes the selected product options; their surcharges are not added again to the fixed offer price. Phoenix applies the appropriate tax and currency display.

For example, if the normal pre-tax price is 10 and an option adds 2, a fixed offer value of 8 sets the pre-tax offer price to 8. It does not become 10 after adding the option surcharge.

### Percentage discount

Enter a percentage from **0 to 100**. The percentage applies to the current normal/special price including the selected option adjustments.

For example, a pre-tax product price of 10 plus a 2 option adjustment gives a normal option-inclusive price of 12. A 10% discount gives a pre-tax offer price of 10.80.

Offers never increase the normal price. If a fixed offer value exceeds the normal option-inclusive price, the normal price is retained. A 0% discount gives no reduction; 100% reduces the offer price to zero.

Only one unit of each offered product can be accepted. Products already in the basket are hidden. Inactive products are hidden, and stock availability is checked when the store's stock checking is enabled. Keep offer products and their stock up to date.

## Choose inline or modal display

Under **Setup → Offer display**, choose an option and save.

| Display | Customer experience |
| --- | --- |
| At the top of checkout | The offer panel appears above the payment methods when JavaScript is available. |
| Modal popup | Eligible offers open automatically on each payment-page visit or reload, including a cached history return. |

Customers can dismiss the modal using its corner close button, Escape, the backdrop or the continue-checkout button. Dismissing it closes it for the current page entry; it can open again on the next eligible visit. There is no separate offer launcher and no remembered browser-storage suppression.

Without JavaScript or native dialog support, modal offers fall back to inline content and customers can still add products. Without JavaScript, the inline panel stays at its original checkout hook position and displayed prices do not recalculate immediately when options change; the server still validates options and calculates the accepted price.

## Choose a template

Open **Offer template**, select a design and save. The choices are:

- No template / Custom
- Red special offer
- BusyBee honey
- Midnight
- Fresh green
- Ocean blue
- Soft plum
- Slate minimal
- Warm coral
- Clean teal
- Champagne

Predefined templates supply their own palette, solid buttons and thumbnail sizing. Manual values for those template-controlled properties are retained but may not be visible while the design is selected. To return to your manual appearance, select **No template / Custom** and save.

Layout, alignment, spacing, corners, text size and modal width remain configurable with a predefined template. Heading/image background overrides and heading/description typography also work with templates. The Classic modal has no separate header strip; styled designs have a promotional banner.

## Customise appearance and alignment

Open **Appearance**, change the required settings and save. Check the actual checkout at desktop and phone widths after making changes.

### Layout and alignment

| Setting | How to use it |
| --- | --- |
| Product layout: Row | Places product cards beside one another, up to Desktop columns. Rows wrap to at most two columns below 768px and one below 576px. |
| Product layout: Stacked | Places one full-width product card on each row. |
| Desktop columns | Choose 1–4 for Row layout. |
| Image, text and button alignment | Select Left, Centre or Right for the offer content in both inline and modal displays. |
| Product row alignment | Positions an incomplete Row of cards within the available width. It is separate from the alignment of the content inside each card. |
| Modal width | Controls the popup width; the popup is also limited by the available screen space. |

For example, with four Desktop columns and two offered products, **Product row alignment: Centre** centres the two cards as a group. **Image, text and button alignment: Left** keeps each card's contents left aligned.

In styled Stacked layouts, Left retains the compact image-beside-details arrangement; Centre and Right place the image above the details. Row cards share layout tracks so prices and buttons stay aligned even when one product has options or text wraps. Savings appear below prices, with Add buttons beneath savings.

### Colours and typography

Use six-digit colour values such as `#ffffff` or `#166534`. Heading background, Image background, Heading text colour and Description colour may be left blank to keep the design defaults. Other colour fields require a valid colour.

Heading and description font sizes use **0** for automatic sizing. Choose **Automatic** weight to retain the design's weight, or choose a listed weight. Normal and Italic styles are available. Customise the heading separately from the general Text size to keep the introductory title balanced with the cards.

### Sizing and spacing limits

Enter whole numbers within these limits:

| Setting | Allowed value |
| --- | --- |
| Corner radius | 0–40px |
| Inner spacing | 0–60px |
| Card gap | 0–48px |
| Border width | 0–6px |
| Image height | 60–300px; predefined templates supply their thumbnail sizing. |
| Text size | 12–24px |
| Desktop columns | 1–4 |
| Modal width | 360–1200px |
| Heading font size | 0–60px; 0 = automatic. |
| Description size | 0–36px; 0 = automatic. |

Choose **Outline** or **Solid** buttons when using your custom design. Shadow choices are None, Soft and Strong.

## Change the wording

Use **Custom offer text**, then save. Leave a field blank to use its predefined wording. Text is plain text: HTML tags are displayed as text rather than used for formatting. Description line breaks are preserved.

| Field | Where it appears | Maximum characters |
| --- | --- | --- |
| Heading | Main offer heading. | 200 |
| Description | Text below the heading. | 1000 |
| Offer badge | Badge in predefined templates. | 80 |
| Add button | Product addition button; styled designs append the current offer price automatically. | 100 |
| Continue checkout button | Modal dismissal button. | 150 |
| Inline note | Note beneath inline offers; hidden in modals and Classic design. | 1000 |
| Savings label | Label before the calculated saving amount. | 80 |

Changing labels does not change the action of the buttons. Keep Add and Continue wording clear so customers understand whether they are adding a product or dismissing the offer.

## Understand the customer checkout journey

1. A signed-in customer reaches the payment page with an eligible basket.
2. The add-on selects one enabled tier and shows its eligible products with option selectors where needed.
3. With JavaScript available, choosing an option updates the normal price, offer price and saving shown on the card.
4. The customer clicks an Add button. The server checks eligibility, the selected options and stock rules, then adds one unit.
5. The customer returns to the delivery step. Delivery is recalculated for the changed basket, and the payment selection must be reviewed again.
6. The first acceptance locks the offer tier to that basket. Remaining products from the same tier may still be accepted; accepting an offer does not switch the customer to another tier.
7. Phoenix recalculates the discounted lines and taxes during confirmation and order processing.

Changing other basket contents, quantities, product options, the customer or the currency invalidates the saved acceptance. Accepted products remain in the basket at their normal prices and the customer must review checkout again. A completed order empties the basket and ends the acceptance.

The offer is limited to the current checkout. It does not create a catalogue-wide special or a coupon. Disabling/deleting the tier or its product rule removes the associated discount entitlement for a checkout in progress. Historical orders keep the prices saved when they were placed.

## Check an offer before enabling it

Use a staging store and test a signed-in customer account.

1. Check values just below, exactly at and just above each tier boundary. Include delivery and tax when preparing the basket.
2. Check overlapping tiers and priorities, and confirm the intended winning tier has eligible products.
3. Test a simple product and a product with options. Confirm the fixed/percentage prices and savings using your store's tax display and currency.
4. Check Row and Stacked presentation, your selected alignment, incomplete product rows and narrow-screen wrapping.
5. In modal mode, check close, Escape, backdrop dismissal and the continue-checkout button. Reload the payment page and confirm the expected reopening.
6. Add an offer, select delivery again and verify the discounted line at confirmation. Confirm that an accepted product cannot be added twice through the offer.
7. Change the basket or currency and check that old acceptance is invalidated. Review any resulting normal prices.
8. Complete a test order using each supported payment route, and check the saved line prices, tax and order total.

For the full technical/staging checklist, see [TESTING.md](TESTING.md). Automated release checks do not replace installation and payment testing on your own store and theme.

## Troubleshooting

| Symptom | What to check |
| --- | --- |
| Checkout Offers is missing from Catalog | Upload the admin box/language files into the actual admin directory and check administrator permissions. |
| The installation screen still appears | Complete Install database tables and check that the add-on's tables/settings were created successfully. |
| No offers appear | Sign in as a customer; enable offers and the tier; check the tax/delivery-inclusive base-currency range and the winning priority. The tier needs eligible active products. |
| A configured product is missing | Check its active status, stock rules and whether it is already in the basket. Check the product rule belongs to the winning tier. |
| The product rule will not save | Select an existing active product, a non-negative fixed price or a 0–100 percentage. The same product cannot be added twice to one tier. |
| A higher-value tier is not selected after adding an offer | The first acceptance intentionally locks the tier for that exact basket. |
| Delivery/payment must be selected again | Adding a product invalidates the previous shipping quote and payment selection so checkout can be recalculated. |
| An accepted product returns to normal price | Check for basket, option, quantity, customer or currency changes, or a disabled/deleted tier or product rule. |
| Manual colours or button style seem ignored | A predefined template supplies its own palette/button style. Select No template / Custom and save to restore manual appearance. |
| Alignment appears wrong | Save Appearance; distinguish content alignment from Product row alignment; upload the complete current package, including both the storefront hook and CSS; refresh cached assets. Check your theme for conflicting CSS. |
| Modal offers appear again on a later visit | This is expected: dismissal applies to the current page entry. |
| Modal offers show inline | Check JavaScript, native dialog support, the standard payment form and the theme's checkout hooks. Inline is the fallback. |
| Option prices do not change immediately | Check that the packaged JavaScript loads without errors. Without JavaScript, the accepted price is still calculated on the server. |
| Appearance changes cannot be saved | Use values within the listed ranges, six-digit colours and permitted choices. Check the error displayed after saving. |

If the problem remains, record the selected display/template/layout, tier boundaries and priority, product IDs, browser width and the checkout step. Include a screenshot and relevant error-log entry when seeking support.

## Disable or remove the add-on

### Disable temporarily

Under **Setup**, clear **Enabled** and save. Tiers and product rules remain stored for later use. To pause only one tier, clear that tier's Enabled checkbox and save its form.

### Remove permanently

Uninstallation deletes all offer tiers, product rules and add-on settings. Back up anything you want to retain first.

1. Disable offers in Setup and save.
2. Open **Maintenance → Danger zone**.
3. Read the removal notice and tick **I confirm removal of all offer tiers and settings**.
4. Click **Uninstall**.
5. Remove only the add-on runtime files listed in `package-manifest.txt`, using your actual admin directory. Do not delete shared Phoenix folders.

Historical orders retain their saved prices. Reinstalling creates a fresh configuration; it does not restore deleted tiers.
