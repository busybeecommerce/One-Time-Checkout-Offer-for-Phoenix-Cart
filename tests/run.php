<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$reference = $argv[1] ?? '';
if (!is_file($reference . '/includes/version.php')) {
    fwrite(STDERR, "Usage: php tests/run.php <PhoenixCart-reference>\n");
    exit(2);
}
$reference = realpath($reference);
$phoenixVersion = trim(file_get_contents($reference . '/includes/version.php'));
$checks = 0;
function expect(bool $condition, string $message): void
{
    $GLOBALS['checks']++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function near(float $actual, float $expected, string $message): void
{
    expect(abs($actual - $expected) < 0.0001, $message . ": $actual != $expected");
}
function rejected(callable $call, string $message): void
{
    try {
        $call();
    } catch (InvalidArgumentException $exception) {
        expect(true, $message);
        return;
    }
    expect(false, $message);
}

$contracts = [
    'includes/version.php' => [$phoenixVersion],
    'includes/system/versioned/1.0.7.10/cart_order_builder.php' => ["cat('cartOrderBuild'", 'update_per_product'],
    'includes/system/versioned/1.0.7.10/order.php' => ['database_order_builder::build'],
    'includes/system/versioned/1.0.8.1/hooks.php' => ["cat('injectAppTop')", 'call_user_func'],
    'templates/default/includes/pages/checkout_payment.php' => ["cat('injectFormDisplay')", "new Form('checkout_payment'", "'check_form'", ', true)'],
    'templates/default/includes/components/template_top.php' => ["cat('injectRedirects')"],
    'templates/default/includes/components/template_bottom.php' => ["cat('injectBodyEnd')"],
    'includes/system/segments/checkout/insert_order.php' => ["'final_price' => \$product['final_price']", "'products_tax' => \$product['tax']"],
    'admin/includes/classes/admin.php' => ['function catalog(', 'catalog_linker->build'],
];
expect(in_array($phoenixVersion, ['1.1.0.6', '1.1.0.8'], true), 'Unsupported Phoenix reference version');
foreach ($contracts as $file => $needles) {
    $source = file_get_contents($reference . '/' . $file);
    foreach ($needles as $needle) {
        expect(false !== strpos($source, $needle), "Phoenix contract missing: $file: $needle");
    }
}

$manifest = file($root . '/package-manifest.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
expect(count($manifest) === count(array_unique($manifest)), 'Duplicate manifest paths');
foreach ($manifest as $file) {
    expect(is_file($root . '/' . $file), "Missing package file: $file");
}
foreach (['admin', 'includes', 'ext', 'images'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        expect(in_array($relative, $manifest, true), "Unmanifested runtime file: $relative");
        if ('php' === $file->getExtension()) {
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $status);
            expect(0 === $status, "PHP lint failed: $relative: " . implode("\n", $output));
            $output = [];
        }
    }
}

define('DIR_FS_CATALOG', $root . '/');
define('DISPLAY_PRICE_WITH_TAX', in_array('--net', $argv, true) ? 'false' : 'true');
define('CHECKOUT_OFFER_ENABLED', 'True');
define('CHECKOUT_OFFER_ACCENT', '#123456');
if (in_array('--modal', $argv, true)) {
    define('CHECKOUT_OFFER_DISPLAY_MODE', 'modal');
}
$fixtureAppearance = in_array('--styled', $argv, true) ? ['background' => '#eef6ff', 'radius' => 18, 'padding' => 24, 'image_height' => 180, 'columns' => 2, 'button_style' => 'solid', 'modal_width' => 800, 'content_alignment' => 'center', 'products_alignment' => 'center'] : [];
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--template=')) {
        $fixtureAppearance['template'] = substr($argument, 11);
    }
}
if ($fixtureAppearance) {
    define('CHECKOUT_OFFER_APPEARANCE', json_encode($fixtureAppearance, JSON_THROW_ON_ERROR));
}
define('STOCK_CHECK', 'true');
define('DEFAULT_ORDERS_STATUS_ID', 1);
define('DEFAULT_CURRENCY', 'GBP');
define('STORE_COUNTRY', 1);
define('STORE_ZONE', 1);

