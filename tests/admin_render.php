<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$reference = realpath($argv[1] ?? '');
if (false === $reference || !is_file($reference . '/includes/version.php')) {
    fwrite(STDERR, "Usage: php tests/admin_render.php <PhoenixCart-reference> [install|setup|tier] [--expect-broken]\n");
    exit(2);
}
$scenario = $argv[2] ?? 'install';
if (!in_array($scenario, ['install', 'setup', 'tier'], true)) {
    throw new InvalidArgumentException('Unknown admin rendering scenario.');
}
define('DIR_FS_CATALOG', $root . '/');
define('SESSION_FORCE_COOKIE_USE', 'True');
define('BOX_HEADING_REPORTS', 'Reports');
define('BOX_HEADING_CATALOG', 'Catalog');
define('CHECKOUT_OFFER_ENABLED', 'False');
define('DEFAULT_CURRENCY', in_array('--currency-eur', $argv, true) ? 'EUR' : (in_array('--currency-jpy', $argv, true) ? 'JPY' : 'GBP'));
$_SESSION = ['sessiontoken' => 'admin-render-token', 'languages_id' => 1];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = 'tier' === $scenario ? ['tier_id' => 1] : [];

$GLOBALS['all_hooks'] = new class {
    public function chain(string $action, array $parameters): array { return $parameters; }
};
$GLOBALS['Admin'] = $Admin = new class {
    public function link(string $page, array $parameters = []): Href { return new Href('https://example.test/admin/', $page, $parameters, false); }
    public function catalog(string $page): Href { return new Href('https://example.test/store/', $page, [], false); }
};
$GLOBALS['db'] = $db = new class($scenario) {
    private string $scenario;
    public function __construct(string $scenario) { $this->scenario = $scenario; }
    public function query(string $sql): object
    {
        $rows = [];
        if (str_contains($sql, 'SHOW TABLES') && 'install' !== $this->scenario) {
            $rows = [['table' => 'checkout_offer_tiers']];
        } elseif (str_contains($sql, 'FROM checkout_offer_tiers')) {
            $rows = [['id' => 1, 'title' => 'Medium basket', 'minimum' => '50', 'maximum' => null, 'priority' => 10, 'enabled' => 1]];
        } elseif (str_contains($sql, 'FROM checkout_offer_products')) {
            $rows = [['id' => 1, 'products_id' => 2, 'tier_id' => 1, 'mode' => 'percent', 'value' => '25']];
        }
        if (str_contains($sql, 'FROM categories c')) {
            $rows = [['categories_id' => 10, 'categories_name' => 'Fruit & veg'], ['categories_id' => 20, 'categories_name' => 'Other']];
        } elseif (str_contains($sql, 'FROM products p')) {
            $rows = [['products_id' => 2, 'products_name' => 'Lime <fresh>', 'categories_id' => 10], ['products_id' => 2, 'products_name' => 'Lime <fresh>', 'categories_id' => 20], ['products_id' => 3, 'products_name' => 'Tomato', 'categories_id' => 10], ['products_id' => 4, 'products_name' => 'Uncategorised', 'categories_id' => null]];
        }
        if (in_array('--without-uncategorised', $GLOBALS['argv'], true) && str_contains($sql, 'FROM products p')) {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => null !== $row['categories_id']));
        }
        if (str_contains($sql, 'FROM currencies')) {
            $rows = [['code' => 'GBP', 'title' => 'Pounds', 'symbol_left' => '£', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => 2, 'value' => 1],
                ['code' => 'EUR', 'title' => 'Euro', 'symbol_left' => '', 'symbol_right' => ' €', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => 2, 'value' => 1.5],
                ['code' => 'JPY', 'title' => 'Yen', 'symbol_left' => '¥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => 0, 'value' => 200]];
        }
        if (str_contains($sql, 'FROM checkout_offer_tiers') && in_array('--finite-tier', $GLOBALS['argv'], true)) {
            $rows[0]['maximum'] = '1234.56';
        }
        return new class($rows) {
            private array $rows;
            public function __construct(array $rows) { $this->rows = $rows; }
            public function fetch_assoc(): ?array { return array_shift($this->rows); }
        };
    }
};
$formFile = is_file($reference . '/includes/system/versioned/1.01.00.07/form.php')
    ? '1.01.00.07/form.php' : '1.0.8.1/form.php';
foreach (['1.0.8.2/text.php', '1.0.8.1/html_element.php', '1.0.8.1/named_html_element.php',
    '1.0.8.1/input.php', $formFile, '1.0.8.5/href.php'] as $file) {
    require $reference . '/includes/system/versioned/' . $file;
}
require $reference . '/includes/system/versioned/1.0.8.6/currencies.php';
$_SESSION['currency'] = 'JPY';
require $root . '/admin/includes/languages/english/checkout_offer.php';
require $reference . '/admin/includes/classes/message_stack.php';
$messageStack = new messageStack();
$messageStack->add(CHECKOUT_OFFER_ADMIN_SAVED, 'success');
$messageStack->add('Warning fixture', 'warning');
$messageStack->add('Error fixture', 'error');

