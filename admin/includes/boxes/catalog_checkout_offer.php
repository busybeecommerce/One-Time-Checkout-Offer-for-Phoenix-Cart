<?php
declare(strict_types=1);

// Phoenix loads catalog.php before this alphabetically sorted application file.
foreach ($cl_box_groups as &$group) {
    if ($group['heading'] === BOX_HEADING_CATALOG) {
        if (!in_array('checkout_offer.php', array_column($group['apps'], 'code'), true)) {
            $group['apps'][] = [
                'code' => 'checkout_offer.php',
                'title' => MODULES_ADMIN_MENU_CATALOG_CHECKOUT_OFFER,
                'link' => $GLOBALS['Admin']->link('checkout_offer.php'),
            ];
        }
        break;
    }
}
unset($group);
