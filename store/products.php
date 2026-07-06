<?php
// Product catalog for the Equity Paradox Conference store.
// stock: null means unlimited. Sold counts are tracked in data/orders.json.

return [
    'early-bird' => [
        'name'  => "Equity Paradox Conference — Early Bird Registration",
        'price' => 'price_1TpFL7JTlzYMrjQeS9K0VJa3',
        'amount' => 60000,
        'stock' => 59,
    ],
    'general' => [
        'name'  => "Equity Paradox Conference — General Registration",
        'price' => 'price_1TpHTqJTlzYMrjQextO9Tkiv',
        'amount' => 75000,
        'stock' => 66,
    ],
    'tour' => [
        'name'  => "Greensboro's Black Wall Street Tour (Add-On)",
        'price' => 'price_1TpFNhJTlzYMrjQeMiGGFRBf',
        'amount' => 2500,
        'stock' => null,
    ],
    'sponsor-platinum' => [
        'name'  => 'Platinum Sponsor',
        'price' => 'price_1TpFOiJTlzYMrjQe4JBNb8kM',
        'amount' => 2500000,
        'group' => 'sponsor',
    ],
    'sponsor-gold' => [
        'name'  => 'Gold Sponsor',
        'price' => 'price_1TpFPHJTlzYMrjQeOHDyaRZ2',
        'amount' => 1500000,
        'group' => 'sponsor',
    ],
    'sponsor-silver' => [
        'name'  => 'Silver Sponsor',
        'price' => 'price_1TpFPoJTlzYMrjQee45oEF0H',
        'amount' => 1000000,
        'group' => 'sponsor',
    ],
    'sponsor-community' => [
        'name'  => 'Community Sponsor',
        'price' => 'price_1TpFQLJTlzYMrjQeJGh61v5Q',
        'amount' => 500000,
        'group' => 'sponsor',
    ],
    'sponsor-supporting' => [
        'name'  => 'Supporting Sponsor',
        'price' => 'price_1TpFQrJTlzYMrjQeW1xhDM2V',
        'amount' => 250000,
        'group' => 'sponsor',
    ],
];

// Shared stock pools for grouped products (e.g. all sponsor tiers draw from
// one combined cap, matching Squarespace's single "Sponsor Registration"
// product with 25 total slots across all tiers).
$GLOBALS['STORE_GROUP_CAPS'] = [
    'sponsor' => 25,
];