class offer_test_result {
    private array $rows;
    public function __construct(array $rows) { $this->rows = array_values($rows); }
    public function fetch_assoc(): ?array { return array_shift($this->rows); }
}
class offer_test_db {
    public array $tiers = [['id' => 1, 'title' => 'Medium', 'minimum' => 100, 'maximum' => 200, 'priority' => 1, 'enabled' => 1]];
    public array $items = [['id' => 1, 'tier_id' => 1, 'products_id' => 2, 'mode' => 'percent', 'value' => 25],
        ['id' => 2, 'tier_id' => 1, 'products_id' => 3, 'mode' => 'fixed', 'value' => 8]];
    public array $products = [];
    public array $writes = [];
    public function query(string $sql): offer_test_result
    {
        if (str_contains($sql, 'FROM currencies')) {
            return new offer_test_result([['code' => 'GBP', 'title' => 'Pounds', 'symbol_left' => '£', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => 2, 'value' => 1],
                ['code' => 'USD', 'title' => 'Dollars', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => 2, 'value' => 1.5]]);
        }
        if (str_contains($sql, 'FROM checkout_offer_tiers')) {
            return new offer_test_result(array_filter($this->tiers, static fn(array $row): bool => (bool)$row['enabled']));
        }
        if (str_contains($sql, 'FROM checkout_offer_products')) {
            return new offer_test_result($this->items);
        }
        if (preg_match('/p.products_id = (\d+)/', $sql, $match)) {
            return new offer_test_result(isset($this->products[(int)$match[1]]) ? [$this->products[(int)$match[1]]] : []);
        }
        if (str_contains($sql, "SHOW TABLES LIKE")) {
            return new offer_test_result([['table' => 'checkout_offer_tiers']]);
        }
        if (str_contains($sql, 'SELECT configuration_id')) {
            return new offer_test_result([]);
        }
        $this->writes[] = $sql;
        return new offer_test_result([]);
    }
    public function perform(string $table, array $data, string $mode = 'insert', string $where = ''): void
    {
        $this->writes[] = [$table, $data, $mode, $where];
    }
}
class offer_test_hooks {
    public function cat(string $action, $parameters = []): string
    {
        if ('cartOrderBuild' === $action) {
            (new hook_shop_siteWide_checkoutOffer())->listen_cartOrderBuild($parameters);
        }
        return '';
    }
}
class offer_test_cart {
    public array $contents = [1 => ['qty' => 1]];
    public string $cartID = 'initial';
    public function get_products(): array
    {
        $products = [];
        foreach ($this->contents as $id => $data) {
            $product = product_by_id::build((int)$id);
            $product->set('id', $id);
            $product->set('quantity', $data['qty']);
            $product->set('attribute_selections', $data['attributes'] ?? []);
            $product->set('final_price', (float)$product->get('base_price') + $this->attributes_price((int)$id, $data['attributes'] ?? []));
            $products[] = $product;
        }
        return $products;
    }
    public function attributes_price(int $id, array $selected): float
    {
        $price = 0.0;
        foreach ($selected as $option => $value) {
            $attribute = $GLOBALS['db']->products[$id]['attributes'][$option]['values'][$value];
            $price += ('-' === $attribute['prefix'] ? -1 : 1) * $attribute['price'];
        }
        return $price;
    }
    public function get_content_type(): string { return 'physical'; }
    public function in_cart($id): bool { return isset($this->contents[$id]); }
    public function add_cart(int $id, int $quantity, array $attributes)
    {
        $uprid = Product::build_uprid($id, $attributes);
        $this->contents[$uprid] = ['qty' => $quantity, 'attributes' => $attributes];
        $this->cartID = 'changed-' . count($this->contents);
        return 'Product';
    }
}
class shipping {
    public function __construct(array $selected = []) { $GLOBALS['flat'] = (object)['tax_class' => 1]; }
}
class ot_shipping {
    public static bool $free = false;
    public static function is_eligible_free_shipping($country, $amount): bool { return self::$free && $amount >= 100; }
}
class language {
    public static function map_to_translation(string $path): string { return DIR_FS_CATALOG . 'includes/languages/english/' . $path; }
}
class Request {
    public static function get_page(): string { return 'checkout_payment.php'; }
}
class Href {
    public static string $last = '';
    public static function redirect($url): void { self::$last = (string)$url; }
}

