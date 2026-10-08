# Changelog

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
