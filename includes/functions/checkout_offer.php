<?php
declare(strict_types=1);

function checkout_offer_enabled(): bool
{
    return defined('CHECKOUT_OFFER_ENABLED') && 'True' === CHECKOUT_OFFER_ENABLED;
}

function checkout_offer_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function checkout_offer_templates(): array
{
    return [
        'classic' => [],
        'red' => ['background' => '#fff5f5', 'text' => '#212529', 'card_background' => '#ffffff', 'border' => '#f5b5b5', 'button_background' => '#d91920', 'button_text' => '#ffffff', 'banner_background' => '#ef2929', 'banner_text' => '#ffffff'],
        'honey' => ['background' => '#fff8e8', 'text' => '#29231b', 'card_background' => '#ffffff', 'border' => '#eccb83', 'button_background' => '#a94d00', 'button_text' => '#ffffff', 'banner_background' => '#ffb900', 'banner_text' => '#212529'],
        'midnight' => ['background' => '#111827', 'text' => '#f9fafb', 'card_background' => '#1f2937', 'border' => '#475569', 'button_background' => '#fbbf24', 'button_text' => '#111827', 'banner_background' => '#17243b', 'banner_text' => '#ffffff'],
        'green' => ['background' => '#effaf5', 'text' => '#18372c', 'card_background' => '#ffffff', 'border' => '#a3d6bf', 'button_background' => '#12734b', 'button_text' => '#ffffff', 'banner_background' => '#19865a', 'banner_text' => '#ffffff'],
        'ocean' => ['background' => '#f0f6fc', 'text' => '#20354b', 'card_background' => '#ffffff', 'border' => '#bdd1e6', 'button_background' => '#1459a0', 'button_text' => '#ffffff', 'banner_background' => '#174a7e', 'banner_text' => '#ffffff'],
        'plum' => ['background' => '#f7f2fa', 'text' => '#35283f', 'card_background' => '#ffffff', 'border' => '#d7c5e2', 'button_background' => '#693b88', 'button_text' => '#ffffff', 'banner_background' => '#5b327a', 'banner_text' => '#ffffff'],
        'slate' => ['background' => '#f4f6f8', 'text' => '#273444', 'card_background' => '#ffffff', 'border' => '#cbd3dd', 'button_background' => '#334155', 'button_text' => '#ffffff', 'banner_background' => '#334155', 'banner_text' => '#ffffff'],
        'coral' => ['background' => '#fff5f1', 'text' => '#49302c', 'card_background' => '#ffffff', 'border' => '#edc5bb', 'button_background' => '#ad3931', 'button_text' => '#ffffff', 'banner_background' => '#b83b32', 'banner_text' => '#ffffff'],
        'teal' => ['background' => '#eff8f7', 'text' => '#203d3b', 'card_background' => '#ffffff', 'border' => '#b5d7d3', 'button_background' => '#0f6664', 'button_text' => '#ffffff', 'banner_background' => '#0f6664', 'banner_text' => '#ffffff'],
        'champagne' => ['background' => '#fcf9f3', 'text' => '#3a3025', 'card_background' => '#ffffff', 'border' => '#d9c9ad', 'button_background' => '#745021', 'button_text' => '#ffffff', 'banner_background' => '#efe3ce', 'banner_text' => '#3a3025'],
    ];
}