$GLOBALS['db'] = new offer_test_db();
$GLOBALS['hooks'] = $GLOBALS['all_hooks'] = new offer_test_hooks();
$GLOBALS['Linker'] = new class {
    public function build(string $page, array $parameters = []): string { return $page; }
};
$GLOBALS['messageStack'] = new class {
    public array $messages = [];
    public function add_session(string $scope, string $message, string $type): void { $this->messages[] = [$scope, $message, $type]; }
};
$GLOBALS['customer'] = new class {
    public function fetch_to_address($id): array { return ['country_id' => 1, 'zone_id' => 1, 'country' => ['id' => 1]]; }
};
$GLOBALS['customer_data'] = new class {
    public function get(string $key, array $address): int { return 1; }
};

$versioned = $reference . '/includes/system/versioned/';
foreach (['1.0.8.2/text.php', '1.0.9.1/guarantor.php', '1.0.7.other/1.0.7.12/capabilities_manager.php', '1.0.7.other/1.0.7.12/product_loader.php',
    '1.0.7.other/1.0.7.12/product_builder.php', '1.0.7.other/1.0.7.12/product.php',
    '1.0.7.other/1.0.7.12/product_by_id.php', '1.0.8.3/tax.php', '1.0.8.6/currencies.php',
    '1.0.7.10/cart_order_builder.php', '1.0.7.10/order.php'] as $file) {
    require $versioned . $file;
}
// Seed Phoenix's tax cache, avoiding a mysqli server while retaining real tax calculations.
$taxCache = new ReflectionProperty(Tax::class, 'taxes');
$taxCache->setAccessible(true);
$taxCache->setValue(null, [0 => [1 => [1 => ['rate' => 0, 'description' => 'No VAT']]], 1 => [1 => [1 => ['rate' => 20, 'description' => 'VAT 20%']]]]);
$GLOBALS['currencies'] = new currencies();
require $root . '/includes/functions/checkout_offer.php';
require $root . '/includes/hooks/shop/siteWide/checkoutOffer.php';
require $root . '/includes/hooks/shop/checkout_payment/checkoutOffer.php';
require $root . '/admin/includes/functions/checkout_offer_admin.php';

$_SESSION = ['customer_id' => 7, 'currency' => 'GBP', 'languages_id' => 1, 'sendto' => 1, 'billto' => 1,
    'shipping' => ['id' => 'flat_flat', 'title' => 'Flat delivery', 'cost' => 10], 'sessiontoken' => 'secret', 'cart' => new offer_test_cart()];
foreach ([1 => 100, 2 => 20, 3 => 12] as $id => $price) {
    $GLOBALS['db']->products[$id] = ['id' => $id, 'status' => 1, 'name' => 'Test <product> ' . $id, 'model' => 'P' . $id,
        'base_price' => $price, 'price' => $price, 'weight' => 1, 'in_stock' => 5, 'tax_class_id' => 1,
        'tax_rate' => 20, 'link' => 'product_info.php', 'attributes' => [], 'image' => 'test.png'];
}
$GLOBALS['db']->products[3]['attributes'] = [4 => ['name' => 'Size', 'values' => [8 => ['name' => 'Large', 'prefix' => '+', 'price' => 3]]]];

