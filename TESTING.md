# Testing

Run README.md automated commands. PHP tests lint package files, check manifest/contracts, exercise real Phoenix order construction and test pricing, tier boundaries, VAT, delivery tax, options and isolated acceptance. Node tests verify placement and option-price updates.

The admin rendering regression executes the complete add-on page using real Phoenix Form, Input and Href classes. It checks the pre-install screen, setup and tier/product forms, CSRF inputs, completion of the page and registration within Reports. Bootstrap/template wrappers and database rows are fixtures.

After generating all three admin fixtures, `node tests/admin_browser.cjs` checks the packaged logo and BusyBee link, page-scoped colours, responsive bounds, appearance controls and unchanged settings/product form submissions. It uses Bootstrap 5.3.8, matching Phoenix's admin hook, and saves desktop/mobile screenshots under `build/`. `CHECKOUT_OFFER_BOOTSTRAP_CSS` can point to the same stylesheet locally instead of an npm installation.

`node tests/browser.cjs` uses actual PHP-generated inline and styled modal fixtures (generate with `tests/run.php --render` and `--render --modal --styled`). It verifies native dialog opening/dismissal/repeat entry and reload, focus and reduced motion, token/payment/product/option submission, mobile bounds, disabled storage and JavaScript/dialog fallbacks. Screenshots are saved under `build/`. Set `CHECKOUT_OFFER_BROWSER=msedge` to use installed Edge locally. Browser fixtures use simplified surrounding checkout markup; test your theme on staging.

The browser regression also checks that no modal header strip renders, the corner close button remains positioned correctly, the accent border is absent, and Left/Centre/Right settings actually position both text and an incomplete product row. PHP/admin tests check alignment persistence and validation plus removal of the obsolete accent setting.

Generate each template fixture with `php tests/run.php <reference> --render --template=red` and the equivalent `--modal` command; repeat for honey, midnight and green. `node tests/templates_browser.cjs` verifies all four templates in both display modes, mobile bounds, full-width product rows, savings/button prices, product/options/token submission, dismiss/reload and no-JavaScript output. Template screenshots are saved in `build/`. Admin browser tests also save a selected template through the existing Setup form.

Template browser checks measure Add button width and image/button positions for Left, Centre and Right at desktop and mobile widths. Centred screenshots show the thumbnail above the details. Saved alignment is rendered as a validated data attribute by the PHP hook, including when JavaScript is unavailable.

The modal checks verify that footer containers are absent, the availability note is hidden only inside the modal, and the retained dismissal button belongs to the main offer content. Every styled template checks the restrained heading scale and dismissal/reload behaviour.

Admin browser checks measure paired appearance field tops and bottoms at 1440, 1200 and 768 pixels, including wrapped labels. They submit the new Product layout setting. PHP checks validate layout persistence and legacy defaults. Browser checks measure Row and Stacked card positions in all templates, inline and modal, and confirm mobile wrapping without horizontal overflow.

## Staging acceptance

1. Install twice; retain tiers. Confirm administrator access controls.
2. Test overlapping tiers and exact boundaries including delivery and VAT.
3. Offer simple, option-bearing, physical and downloadable products. Hide inactive, already-carted and out-of-stock products. Verify option prices.
4. Add an offer; recalculate delivery; verify discounted confirmation. Add another offer from the locked tier.
5. Complete orders through each payment provider, including callbacks. Match capture, line prices, VAT, shipping and invoice totals.
6. Replay addition; prevent duplicates. Reject invalid tokens and noneligible products.
7. Change quantities, options, customer/currency; review normal prices. Complete an order and confirm a new basket receives no previous acceptance.
8. Test inline and modal modes, dismiss/reload, Escape, mobile scrolling, JavaScript disabled and your actual template. Save appearance changes and verify colours, button styles, shadows, column counts and size limits. Confirm adding an offer recalculates shipping and retains the discounted order line.
9. Disable/uninstall; preserve saved historical prices.

No live store installation or real payment capture was exercised during release validation.