function checkout_offer_appearance_fields(): array
{
    return [
        'template' => ['type' => 'select', 'default' => 'classic', 'choices' => array_keys(checkout_offer_templates())],
        'background' => ['type' => 'color', 'default' => '#ffffff'],
        'text' => ['type' => 'color', 'default' => '#212529'],
        'card_background' => ['type' => 'color', 'default' => '#ffffff'],
        'border' => ['type' => 'color', 'default' => '#dee2e6'],
        'button_background' => ['type' => 'color', 'default' => '#0d6efd'],
        'button_text' => ['type' => 'color', 'default' => '#ffffff'],
        'radius' => ['type' => 'number', 'default' => 6, 'min' => 0, 'max' => 40],
        'padding' => ['type' => 'number', 'default' => 16, 'min' => 0, 'max' => 60],
        'gap' => ['type' => 'number', 'default' => 16, 'min' => 0, 'max' => 48],
        'border_width' => ['type' => 'number', 'default' => 1, 'min' => 0, 'max' => 6],
        'image_height' => ['type' => 'number', 'default' => 140, 'min' => 60, 'max' => 300],
        'font_size' => ['type' => 'number', 'default' => 16, 'min' => 12, 'max' => 24],
        'columns' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 4],
        'modal_width' => ['type' => 'number', 'default' => 900, 'min' => 360, 'max' => 1200],
        'button_style' => ['type' => 'select', 'default' => 'outline', 'choices' => ['outline', 'solid']],
        'shadow' => ['type' => 'select', 'default' => 'none', 'choices' => ['none', 'soft', 'strong']],
        'content_alignment' => ['type' => 'select', 'default' => 'left', 'choices' => ['left', 'center', 'right']],
        'products_alignment' => ['type' => 'select', 'default' => 'left', 'choices' => ['left', 'center', 'right']],
        'product_layout' => ['type' => 'select', 'default' => 'row', 'choices' => ['row', 'stacked']],
        'heading_size' => ['type' => 'number', 'default' => 0, 'min' => 0, 'max' => 60],
        'description_size' => ['type' => 'number', 'default' => 0, 'min' => 0, 'max' => 36],
        'heading_weight' => ['type' => 'select', 'default' => 'auto', 'choices' => ['auto', '400', '500', '600', '700', '800']],
        'description_weight' => ['type' => 'select', 'default' => 'auto', 'choices' => ['auto', '400', '500', '600', '700']],
        'heading_colour' => ['type' => 'optional_color', 'default' => ''],
        'description_colour' => ['type' => 'optional_color', 'default' => ''],
        'heading_style' => ['type' => 'select', 'default' => 'normal', 'choices' => ['normal', 'italic']],
        'description_style' => ['type' => 'select', 'default' => 'normal', 'choices' => ['normal', 'italic']],
        'heading_text' => ['type' => 'text', 'default' => '', 'max' => 200],
        'description_text' => ['type' => 'text', 'default' => '', 'max' => 1000],
        'badge_text' => ['type' => 'text', 'default' => '', 'max' => 80],
        'add_text' => ['type' => 'text', 'default' => '', 'max' => 100],
        'dismiss_text' => ['type' => 'text', 'default' => '', 'max' => 150],
        'note_text' => ['type' => 'text', 'default' => '', 'max' => 1000],
        'saving_text' => ['type' => 'text', 'default' => '', 'max' => 80],
    ];
}

function checkout_offer_sanitise_appearance(array $input, bool $strict = false): array
{
    $appearance = [];
    foreach (checkout_offer_appearance_fields() as $key => $field) {
        // Preserve the previous styled-template stack until a layout is explicitly saved.
        $value = $input[$key] ?? ('product_layout' === $key && 'classic' !== $appearance['template'] ? 'stacked' : $field['default']);
        if ('number' === $field['type']) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
            $valid = false !== $value && $value >= $field['min'] && $value <= $field['max'];
        } elseif ('text' === $field['type']) {
            $value = is_string($value) ? trim($value) : $value;
            $valid = is_string($value) && 1 === preg_match('/^.{0,' . $field['max'] . '}$/usD', $value) && !str_contains($value, "\0");
        } elseif ('optional_color' === $field['type']) {
            $valid = is_string($value) && ('' === $value || 1 === preg_match('/^#[0-9a-f]{6}$/i', $value));
        } elseif ('color' === $field['type']) {
            $valid = is_string($value) && 1 === preg_match('/^#[0-9a-f]{6}$/i', $value);
        } else {
            $valid = is_string($value) && in_array($value, $field['choices'], true);
        }
        if (!$valid && $strict) {
            throw new InvalidArgumentException('Invalid appearance value: ' . $key);
        }
        $appearance[$key] = $valid ? $value : $field['default'];
    }
    return $appearance;
}

