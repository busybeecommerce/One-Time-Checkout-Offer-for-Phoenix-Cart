# Changelog

## 1.7.19

- Format saved tier limits using the store base currency, including symbols, separators and decimal precision, without exchange conversion. Identify the base currency on amount fields and move tier edit/delete icons to the far right.

## 1.7.18

- Show Uncategorised only when active uncategorised products exist. Add labelled edit/delete icons to the left of saved tiers, using the existing tier actions and CSRF protection.

## 1.7.17

- Populate the product dropdown only after a category is selected. Preselect a category for saved products, clear products when category is cleared, and provide Uncategorised for products without category assignments.

## 1.7.16

- Restore all saved tier borders, compact the basket-value fields and place Save/Delete actions alongside each other.
- Add category and active-product dropdowns, preserving linked products and uncategorised products through All categories. Product selection remains available without JavaScript; server validation is unchanged.

## 1.7.15

- Remove version references and update instructions from the user manual, retaining temporary disable and permanent add-on removal guidance. Rename the maintenance guide and regenerate packaged HTML.

## 1.7.14

- Start modal focus at the offer heading, avoiding an automatic second border on the close button. Preserve its visible keyboard focus outline, native modal behaviour and focus restoration after dismissal.

## 1.7.13

- Match the desktop user-guide reading pane to the topic column height instead of capping it at 68vh. Retain scrolling for long guides and natural content height on mobile.

## 1.7.12

- Move Checkout Offers into Catalog after Phoenix loads the Catalog box. Package inert Reports/legacy files for overwrite upgrades; avoid duplicate registrations.
- Replace duplicated manual navigation with one grouped topic navigator and a selected-guide reading pane; add a concise three-step quick start and compact heading/search.
- Preserve complete guidance and native/no-JavaScript access, keyboard/mobile support, unsaved settings and hidden settings Save form. No storefront/accounting changes or migration.

## 1.7.11

- Turn the admin manual into a task guide with compact navigation cards, an always-open quick start and native expandable reference sections.
- Add local section search with accessible result counts and links that reveal and focus their targets; essential guidance remains available without JavaScript.
- Preserve all guidance, unsaved settings and checkout behaviour; add manual navigation, search and disclosure browser regressions. No migration required.

## 1.7.10

- Make the complete user manual available inside Checkout Offers administration as a read-only User manual tab with contents navigation and responsive tables. Include the rendered manual in the runtime package and verify it matches the Markdown guide. Preserve unsaved settings across tab switches and provide the manual without JavaScript.

## 1.7.9

- Add a packaged user manual with installation, tab-by-tab administration, tier and pricing examples, display/template/appearance controls, customer checkout behaviour, troubleshooting and maintenance instructions. Refresh README/install guidance for current alignment and modal behaviour. No runtime or database changes.

## 1.7.8

- Restore Left/Centre/Right image, text, button and incomplete product row alignment in inline offers as well as modals. Keep prices, savings and buttons in consistent vertical purchase areas across Classic and every template, preventing independent savings wrapping from shifting prices on option-bearing cards.

## 1.7.7

- Show the customisable You save label and saving amount beneath Classic modal offer prices. Preserve card/button alignment and existing option-price recalculation.

## 1.7.6

- Show the customisable You save label and saving amount beneath Classic inline offer prices. Retain aligned purchase rows and existing option-price recalculation.

## 1.7.5

- Present Maintenance as an open danger area with red warning styling, permanent-removal wording and a distinct confirmation checkbox. Preserve existing uninstall validation and historical order records.

## 1.7.4

- Add heading-area and product-image background colour controls for inline and modal offers. Clarify heading font size and text colour labels. Blank colours retain existing Classic/template defaults; no migration or reinstall.

## 1.7.3

- Refine Classic inline offer cards with a separated introduction, subtle image surfaces, stronger price hierarchy and compact purchase rows. Retain option-bearing card alignment, configured colours/sizes, custom text and existing modal/template styling.

## 1.7.2

- Restore the BusyBee orange admin header with white text. Match notification width to the admin content and fade successful save messages after six seconds, including on the installation screen. Keep warnings/errors visible and respect reduced motion.

## 1.7.1

- Refine all admin tabs with compact value fields, grouped appearance controls, paired short-text fields and bounded descriptions and maintenance panels. Keep existing form names, values and responsive layouts.

## 1.7.0

- Organise administration into Setup, Templates, Appearance, Custom text, Tiers & products and Maintenance tabs. Preserve unsaved settings when switching tabs and return to the active tab after saving.
- Add keyboard tab navigation and a complete no-JavaScript fallback. Keep existing forms and validation.
- Use white admin header text over a deeper BusyBee orange background.
- Align offer prices and Add buttons across row cards, including products with options, and refine Classic typography and card depth.

## 1.6.0

- Reduce the modal close control to 28px with a 1px border and a precisely centred drawn cross.
- Add custom heading, description, badge, Add/continue buttons, savings label and inline note; blank fields retain language-file fallback wording. Custom text is escaped as plain text.
- Add independent heading/description sizes, colours, weights and normal/italic styling. Automatic defaults preserve existing templates.
- Validate text lengths, Unicode and typography values; test saving/clearing text and actual browser rendering in all ten templates.

## 1.5.0

- Add Ocean blue, Soft plum, Slate minimal, Warm coral, Clean teal and Champagne predefined templates.
- Make the custom appearance choice explicit as No template / Custom. Choosing it and saving removes the predefined palette and restores custom colours without changing the selected product layout.
- Test all ten predefined designs in inline/modal and Row/Stacked layouts, plus no-template settings submission and persistence.

## 1.4.0

- Align paired admin appearance controls even when labels wrap onto multiple lines.
- Add Row / Stacked product layout for Classic and all predefined templates, in inline and modal display. Row uses Desktop columns and wraps responsively.
- Preserve existing Classic rows and predefined-template stacks until a layout is explicitly saved.
- Update modal help text to describe automatic opening and dismissal.

## 1.3.1

- Refine all four predefined templates with solid colour banners, restrained typography, clean image tiles and compact buttons.
- Remove modal footer strips and the availability-note footer; keep the dismissal button inside the main offer content. Inline offers retain their availability note.
- Preserve automatic opening, image/content alignment, focus restoration and checkout submission.

## 1.3.0

- Automatically open eligible modal offers on every payment-page entry or reload; remove launcher and session storage suppression.
- Restore focus to checkout after dismissal without reopening on the same page.
- Upgrade styled templates with richer promotional banners, larger product imagery, depth and compact Add buttons. Respect reduced motion.
- Preserve Classic settings, alignment, inline fallbacks and server-side checkout accounting.

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
