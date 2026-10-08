<?php
declare(strict_types=1);

foreach ($cl_box_groups as &$group) {
    if ($group['heading'] === BOX_HEADING_REPORTS) {
        $group['apps'][] = [
            'code' => 'checkout_offer.php',
            'title' => MODULES_ADMIN_MENU_REPORTS_CHECKOUT_OFFER,
            'link' => $GLOBALS['Admin']->link('checkout_offer.php'),
        ];
        break;
    }
}
unset($group);