near(checkout_offer_price(20, 'percent', 25), 15, 'Percentage price');
near(checkout_offer_price(20, 'fixed', 8), 8, 'Fixed price');
near(checkout_offer_price(5, 'fixed', 8), 5, 'Never raise normal price');
near(checkout_offer_price(20, 'percent', 100), 0, 'Free offer');
foreach ([['percent', 101], ['percent', -1], ['bad', 10], ['fixed', INF]] as [$mode, $value]) {
    rejected(static fn() => checkout_offer_price(20, $mode, $value), 'Reject invalid price');
}
$tiers = [['id' => 3, 'enabled' => 1, 'minimum' => 100, 'maximum' => null, 'priority' => 2],
    ['id' => 2, 'enabled' => 1, 'minimum' => 100, 'maximum' => 200, 'priority' => 2],
    ['id' => 1, 'enabled' => 1, 'minimum' => 0, 'maximum' => 100, 'priority' => 1]];
expect(1 === checkout_offer_select_tier($tiers, 99.99)['id'], 'Below boundary');
expect(2 === checkout_offer_select_tier($tiers, 100)['id'], 'Inclusive minimum and priority tie');
expect(3 === checkout_offer_select_tier($tiers, 200)['id'], 'Exclusive maximum');
expect(null === checkout_offer_select_tier([], 10), 'No tier');
rejected(static fn() => checkout_offer_validate_tier(['title' => 'x', 'minimum' => 20, 'maximum' => 10, 'priority' => 0]), 'Reject inverted tier');
rejected(static fn() => checkout_offer_decimal('NaN'), 'Reject malformed number');

$GLOBALS['order'] = new order();
near(checkout_offer_baseline_total($GLOBALS['order'], []), 132, 'Eligibility includes goods and delivery VAT');
ot_shipping::$free = true;
near(checkout_offer_baseline_total($GLOBALS['order'], []), 120, 'Free shipping eligibility');
ot_shipping::$free = false;
$html = (new hook_shop_checkout_payment_checkoutOffer())->listen_injectFormDisplay();
expect(!defined('DIR_WS_IMAGES'), 'Do not mask unsupported legacy image constants in tests');
expect(str_contains($html, 'src="images/test.png"'), 'Offer image uses the Phoenix images path without legacy constants');
expect(str_contains($html, 'data-display-mode="' . checkout_offer_display_mode() . '"'), 'Configured display mode is rendered');
expect(str_contains($html, '--co-image-height:'), 'Appearance values rendered as scoped CSS properties');
expect(str_contains($html, 'data-template="' . checkout_offer_appearance()['template'] . '"'), 'Validated template is rendered');
expect(str_contains($html, 'data-content-alignment="' . checkout_offer_appearance()['content_alignment'] . '"'), 'Validated image/content alignment is rendered');
expect(str_contains($html, 'data-offer-saving') && str_contains($html, 'data-button-price'), 'Savings and price-labelled button render without JavaScript');
expect(str_contains((new hook_shop_siteWide_checkoutOffer())->listen_injectSiteStart(), 'checkout_offer.css'), 'Appearance stylesheet is loaded');
if (in_array('--render', $argv, true)) {
    $renderDirectory = $root . '/build';
    if (!is_dir($renderDirectory)) {
        mkdir($renderDirectory, 0777, true);
    }
    $suffix = in_array('--modal', $argv, true) ? 'modal' : 'inline';
    if ('classic' !== checkout_offer_appearance()['template']) {
        $suffix .= '-' . checkout_offer_appearance()['template'];
    }
    file_put_contents($renderDirectory . '/storefront-' . $suffix . '.html',
        '<form id="check_form" method="post" action="checkout_confirmation.php"><input type="hidden" name="formid" value="secret">'
        . '<h1>Payment From You</h1><div id="payment-methods"><h2>Payment Method</h2><input type="radio" name="payment" value="cod" checked>Cash on delivery</div>'
        . $html . '<button id="continue" type="submit">Continue</button></form>');
}
// Catalogue display tax can differ from the selected checkout delivery address.
$GLOBALS['db']->products[2]['tax_rate'] = 0;
$addressHtml = (new hook_shop_checkout_payment_checkoutOffer())->listen_injectFormDisplay();
expect(str_contains($addressHtml, 'data-tax="20"'), 'Cards use checkout tax address rather than catalogue tax');
$GLOBALS['db']->products[2]['tax_rate'] = 20;
expect(str_contains($html, 'checkout_offer_options[3][4]'), 'Option fields have server-readable names');
expect(str_contains($html, 'Test &lt;product&gt;'), 'Product title escaped');
expect(!str_contains($html, '#123456') && !str_contains($html, '--co-accent'), 'Legacy accent colour is not rendered');
expect(!str_contains($html, 'value="1" formaction'), 'Existing product hidden');

