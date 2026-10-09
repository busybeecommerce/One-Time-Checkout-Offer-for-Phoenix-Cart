# Changelog

## 1.2.1

- Make styled-template Add buttons fit their labels rather than filling the available width.
- Apply modal content alignment to product images as well as text and buttons. Centre and Right stack thumbnails above aligned details; Left keeps compact rows.
- Verify image/button positions and compact button widths in all templates on desktop and mobile.

## 1.2.0

- Add an admin template picker with visual swatches: Custom / Classic, Red special offer, BusyBee honey, Midnight and Fresh green.
- Add styled promotional banners, compact product rows, savings badges, price-labelled Add buttons and an order-availability note for inline and modal offers.
- Keep the current appearance as the default; validate stored template IDs and retain manual appearance values when switching designs.
- Calculate displayed savings from the displayed normal/offer amounts and refresh savings/button prices when options change, including tax and currency formatting.
- Test every styled design on desktop/mobile, in both display modes, including form submission and no-JavaScript rendering.

## 1.1.2

- Add a BusyBee orange/yellow admin header with a locally packaged logo linking to BusyBee Commerce and an offer-status indicator.
- Refine admin spacing, typography, inputs, buttons, selected-tier highlighting and product forms; include guidance before a tier is selected.
- Scope styling to the add-on page and resolve logo/CSS URLs through Phoenix's catalogue link builder.
- Verify desktop/mobile rendering with Phoenix's Bootstrap version and retain existing settings/product submission fields.

## 1.1.1

- Remove the modal header strip while retaining an accessible top-right close ×.
- Add independent Left, Centre and Right alignment for modal text/buttons and product rows.
- Remove the accent colour setting and coloured top border; use the configured button colour for keyboard focus. Clean up the obsolete setting when Setup is saved.
- Test alignment placement, header removal, close/reopen behaviour and upgrade configuration.

## 1.1.0

- Add optional modal offers with automatic opening once per basket, accessible close controls, Escape/backdrop dismissal and a reopen button.
- Keep product options and offer submissions inside the original checkout form; retain inline fallbacks.
- Add validated appearance controls for colours, buttons, shadows, borders, corners, spacing, images, typography, columns and modal width.
- Preserve existing inline display and rules during upgrades; add settings on Setup save.
- Add real browser checks for modal behaviour, form submission, mobile sizing and fallbacks.

## 1.0.2

- Fix checkout truncation when an eligible offer has an image: use Phoenix's `images/` path instead of the undefined legacy `DIR_WS_IMAGES` constant.
- Remove the test fixture's fabricated image constant and assert image rendering without it.
- Test checkout and admin rendering on pinned Phoenix 1.1.0.6 and 1.1.0.8 sources with PHP 8.1/8.3 CI.

## 1.0.1

- Fix the blank admin page caused by passing a Href object to the strict string-typed Form constructor.
- Place Checkout Offers in the existing Reports menu and overwrite the old separate-menu file during updates.
- Add full admin rendering regression tests using Phoenix's actual Form, Input and Href classes.

## 1.0.0

Initial standalone release: basket tiers, scoped product prices, payment offer cards, product options, admin configuration/installation, tests and release packaging.
