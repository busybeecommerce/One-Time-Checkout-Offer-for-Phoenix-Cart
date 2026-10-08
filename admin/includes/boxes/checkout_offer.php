<?php
declare(strict_types=1);

$cl_box_groups[] = [
    'nav' => 'end',
    'sort' => 45,
    'heading' => 'Checkout offers',
    'apps' => [['code' => 'checkout_offer.php', 'title' => 'One-Time Checkout Offer',
        'link' => $GLOBALS['Admin']->link('checkout_offer.php')]],
];
