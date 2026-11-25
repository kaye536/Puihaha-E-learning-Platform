<?php

return [
    // Currency symbol shown on every amount in the system.
    'currency' => env('MEMBERSHIP_CURRENCY', '₱'),

    // Organization name printed on receipts and membership records.
    'organization' => env('MEMBERSHIP_ORGANIZATION', 'Membership Management Office'),

    // Members may request a renewal from the portal this many days before expiry (or any time after it lapses).
    'renewal_window_days' => (int) env('MEMBERSHIP_RENEWAL_WINDOW_DAYS', 90),
];