// Only Phoenix bootstrap/template wrappers and database rows are fixtures;
// the complete add-on page, Form, Input and Href classes execute unchanged.
$fixtureRoot = $root . '/build/admin-render-fixture';
if (!is_dir($fixtureRoot . '/includes')) {
    mkdir($fixtureRoot . '/includes', 0777, true);
}
file_put_contents($fixtureRoot . '/includes/application_top.php', '<?php');
file_put_contents($fixtureRoot . '/includes/application_bottom.php', '<?php');
file_put_contents($fixtureRoot . '/includes/template_top.php', '<!doctype html><html><body>' . $messageStack->output());
file_put_contents($fixtureRoot . '/includes/template_bottom.php', '<footer id="render-complete"></footer></body></html>');
$previousDirectory = getcwd();
chdir($fixtureRoot);
ob_start();
$error = null;
try {
    require $root . '/admin/checkout_offer.php';
} catch (Throwable $exception) {
    $error = $exception;
} finally {
    $html = ob_get_clean();
    chdir($previousDirectory);
}
if ('tier' === $scenario) {
    $expectedMinimum = ['GBP' => '£50.00', 'EUR' => '50,00 €', 'JPY' => '¥50'][DEFAULT_CURRENCY];
    $expectedMaximum = in_array('--finite-tier', $argv, true) ? ['GBP' => '£1,234.56', 'EUR' => '1.234,56 €', 'JPY' => '¥1,235'][DEFAULT_CURRENCY] : '∞';
    if (!str_contains($html, $expectedMinimum . ' – ' . $expectedMaximum) || !str_contains($html, '(' . DEFAULT_CURRENCY . ')')) {
        throw new RuntimeException('Tier amounts must use base currency formatting without exchange conversion.');
    }
    if (!str_contains($html, 'name="minimum" value="50.00"')) {
        throw new RuntimeException('Basket amount inputs must render two decimal places.');
    }
    $hasOption = str_contains($html, '<option value="0">Uncategorised</option>');
    if ($hasOption === in_array('--without-uncategorised', $argv, true)) {
        throw new RuntimeException('Uncategorised must appear only when active uncategorised products exist.');
    }
}
if (in_array('--expect-broken', $argv, true)) {
    if (!$error instanceof TypeError || !str_contains($error->getMessage(), 'Form::__construct()')) {
        throw new RuntimeException('Expected the reported strict-type Form rendering failure.');
    }
    echo "Reproduced admin $scenario rendering failure: " . $error->getMessage() . "\n";
    exit(0);
}
if (null !== $error) {
    throw $error;
}
$expected = 'install' === $scenario ? ['Install database tables'] : ['Setup', 'New tier', 'Uninstall', 'name="display_mode"', 'Modal popup', 'name="appearance[modal_width]"', 'name="appearance[background]"', 'name="appearance[content_alignment]"', 'name="appearance[products_alignment]"'];
if (str_contains($html, 'name="accent"') || str_contains($html, 'Accent colour')) {
    throw new RuntimeException('Removed accent setting must not render.');
}
if ('tier' === $scenario) {
    $expected[] = 'name="products_id"';
    $expected[] = 'name="item_id" value="1"';
}
if ('install' !== $scenario) {
    foreach (array_keys(checkout_offer_templates()) as $template) {
        $expected[] = 'name="appearance[template]" value="' . $template . '"';
    }
}
if ('install' !== $scenario) {
    $expected = array_merge($expected, ['data-co-tab="manual"', 'id="co-panel-manual"', 'id="troubleshooting"', 'Create your first offer']);
}
foreach (array_merge($expected, ['name="formid"', 'admin-render-token', 'id="render-complete"', 'class="co-admin-header"', 'href="https://busybeecommerce.co.uk"', 'alt="BusyBee Commerce"', 'https://example.test/store/images/checkout_offer/busybee-logo.png', 'checkout_offer_admin.css?v=1.7.24']) as $text) {
    if (!str_contains($html, $text)) {
        throw new RuntimeException("Admin $scenario output missing: $text");
    }
}
if (substr_count($html, '<form') !== substr_count($html, '</form>')) {
    throw new RuntimeException('Unbalanced admin forms.');
}

$cl_box_groups = [['heading' => 'Tools', 'apps' => []]];
require $root . '/admin/includes/languages/english/modules/boxes/catalog_checkout_offer.php';
require $root . '/admin/includes/languages/english/modules/boxes/reports_checkout_offer.php';
// Use Phoenix's ascending filename loading order with all legacy files installed.
$boxes = [
    'catalog.php' => $reference . '/admin/includes/boxes/catalog.php',
    'reports.php' => $reference . '/admin/includes/boxes/reports.php',
];
foreach (glob($root . '/admin/includes/boxes/*.php') as $file) {
    $boxes[basename($file)] = $file;
}
uksort($boxes, 'strnatcasecmp');
foreach ($boxes as $file) {
    require $file;
}
// An accidental repeated inclusion must not add a duplicate application.
require $root . '/admin/includes/boxes/catalog_checkout_offer.php';
$offerGroups = [];
foreach ($cl_box_groups as $group) {
    foreach ($group['apps'] as $app) {
        if ('checkout_offer.php' === $app['code']) {
            $offerGroups[] = $group['heading'];
            if ('Checkout Offers' !== $app['title'] || !str_contains((string)$app['link'], 'checkout_offer.php')) {
                throw new RuntimeException('Catalog offer registration has an incorrect title/link.');
            }
        }
    }
}
if (3 !== count($cl_box_groups) || ['Catalog'] !== $offerGroups) {
    throw new RuntimeException('Checkout Offers must register once in Catalog and never in Reports/legacy menus.');
}
file_put_contents($root . '/build/admin-' . $scenario . '.html', $html);
echo "Admin $scenario rendering passed with real Phoenix Form/Href; Catalog and inert legacy menus passed.\n";
