# Testing

Run README.md automated commands. PHP tests lint package files, check manifest/contracts, exercise real Phoenix order construction and test pricing, tier boundaries, VAT, delivery tax, options and isolated acceptance. Node tests verify placement and option-price updates.

## Staging acceptance

1. Install twice; retain tiers. Confirm administrator access controls.
2. Test overlapping tiers and exact boundaries including delivery and VAT.
3. Offer simple, option-bearing, physical and downloadable products. Hide inactive, already-carted and out-of-stock products. Verify option prices.
4. Add an offer; recalculate delivery; verify discounted confirmation. Add another offer from the locked tier.
5. Complete orders through each payment provider, including callbacks. Match capture, line prices, VAT, shipping and invoice totals.
6. Replay addition; prevent duplicates. Reject invalid tokens and noneligible products.
7. Change quantities, options, customer/currency; review normal prices. Complete an order and confirm a new basket receives no previous acceptance.
8. Test mobile, keyboard, JavaScript disabled and your actual template.
9. Disable/uninstall; preserve saved historical prices.

No live store installation or real payment capture was exercised during release validation.
