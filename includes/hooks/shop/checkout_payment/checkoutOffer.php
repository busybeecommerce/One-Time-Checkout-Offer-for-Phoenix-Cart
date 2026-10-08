<?php
declare(strict_types=1);

require_once DIR_FS_CATALOG . 'includes/functions/checkout_offer.php';

class hook_shop_checkout_payment_checkoutOffer {

    public function listen_injectFormDisplay(): string
    {
        if (!checkout_offer_enabled() || empty($_SESSION['customer_id'])) {
            return '';
        }
        checkout_offer_language();
        $order = $GLOBALS['order'];
        $tier = checkout_offer_current_tier($order, checkout_offer_state());
        if (null === $tier) {
            return '';
        }
        $cards = '';
        foreach (checkout_offer_items((int)$tier['id']) as $rule) {
            $product = product_by_id::build((int)$rule['products_id']);
            $inCart = false;
            foreach ($order->products as $line) {
                $inCart = $inCart || (int)$line['id'] === (int)$rule['products_id'];
            }
            if ($inCart || !$product->get('status') || (defined('STOCK_CHECK') && 'true' === STOCK_CHECK && $product->lacks_stock(1))) {
                continue;
            }
            $cards .= $this->card($product, $rule);
        }
        if ('' === $cards) {
            return '';
        }
        $accent = defined('CHECKOUT_OFFER_ACCENT') && preg_match('/^#[0-9a-f]{6}$/i', CHECKOUT_OFFER_ACCENT)
            ? CHECKOUT_OFFER_ACCENT : '#6f42c1';
        return '<section id="checkout-offer" class="border rounded p-3 mb-4" style="border-top:4px solid ' . $accent . '!important">'
            . '<h2 class="h5">' . CHECKOUT_OFFER_HEADING . '</h2><p class="text-body-secondary small">' . CHECKOUT_OFFER_DESCRIPTION
            . '</p><div class="row g-3">' . $cards . '</div></section>';
    }

    private function card(Product $product, array $rule): string
    {
        $id = (int)$rule['products_id'];
        $options = '';
        $defaultAdjustment = 0.0;
        foreach (($product->get('attributes') ?? []) as $optionId => $option) {
            $options .= '<label class="form-label small" for="offer-' . $id . '-' . (int)$optionId . '">' . checkout_offer_escape($option['name'])
                . '</label><select class="form-select form-select-sm mb-2" name="checkout_offer_options[' . $id . '][' . (int)$optionId . ']" id="offer-' . $id . '-' . (int)$optionId . '" data-offer-option="' . (int)$optionId . '">';
            $first = true;
            foreach ($option['values'] as $valueId => $value) {
                $adjustment = ('-' === $value['prefix'] ? -1 : 1) * (float)$value['price'];
                if ($first) {
                    $defaultAdjustment += $adjustment;
                    $first = false;
                }
                $options .= '<option value="' . (int)$valueId . '" data-adjustment="' . $adjustment . '">'
                    . checkout_offer_escape($value['name']) . '</option>';
            }
            $options .= '</select>';
        }
        $normal = max(0.0, (float)$product->get('base_price') + $defaultAdjustment);
        $offer = checkout_offer_price($normal, $rule['mode'], (float)$rule['value']);
        $builder = new cart_order_builder($GLOBALS['order']);
        $taxAddress = $builder->build_tax_address();
        $rate = (float)Tax::get_rate($product->get('tax_class_id'), $taxAddress['entry_country_id'], $taxAddress['entry_zone_id']);
        $image = (string)$product->get('image');
        $imageHtml = '' === $image ? '' : '<img class="img-fluid mb-2" style="max-height:140px;object-fit:contain" src="'
            . checkout_offer_escape(DIR_WS_IMAGES . $image) . '" alt="' . checkout_offer_escape((string)$product->get('name')) . '">';
        $currency = $GLOBALS['currencies']->currencies[$_SESSION['currency']];
        $format = checkout_offer_escape(json_encode($currency, JSON_THROW_ON_ERROR));
        return '<div class="col-12 col-md-6 col-lg-4"><article class="border rounded p-3 h-100" data-offer-card data-currency="' . $format . '" data-tax-included="' . ('true' === DISPLAY_PRICE_WITH_TAX ? '1' : '0') . '" data-base="' . (float)$product->get('base_price')
            . '" data-mode="' . checkout_offer_escape($rule['mode']) . '" data-value="' . (float)$rule['value'] . '" data-tax="' . $rate . '">'
            . $imageHtml . '<h3 class="h6">' . checkout_offer_escape((string)$product->get('name')) . '</h3>' . $options
            . '<p><del class="text-body-secondary me-2" data-normal-price>' . $GLOBALS['currencies']->display_price($normal, $rate)
            . '</del><strong data-offer-price>' . $GLOBALS['currencies']->display_price($offer, $rate) . '</strong></p>'
            . '<button type="submit" class="btn btn-outline-primary btn-sm" name="checkout_offer_product" value="' . $id
            . '" formaction="' . checkout_offer_escape((string)$GLOBALS['Linker']->build('checkout_payment.php')) . '" formnovalidate>'
            . CHECKOUT_OFFER_ADD . '</button></article></div>';
    }
}
