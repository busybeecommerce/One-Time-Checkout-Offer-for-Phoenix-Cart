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
define('CHECKOUT_OFFER_ENABLED', 'False');
$_SESSION = ['sessiontoken' => 'admin-render-token'];
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
require $root . '/admin/includes/languages/english/checkout_offer.php';

// Only Phoenix bootstrap/template wrappers and database rows are fixtures;
// the complete add-on page, Form, Input and Href classes execute unchanged.
$fixtureRoot = $root . '/build/admin-render-fixture';
if (!is_dir($fixtureRoot . '/includes')) {
    mkdir($fixtureRoot . '/includes', 0777, true);
}
file_put_contents($fixtureRoot . '/includes/application_top.php', '<?php');
file_put_contents($fixtureRoot . '/includes/application_bottom.php', '<?php');
file_put_contents($fixtureRoot . '/includes/template_top.php', '<!doctype html><html><body>');
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
foreach (array_merge($expected, ['name="formid"', 'admin-render-token', 'id="render-complete"', 'class="co-admin-header"', 'href="https://busybeecommerce.co.uk"', 'alt="BusyBee Commerce"', 'https://example.test/store/images/checkout_offer/busybee-logo.png', 'checkout_offer_admin.css?v=1.1.2']) as $text) {
    if (!str_contains($html, $text)) {
        throw new RuntimeException("Admin $scenario output missing: $text");
    }
}
if (substr_count($html, '<form') !== substr_count($html, '</form>')) {
    throw new RuntimeException('Unbalanced admin forms.');
}

$cl_box_groups = [['heading' => 'Tools', 'apps' => []]];
require $reference . '/admin/includes/boxes/reports.php';
require $root . '/admin/includes/languages/english/modules/boxes/reports_checkout_offer.php';
// Phoenix sorts box filenames: the legacy compatibility file is read first.
require $root . '/admin/includes/boxes/checkout_offer.php';
require $root . '/admin/includes/boxes/reports_checkout_offer.php';
if (2 !== count($cl_box_groups) || 'checkout_offer.php' !== ($cl_box_groups[1]['apps'][0]['code'] ?? '')
    || 'Checkout Offers' !== $cl_box_groups[1]['apps'][0]['title']) {
    throw new RuntimeException('Checkout Offers must join Reports without another top-level menu.');
}
file_put_contents($root . '/build/admin-' . $scenario . '.html', $html);
echo "Admin $scenario rendering passed with real Phoenix Form/Href; Reports menu passed.\n";