function checkout_offer_appearance(): array
{
    $saved = defined('CHECKOUT_OFFER_APPEARANCE') ? json_decode(CHECKOUT_OFFER_APPEARANCE, true) : [];
    return checkout_offer_sanitise_appearance(is_array($saved) ? $saved : []);
}

function checkout_offer_display_mode(): string
{
    return defined('CHECKOUT_OFFER_DISPLAY_MODE') && 'modal' === CHECKOUT_OFFER_DISPLAY_MODE ? 'modal' : 'inline';
}

function checkout_offer_style(array $appearance): string
{
    $palette = checkout_offer_templates()[$appearance['template']];
    $appearance = array_replace($appearance, $palette);
    $style = '';
    foreach (checkout_offer_appearance_fields() as $key => $field) {
        if ('text' === $field['type'] || '' === $appearance[$key] || (in_array($key, ['heading_size', 'description_size'], true) && 0 === $appearance[$key])) {
            continue;
        }
        if ('select' !== $field['type'] || in_array($key, ['content_alignment', 'products_alignment', 'heading_style', 'description_style'], true)
            || (in_array($key, ['heading_weight', 'description_weight'], true) && 'auto' !== $appearance[$key])) {
            $unit = 'number' === $field['type'] && 'columns' !== $key ? 'px' : '';
            $style .= '--co-' . str_replace('_', '-', $key) . ':' . $appearance[$key] . $unit . ';';
        }
    }
    foreach (['banner_background', 'banner_text'] as $key) {
        if (isset($palette[$key])) {
            $style .= '--co-' . str_replace('_', '-', $key) . ':' . $palette[$key] . ';';
        }
    }
    return $style;
}

function checkout_offer_text(string $key, string $fallback): string
{
    $appearance = checkout_offer_appearance();
    return checkout_offer_escape('' === $appearance[$key] ? $fallback : $appearance[$key]);
}

function checkout_offer_language(): void
{
    if (!defined('CHECKOUT_OFFER_HEADING')) {
        require language::map_to_translation('checkout_offer.php');
    }
}

function checkout_offer_decimal($value): float
{
    if (!is_scalar($value) || !is_numeric($value) || !is_finite((float)$value) || (float)$value < 0) {
        throw new InvalidArgumentException('Enter a finite, non-negative number.');
    }
    return round((float)$value, 4);
}

function checkout_offer_validate_tier(array $input): array
{
    if (!is_string($input['title'] ?? null)) {
        throw new InvalidArgumentException('Enter a valid tier title.');
    }
    $title = trim((string)($input['title'] ?? ''));
    $minimum = checkout_offer_decimal($input['minimum'] ?? '');
    $maximum = '' === ($input['maximum'] ?? '') ? null : checkout_offer_decimal($input['maximum']);
    if ('' === $title || strlen($title) > 120 || (null !== $maximum && $maximum <= $minimum)) {
        throw new InvalidArgumentException('Enter a title (up to 120 characters) and a maximum greater than the minimum.');
    }
    $priority = filter_var($input['priority'] ?? '', FILTER_VALIDATE_INT);
    if (false === $priority || $priority < -100000 || $priority > 100000) {
        throw new InvalidArgumentException('Priority must be an integer between -100000 and 100000.');
    }
    return ['title' => $title, 'minimum' => $minimum, 'maximum' => $maximum, 'priority' => $priority,
        'enabled' => isset($input['enabled']) ? 1 : 0];
}

function checkout_offer_price(float $normal, string $mode, float $value): float
{
    if (!is_finite($normal) || !is_finite($value) || $normal < 0 || $value < 0
        || !in_array($mode, ['fixed', 'percent'], true) || ('percent' === $mode && $value > 100)) {
        throw new InvalidArgumentException('Invalid offer price.');
    }
    return round(min($normal, 'fixed' === $mode ? $value : $normal * (1 - $value / 100)), 4);
}

