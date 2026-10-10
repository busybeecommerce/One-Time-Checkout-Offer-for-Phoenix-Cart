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
        $appearance = checkout_offer_appearance();
        $attributes = '';
        foreach (['price_alignment', 'button_alignment', 'button_size', 'button_width', 'card_shadow', 'button_shadow'] as $key) {
            $attributes .= ' data-' . str_replace('_', '-', $key) . '="' . checkout_offer_escape($appearance[$key]) . '"';
        }
        return '<section id="checkout-offer" class="mb-4" data-display-mode="' . checkout_offer_display_mode()
            . '" data-template="' . $appearance['template'] . '" data-product-layout="' . $appearance['product_layout'] . '" data-content-alignment="' . $appearance['content_alignment']
            . '" data-shadow="' . $appearance['shadow'] . '" data-button-style="' . $appearance['button_style']
            . '" data-close-label="' . checkout_offer_escape(CHECKOUT_OFFER_CLOSE)
            . '" data-dismiss-label="' . checkout_offer_text('dismiss_text', CHECKOUT_OFFER_DISMISS) . '"' . $attributes . ' style="' . checkout_offer_escape(checkout_offer_style($appearance)) . '">'
            . '<div class="checkout-offer-banner"><span class="checkout-offer-badge">' . checkout_offer_text('badge_text', CHECKOUT_OFFER_BADGE) . '</span>'
            . '<h2 class="h5" id="checkout-offer-title">' . checkout_offer_text('heading_text', 'classic' === $appearance['template'] ? CHECKOUT_OFFER_HEADING : CHECKOUT_OFFER_TEMPLATE_HEADING)
            . '</h2><p class="checkout-offer-description">' . checkout_offer_text('description_text', 'classic' === $appearance['template'] ? CHECKOUT_OFFER_DESCRIPTION : CHECKOUT_OFFER_TEMPLATE_DESCRIPTION)
            . '</p></div><div class="checkout-offer-grid">' . $cards . '</div><p class="checkout-offer-note">' . checkout_offer_text('note_text', CHECKOUT_OFFER_NOTE) . '</p></section>';
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
        $imageHtml = '' === $image ? '' : '<img class="checkout-offer-image" src="'
            . checkout_offer_escape('images/' . $image) . '" alt="' . checkout_offer_escape((string)$product->get('name')) . '">';
        $currency = $GLOBALS['currencies']->currencies[$_SESSION['currency']];
        $saving = max(0.0, (float)$GLOBALS['currencies']->display_raw($normal, $rate) - (float)$GLOBALS['currencies']->display_raw($offer, $rate));
        $format = checkout_offer_escape(json_encode($currency, JSON_THROW_ON_ERROR));
        return '<article class="checkout-offer-card" data-offer-card data-currency="' . $format . '" data-tax-included="' . ('true' === DISPLAY_PRICE_WITH_TAX ? '1' : '0') . '" data-base="' . (float)$product->get('base_price')
            . '" data-mode="' . checkout_offer_escape($rule['mode']) . '" data-value="' . (float)$rule['value'] . '" data-tax="' . $rate . '">'
            . $imageHtml . '<div class="checkout-offer-card-content"><div class="checkout-offer-details"><h3 class="h6">' . checkout_offer_escape((string)$product->get('name')) . '</h3>' . $options . '</div>'
            . '<div class="checkout-offer-actions"><p class="checkout-offer-prices"><del class="text-body-secondary me-2" data-normal-price>' . $GLOBALS['currencies']->display_price($normal, $rate)
            . '</del><strong data-offer-price>' . $GLOBALS['currencies']->display_price($offer, $rate) . '</strong>'
            . '<span class="checkout-offer-saving">' . checkout_offer_text('saving_text', CHECKOUT_OFFER_SAVE) . ' <span data-offer-saving>' . $GLOBALS['currencies']->format($saving, false) . '</span></span></p>'
            . '<button type="submit" class="btn checkout-offer-add" name="checkout_offer_product" value="' . $id
            . '" formaction="' . checkout_offer_escape((string)$GLOBALS['Linker']->build('checkout_payment.php')) . '" formnovalidate>'
            . '<span class="checkout-offer-classic-label">' . checkout_offer_text('add_text', CHECKOUT_OFFER_ADD) . '</span><span class="checkout-offer-template-label">' . checkout_offer_text('add_text', CHECKOUT_OFFER_ADD_PRICE)
            . ' <span data-button-price>' . $GLOBALS['currencies']->display_price($offer, $rate) . '</span></span></button></div></div></article>';
    }
}