$paymentHook = new hook_shop_checkout_payment_checkoutOffer();
$savedCustomer = $_SESSION['customer_id'];
unset($_SESSION['customer_id']);
expect('' === $paymentHook->listen_injectFormDisplay(), 'No offers rendered without a customer');
$_SESSION['customer_id'] = $savedCustomer;
$GLOBALS['db']->tiers[0]['enabled'] = 0;
expect('' === $paymentHook->listen_injectFormDisplay(), 'No modal content for a disabled eligible tier');
$GLOBALS['db']->tiers[0]['enabled'] = 1;
$savedItems = $GLOBALS['db']->items;
$GLOBALS['db']->items = [];
expect('' === $paymentHook->listen_injectFormDisplay(), 'No modal content when no offered products exist');
$GLOBALS['db']->items = $savedItems;
foreach ([2, 3] as $id) $GLOBALS['db']->products[$id]['status'] = 0;
expect('' === $paymentHook->listen_injectFormDisplay(), 'No modal content when all offered products are inactive');
foreach ([2, 3] as $id) {
    $GLOBALS['db']->products[$id]['status'] = 1;
    $GLOBALS['db']->products[$id]['in_stock'] = 0;
}
expect('' === $paymentHook->listen_injectFormDisplay(), 'No modal content when all offered products lack stock');
foreach ([2, 3] as $id) $GLOBALS['db']->products[$id]['in_stock'] = 5;

