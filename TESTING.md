# Testing

Run README.md automated commands. PHP tests lint package files, check manifest/contracts, exercise real Phoenix order construction and test pricing, tier boundaries, VAT, delivery tax, options and isolated acceptance. Node tests verify placement and option-price updates.

The admin rendering regression executes the complete add-on page using real Phoenix Form, Input and Href classes. It checks the pre-install screen, setup and tier/product forms, CSRF inputs, completion of the page and registration within Reports. Bootstrap/template wrappers and database rows are fixtures.

`node tests/browser.cjs` uses actual PHP-generated inline and styled modal fixtures (generate with `tests/run.php --render` and `--render --modal --styled`). It verifies native dialog opening/dismissal/reopening, token/payment/product/option submission, mobile bounds, disabled storage and JavaScript/dialog fallbacks. Screenshots are saved under `build/`. Set `CHECKOUT_OFFER_BROWSER=msedge` to use installed Edge locally. Browser fixtures use simplified surrounding checkout markup; test your theme on staging.

## Staging acceptance

1. Install twice; retain tiers. Confirm administrator access controls.
2. Test overlapping tiers and exact boundaries including delivery and VAT.
3. Offer simple, option-bearing, physical and downloadable products. Hide inactive, already-carted and out-of-stock products. Verify option prices.
4. Add an offer; recalculate delivery; verify discounted confirmation. Add another offer from the locked tier.
5. Complete orders through each payment provider, including callbacks. Match capture, line prices, VAT, shipping and invoice totals.
6. Replay addition; prevent duplicates. Reject invalid tokens and noneligible products.
7. Change quantities, options, customer/currency; review normal prices. Complete an order and confirm a new basket receives no previous acceptance.
8. Test inline and modal modes, close/reopen, Escape, mobile scrolling, JavaScript disabled and your actual template. Save appearance changes and verify colours, button styles, shadows, column counts and size limits. Confirm adding an offer recalculates shipping and retains the discounted order line.
9. Disable/uninstall; preserve saved historical prices.

No live store installation or real payment capture was exercised during release validation.
