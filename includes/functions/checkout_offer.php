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