checkout_offer_accept($GLOBALS['order'], 2, []);
expect(!isset($_SESSION['shipping']), 'Adding invalidates shipping quote');
expect($_SESSION['cart']->in_cart(2), 'Product added');
$savedState = $_SESSION['checkout_offer'];
// Phoenix changes cartID when the shipping page is visited, without changing contents.
$_SESSION['cart']->cartID = 'shipping-validation';
expect([] !== checkout_offer_state(), 'Shipping cartID regeneration preserves acceptance');
$_SESSION['shipping'] = ['id' => 'flat_flat', 'title' => 'Flat delivery', 'cost' => 10];
$GLOBALS['order'] = new order();
near((float)$GLOBALS['order']->products[1]['final_price'], 15, 'Real order builder applies offer line price');
near((float)$GLOBALS['order']->info['tax'], 23, 'Tax recalculated');
near((float)$GLOBALS['order']->info['subtotal'], 'true' === DISPLAY_PRICE_WITH_TAX ? 138 : 115, 'Subtotal uses Phoenix tax display mode');
near((float)$GLOBALS['order']->info['total'], 148, 'Order total before delivery tax');
near((float)$GLOBALS['order']->info['tax_groups']['VAT 20%'], 23, 'Tax group recalculated');
$repeat = new order();
near((float)$repeat->products[1]['final_price'], 15, 'Repeated order construction is idempotent');
rejected(static fn() => checkout_offer_accept($GLOBALS['order'], 2, []), 'Reject duplicate acceptance');
rejected(static fn() => checkout_offer_accept($GLOBALS['order'], 99, []), 'Reject unoffered product');
rejected(static fn() => checkout_offer_accept($GLOBALS['order'], 3, [4 => 999]), 'Reject invalid option');
checkout_offer_accept($GLOBALS['order'], 3, [4 => 8]);
$_SESSION['shipping'] = ['id' => 'flat_flat', 'title' => 'Flat delivery', 'cost' => 10];
$GLOBALS['order'] = new order();
near((float)$GLOBALS['order']->products[2]['final_price'], 8, 'Fixed price includes option surcharge');
near((float)$GLOBALS['order']->products[2]['attributes'][0]['price'], 3, 'Phoenix retains selected attribute data');
$state = $_SESSION['checkout_offer'];
$_SESSION['customer_id'] = 8;
expect([] === checkout_offer_state(), 'Another customer cannot inherit acceptance');
$_SESSION['customer_id'] = 7;
$_SESSION['checkout_offer'] = $state;
$_SESSION['currency'] = 'USD';
expect([] === checkout_offer_state(), 'Currency change expires acceptance');
$_SESSION['currency'] = 'GBP';
$_SESSION['checkout_offer'] = $state;
$_SESSION['cart']->contents[2]['qty'] = 2;
expect([] === checkout_offer_state(), 'Quantity change expires acceptance');
$normal = new order();
near((float)$normal->products[1]['final_price'], 20, 'Normal price restored');
$_SESSION['cart']->contents[2]['qty'] = 1;
$_SESSION['checkout_offer'] = $state;
$GLOBALS['db']->tiers[0]['enabled'] = 0;
$normal = new order();
near((float)$normal->products[1]['final_price'], 20, 'Disabled tier revokes discount');
$GLOBALS['db']->tiers[0]['enabled'] = 1;
$_SESSION['checkout_offer'] = $state;
$before = serialize($normal);
$beforePricedOrder = serialize($GLOBALS['order']);
(new hook_shop_siteWide_checkoutOffer())->listen_cartOrderBuild(['order' => $GLOBALS['order'], 'builder' => new cart_order_builder($GLOBALS['order'])]);
expect(serialize($normal) === $before, 'Other order objects remain unchanged');
expect(serialize($GLOBALS['order']) === $beforePricedOrder, 'Repeating hook on same object never compounds discount');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['checkout_offer_product' => '3', 'formid' => ['bad']];
$beforeCart = serialize($_SESSION['cart']->contents);
(new hook_shop_siteWide_checkoutOffer())->listen_injectRedirects();
expect(serialize($_SESSION['cart']->contents) === $beforeCart, 'Array/invalid token cannot mutate basket');
expect('checkout_payment.php' === Href::$last, 'Invalid token redirects safely');
$_SESSION['cart']->contents = [];
(new hook_shop_siteWide_checkoutOffer())->listen_injectAppTop();
expect(!isset($_SESSION['checkout_offer']), 'Empty completed basket expires acceptance');