function checkout_offer_select_tier(array $tiers, float $total): ?array
{
    usort($tiers, static function (array $left, array $right): int {
        return ((int)$right['priority'] <=> (int)$left['priority']) ?: ((int)$left['id'] <=> (int)$right['id']);
    });
    foreach ($tiers as $tier) {
        if (!empty($tier['enabled']) && $total >= (float)$tier['minimum']
            && (null === $tier['maximum'] || $total < (float)$tier['maximum'])) {
            return $tier;
        }
    }
    return null;
}

function checkout_offer_tiers(): array
{
    $tiers = [];
    $query = $GLOBALS['db']->query('SELECT * FROM checkout_offer_tiers WHERE enabled = 1 ORDER BY priority DESC, id');
    while ($row = $query->fetch_assoc()) {
        $tiers[] = $row;
    }
    return $tiers;
}

function checkout_offer_items(int $tierId): array
{
    $items = [];
    $query = $GLOBALS['db']->query('SELECT * FROM checkout_offer_products WHERE tier_id = ' . $tierId . ' ORDER BY id');
    while ($row = $query->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

function checkout_offer_state(): array
{
    $state = $_SESSION['checkout_offer'] ?? [];
    if (!is_array($state) || ($state['customer_id'] ?? 0) !== (int)($_SESSION['customer_id'] ?? 0)
        || empty($_SESSION['cart']->contents)
        || ($state['basket'] ?? '') !== checkout_offer_basket_signature()
        || ($state['currency'] ?? '') !== ($_SESSION['currency'] ?? '')) {
        unset($_SESSION['checkout_offer']);
        return [];
    }
    return $state;
}

function checkout_offer_basket_signature(): string
{
    return hash('sha256', serialize($_SESSION['cart']->contents ?? []));
}

function checkout_offer_shipping_tax(order $order): float
{
    $moduleName = explode('_', (string)($_SESSION['shipping']['id'] ?? ''))[0];
    $shipping = new shipping(is_array($_SESSION['shipping'] ?? null) ? $_SESSION['shipping'] : []);
    $taxClass = (int)($GLOBALS[$moduleName]->tax_class ?? 0);
    if ($taxClass <= 0) {
        return 0.0;
    }
    return (float)Tax::get_rate($taxClass, $order->delivery['country']['id'], $order->delivery['zone_id']);
}

function checkout_offer_baseline_total(order $order, array $state): float
{
    $goods = 0.0;
    foreach ($order->products as $product) {
        if (isset($state['items'][(string)$product['id']])) {
            continue;
        }
        $goods += (float)$product['final_price'] * (int)$product['qty'] * (1 + (float)$product['tax'] / 100);
    }
    if ('virtual' === $order->content_type) {
        return round($goods, 4);
    }
    $shippingCost = (float)($order->info['shipping_cost'] ?? 0);
    if (class_exists('ot_shipping') && ot_shipping::is_eligible_free_shipping($order->delivery['country_id'], $goods)) {
        $shippingCost = 0.0;
    }
    return round($goods + $shippingCost * (1 + checkout_offer_shipping_tax($order) / 100), 4);
}

function checkout_offer_current_tier(order $order, array $state): ?array
{
    $tiers = checkout_offer_tiers();
    if (!empty($state['items'])) {
        foreach ($tiers as $tier) {
            if ((int)$tier['id'] === (int)$state['tier_id']) {
                return $tier;
            }
        }
        return null;
    }
    return checkout_offer_select_tier($tiers, checkout_offer_baseline_total($order, []));
}

function checkout_offer_apply(array $parameters): void
{
    $order = $parameters['order'];
    if (!empty($order->info['checkout_offer_applied'])) {
        return;
    }
    if (!checkout_offer_enabled() || !isset($_SESSION['customer_id'])) {
        unset($_SESSION['checkout_offer']);
        return;
    }
    $state = checkout_offer_state();
    if (empty($state['items'])) {
        return;
    }
    $tier = checkout_offer_current_tier($order, $state);
    if (null === $tier || (int)$tier['id'] !== (int)$state['tier_id']) {
        unset($_SESSION['checkout_offer']);
        return;
    }
    $items = array_column(checkout_offer_items((int)$tier['id']), null, 'products_id');
    foreach ($order->products as &$product) {
        $id = (string)$product['id'];
        $rule = $items[(int)$id] ?? null;
        if (isset($state['items'][$id]) && null !== $rule && 1 === (int)$product['qty']) {
            $product['final_price'] = checkout_offer_price((float)$product['final_price'], $rule['mode'], (float)$rule['value']);
        }
    }
    unset($product);
    // Use Phoenix's own rounding and tax-group calculation for every order line.
    $order->info['subtotal'] = 0;
    $order->info['tax'] = 0;
    $order->info['tax_groups'] = [];
    foreach ($order->products as $product) {
        $parameters['builder']->update_per_product($product);
    }
    $order->info['total'] = $order->info['subtotal'] + (float)$order->info['shipping_cost'];
    if ('true' !== DISPLAY_PRICE_WITH_TAX) {
        $order->info['total'] += $order->info['tax'];
    }
    $order->info['checkout_offer_applied'] = true;
}

function checkout_offer_attributes(Product $product, array $selected): array
{
    $attributes = $product->get('attributes') ?? [];
    $result = [];
    foreach ($attributes as $optionId => $option) {
        $valueId = filter_var($selected[$optionId] ?? '', FILTER_VALIDATE_INT);
        if (false === $valueId || !isset($option['values'][$valueId])) {
            throw new InvalidArgumentException('Choose a valid value for every product option.');
        }
        $result[(int)$optionId] = $valueId;
    }
    if (count($selected) !== count($result)) {
        throw new InvalidArgumentException('Unexpected product options.');
    }
    return $result;
}

function checkout_offer_accept(order $order, int $productId, array $selected): void
{
    if (!checkout_offer_enabled() || empty($_SESSION['customer_id'])) {
        throw new InvalidArgumentException('This offer is unavailable.');
    }
    $state = checkout_offer_state();
    $tier = checkout_offer_current_tier($order, $state);
    $rules = null === $tier ? [] : array_column(checkout_offer_items((int)$tier['id']), null, 'products_id');
    if (!isset($rules[$productId])) {
        throw new InvalidArgumentException('This product is not eligible for the current basket.');
    }
    foreach ($order->products as $line) {
        if ((int)$line['id'] === $productId) {
            throw new InvalidArgumentException('This product is already in your basket.');
        }
    }
    $product = product_by_id::build($productId);
    if (!$product->get('status') || (defined('STOCK_CHECK') && 'true' === STOCK_CHECK && $product->lacks_stock(1))) {
        throw new InvalidArgumentException('This product is unavailable or out of stock.');
    }
    $attributes = checkout_offer_attributes($product, $selected);
    $id = Product::build_uprid($productId, $attributes);
    if (false === $_SESSION['cart']->add_cart($productId, 1, $attributes)) {
        throw new InvalidArgumentException('The product could not be added.');
    }
    if (!$_SESSION['cart']->in_cart($id)) {
        throw new RuntimeException('The basket did not retain the offered product.');
    }
    $state['items'][$id] = true;
    $_SESSION['checkout_offer'] = ['customer_id' => (int)$_SESSION['customer_id'],
        'currency' => $_SESSION['currency'], 'basket' => checkout_offer_basket_signature(),
        'tier_id' => (int)$tier['id'], 'items' => $state['items']];
    // Never retain a shipping quote calculated before the new item was added.
    unset($_SESSION['shipping'], $_SESSION['payment']);
}
