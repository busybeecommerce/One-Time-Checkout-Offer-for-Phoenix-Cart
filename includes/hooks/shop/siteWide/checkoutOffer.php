<?php
declare(strict_types=1);

require_once DIR_FS_CATALOG . 'includes/functions/checkout_offer.php';

class hook_shop_siteWide_checkoutOffer {

    public function listen_injectAppTop(): void
    {
        checkout_offer_state();
    }

    public function listen_cartOrderBuild(array $parameters): void
    {
        checkout_offer_apply($parameters);
    }

    public function listen_injectRedirects(): void
    {
        if ('checkout_payment.php' !== Request::get_page() || 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')
            || !isset($_POST['checkout_offer_product'])) {
            return;
        }
        checkout_offer_language();
        $token = $_POST['formid'] ?? null;
        if (!is_string($token) || !isset($_SESSION['sessiontoken']) || !hash_equals($_SESSION['sessiontoken'], $token)) {
            $GLOBALS['messageStack']->add_session('checkout_payment', CHECKOUT_OFFER_INVALID_REQUEST, 'error');
            Href::redirect($GLOBALS['Linker']->build('checkout_payment.php'));
            return;
        }
        try {
            $productId = filter_var($_POST['checkout_offer_product'], FILTER_VALIDATE_INT);
            $options = $_POST['checkout_offer_options'] ?? [];
            if (false === $productId || $productId <= 0 || !is_array($options) || !is_array($options[$productId] ?? [])) {
                throw new InvalidArgumentException('Invalid product.');
            }
            checkout_offer_accept($GLOBALS['order'], $productId, $options[$productId] ?? []);
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $GLOBALS['messageStack']->add_session('checkout_payment', CHECKOUT_OFFER_UNAVAILABLE, 'error');
            Href::redirect($GLOBALS['Linker']->build('checkout_payment.php'));
            return;
        }
        $GLOBALS['messageStack']->add_session('checkout_shipping', CHECKOUT_OFFER_ADDED, 'success');
        Href::redirect($GLOBALS['Linker']->build('checkout_shipping.php'));
    }

    public function listen_injectBodyEnd(): string
    {
        if ('checkout_payment.php' !== Request::get_page() || !checkout_offer_enabled()) {
            return '';
        }
        return '<script src="ext/checkout_offer/checkout_offer.js?v=1.6.0" defer></script>';
    }

    public function listen_injectSiteStart(): string
    {
        return 'checkout_payment.php' === Request::get_page() && checkout_offer_enabled()
            ? '<link rel="stylesheet" href="ext/checkout_offer/checkout_offer.css?v=1.6.0">' : '';
    }
}