$GLOBALS['db']->writes = [];
checkout_offer_install();
expect(5 === count($GLOBALS['db']->writes), 'Installer creates two tables and three settings');
rejected(static fn() => checkout_offer_admin_action('settings', ['appearance' => ['background' => 'red;display:none']]), 'Reject CSS injection');
rejected(static fn() => checkout_offer_admin_action('uninstall', []), 'Uninstall requires confirmation');
checkout_offer_admin_action('settings', ['enabled' => 'on']);
$settingsWrites = array_slice($GLOBALS['db']->writes, -4, 3);
expect('True' === $settingsWrites[0][1]['configuration_value'], 'Enable setting saved');
rejected(static fn() => checkout_offer_admin_action('settings', ['display_mode' => 'invalid']), 'Reject invalid display mode');
checkout_offer_admin_action('settings', ['display_mode' => 'modal', 'appearance' => ['radius' => 20, 'background' => '#fafafa', 'content_alignment' => 'right', 'products_alignment' => 'center']]);
$settingsWrites = array_slice($GLOBALS['db']->writes, -4, 3);
expect('modal' === $settingsWrites[1][1]['configuration_value'], 'Modal choice saved');
expect(20 === json_decode($settingsWrites[2][1]['configuration_value'], true)['radius'], 'Appearance saved');
expect('right' === json_decode($settingsWrites[2][1]['configuration_value'], true)['content_alignment'], 'Modal alignment saved');
expect('update' === $settingsWrites[2][2], 'Appearance updates store configuration');
expect(str_contains($GLOBALS['db']->writes[count($GLOBALS['db']->writes) - 1], "configuration_key = 'CHECKOUT_OFFER_ACCENT'"), 'Saving Setup removes legacy accent setting');
expect(!str_contains(checkout_offer_style(checkout_offer_appearance()), 'accent'), 'Legacy accent has no styling effect');
foreach (['radius' => 41, 'image_height' => 301, 'columns' => 5, 'background' => 'red;display:none', 'shadow' => 'invalid', 'content_alignment' => 'justify', 'products_alignment' => 'center;display:none', 'template' => 'red" onclick="bad'] as $key => $value) {
    rejected(static fn() => checkout_offer_sanitise_appearance([$key => $value], true), 'Reject out-of-range or unsafe appearance');
}
expect(6 === checkout_offer_sanitise_appearance(['radius' => 41])['radius'], 'Invalid stored appearance safely defaults');
expect(0 === checkout_offer_sanitise_appearance(['radius' => 0], true)['radius'], 'Minimum radius accepted');
expect(300 === checkout_offer_sanitise_appearance(['image_height' => 300], true)['image_height'], 'Maximum image size accepted');
foreach (array_keys(checkout_offer_templates()) as $template) {
    expect($template === checkout_offer_sanitise_appearance(['template' => $template], true)['template'], 'Known template accepted');
}
expect('classic' === checkout_offer_sanitise_appearance(['template' => 'unknown'])['template'], 'Invalid stored template falls back to Classic');
expect(str_contains(checkout_offer_style(checkout_offer_sanitise_appearance(['template' => 'red'])), '--co-button-background:#d91920;'), 'Template palette applied');
checkout_offer_admin_action('save_tier', ['title' => 'Unlimited', 'minimum' => '0', 'maximum' => '', 'priority' => '3', 'enabled' => 'on']);
$tierWrite = $GLOBALS['db']->writes[count($GLOBALS['db']->writes) - 1];
expect('NULL' === $tierWrite[1]['maximum'], 'Unlimited tiers use Phoenix SQL NULL marker');
expect(3 === $tierWrite[1]['priority'], 'Tier priority saved');
$virtual = clone $GLOBALS['order'];
$virtual->content_type = 'virtual';
near(checkout_offer_baseline_total($virtual, []), 147.6, 'Virtual order eligibility excludes delivery');

$_SESSION['cart'] = new offer_test_cart();
$_SESSION['shipping'] = ['id' => 'flat_flat', 'title' => 'Flat delivery', 'cost' => 10];
$GLOBALS['order'] = new order();
$GLOBALS['db']->products[2]['in_stock'] = 0;
rejected(static fn() => checkout_offer_accept($GLOBALS['order'], 2, []), 'Stock checked at acceptance');
$GLOBALS['db']->products[2]['in_stock'] = 5;
$_POST = ['checkout_offer_product' => '2', 'formid' => 'secret'];
(new hook_shop_siteWide_checkoutOffer())->listen_injectRedirects();
expect('checkout_shipping.php' === Href::$last, 'Valid one-click POST redirects to delivery validation');
expect($_SESSION['cart']->in_cart(2), 'Valid POST adds chosen product');
expect(!isset($_SESSION['shipping']), 'Valid POST cannot keep outdated delivery quote');

echo "Checkout Offer: $checks checks passed (Phoenix $phoenixVersion; tax display " . DISPLAY_PRICE_WITH_TAX . ").\n";
if (!in_array('--net', $argv, true)) {
    $childArguments = array_filter(array_slice($argv, 2), static fn(string $argument): bool => '--render' !== $argument);
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($reference) . ' --net ' . implode(' ', array_map('escapeshellarg', $childArguments)), $status);
    expect(0 === $status, 'Tax-exclusive test process failed');
}
