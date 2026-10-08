<?php
declare(strict_types=1);

function checkout_offer_schema_ready(): bool
{
    return (bool)$GLOBALS['db']->query("SHOW TABLES LIKE 'checkout_offer_tiers'")->fetch_assoc();
}

function checkout_offer_install(): void
{
    $GLOBALS['db']->query('CREATE TABLE IF NOT EXISTS checkout_offer_tiers (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(120) NOT NULL, minimum DECIMAL(15,4) NOT NULL DEFAULT 0,
        maximum DECIMAL(15,4) NULL, priority INT NOT NULL DEFAULT 0, enabled TINYINT NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $GLOBALS['db']->query("CREATE TABLE IF NOT EXISTS checkout_offer_products (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, tier_id INT UNSIGNED NOT NULL,
        products_id INT UNSIGNED NOT NULL, mode VARCHAR(10) NOT NULL, value DECIMAL(15,4) NOT NULL,
        UNIQUE KEY tier_product (tier_id, products_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (['CHECKOUT_OFFER_ENABLED' => 'False', 'CHECKOUT_OFFER_ACCENT' => '#6f42c1'] as $key => $value) {
        $exists = $GLOBALS['db']->query("SELECT configuration_id FROM configuration WHERE configuration_key = '" . $key . "'")->fetch_assoc();
        if (!$exists) {
            $GLOBALS['db']->perform('configuration', ['configuration_title' => $key, 'configuration_key' => $key,
                'configuration_value' => $value, 'configuration_description' => 'Managed in Checkout offers',
                'configuration_group_id' => 6, 'sort_order' => 0, 'date_added' => 'NOW()']);
        }
    }
}

function checkout_offer_admin_tier(int $id): ?array
{
    $row = $GLOBALS['db']->query('SELECT * FROM checkout_offer_tiers WHERE id = ' . $id)->fetch_assoc();
    return $row ?: null;
}

function checkout_offer_admin_action(string $action, array $input): void
{
    $db = $GLOBALS['db'];
    if ('install' === $action) {
        checkout_offer_install();
        return;
    }
    if (!checkout_offer_schema_ready()) {
        throw new InvalidArgumentException('Install the database tables first.');
    }
    if ('settings' === $action) {
        $accent = (string)($input['accent'] ?? '');
        if (!preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
            throw new InvalidArgumentException('Enter a six-digit hexadecimal accent colour.');
        }
        foreach (['CHECKOUT_OFFER_ENABLED' => isset($input['enabled']) ? 'True' : 'False', 'CHECKOUT_OFFER_ACCENT' => $accent] as $key => $value) {
            $db->perform('configuration', ['configuration_value' => $value], 'update', "configuration_key = '" . $key . "'");
        }
        return;
    }
    if ('uninstall' === $action) {
        if ('yes' !== ($input['confirm_remove'] ?? '')) {
            throw new InvalidArgumentException('Confirm removal of the offer configuration.');
        }
        $db->query("DELETE FROM configuration WHERE configuration_key IN ('CHECKOUT_OFFER_ENABLED', 'CHECKOUT_OFFER_ACCENT')");
        $db->query('DROP TABLE IF EXISTS checkout_offer_products');
        $db->query('DROP TABLE IF EXISTS checkout_offer_tiers');
        return;
    }
    $tierId = (int)($input['tier_id'] ?? 0);
    if ('save_tier' === $action) {
        $tier = checkout_offer_validate_tier($input);
        // Phoenix's database API requires the literal NULL marker for SQL NULL.
        if (null === $tier['maximum']) {
            $tier['maximum'] = 'NULL';
        }
        if ($tierId > 0 && null === checkout_offer_admin_tier($tierId)) {
            throw new InvalidArgumentException('The tier no longer exists.');
        }
        if ($tierId > 0) {
            $db->perform('checkout_offer_tiers', $tier, 'update', 'id = ' . $tierId);
        } else {
            $db->perform('checkout_offer_tiers', $tier);
        }
        return;
    }
    if (null === checkout_offer_admin_tier($tierId)) {
        throw new InvalidArgumentException('Choose an existing tier.');
    }
    if ('delete_tier' === $action) {
        $db->query('START TRANSACTION');
        try {
            $db->query('DELETE FROM checkout_offer_products WHERE tier_id = ' . $tierId);
            $db->query('DELETE FROM checkout_offer_tiers WHERE id = ' . $tierId);
            $db->query('COMMIT');
        } catch (Throwable $exception) {
            $db->query('ROLLBACK');
            throw $exception;
        }
        return;
    }
    $itemId = (int)($input['item_id'] ?? 0);
    if ('delete_product' === $action) {
        $db->query('DELETE FROM checkout_offer_products WHERE id = ' . $itemId . ' AND tier_id = ' . $tierId);
        return;
    }
    if ('save_product' !== $action) {
        throw new InvalidArgumentException('Unknown action.');
    }
    $productId = filter_var($input['products_id'] ?? '', FILTER_VALIDATE_INT);
    $mode = (string)($input['mode'] ?? '');
    $value = checkout_offer_decimal($input['value'] ?? '');
    if (false === $productId || $productId <= 0 || !product_by_id::administer($productId)->get('status')) {
        throw new InvalidArgumentException('Choose an active product ID.');
    }
    checkout_offer_price(100.0, $mode, $value);
    $duplicate = $db->query('SELECT id FROM checkout_offer_products WHERE tier_id = ' . $tierId . ' AND products_id = ' . $productId . ' AND id <> ' . $itemId)->fetch_assoc();
    if ($duplicate) {
        throw new InvalidArgumentException('This product already belongs to the tier.');
    }
    $data = ['tier_id' => $tierId, 'products_id' => $productId, 'mode' => $mode, 'value' => $value];
    if ($itemId > 0) {
        $db->perform('checkout_offer_products', $data, 'update', 'id = ' . $itemId . ' AND tier_id = ' . $tierId);
    } else {
        $db->perform('checkout_offer_products', $data);
    }
}

function checkout_offer_admin_form(string $action, array $hidden = []): string
{
    $html = (string)new Form('checkout_offer_' . $action, $GLOBALS['Admin']->link('checkout_offer.php'), 'post');
    foreach (['action' => $action] + $hidden as $key => $value) {
        $html .= '<input type="hidden" name="' . checkout_offer_escape($key) . '" value="' . checkout_offer_escape((string)$value) . '">';
    }
    return $html;
}
