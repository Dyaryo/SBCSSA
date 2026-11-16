<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS / betting rules
    |--------------------------------------------------------------------------
    | All amounts are virtual credits. There is deliberately no cash-in,
    | cash-out or payment gateway configuration in this file.
    */
    'min_bet' => (int) env('POS_MIN_BET', 1),
    'max_bet' => (int) env('POS_MAX_BET', 100000),
    'max_bets_per_fight' => (int) env('POS_MAX_BETS_PER_FIGHT', 20),

    // A slip at or above this stake triggers the arena board's BIG BET flash.
    // The board receives the side only — never the amount, ticket or teller.
    'big_bet_threshold' => (int) env('POS_BIG_BET', 10000),

    // An active POS till below this balance is flagged for a top-up on the
    // supervisor dashboard, so tills are funded before the next event.
    'low_till_threshold' => (int) env('POS_LOW_TILL', 1000),

    // Largest single credit allocation a supervisor can grant to a teller.
    'max_top_up' => (int) env('POS_MAX_TOP_UP', 100000),

    // When true, a teller can only sign in from an approved workstation whose
    // device fingerprint matches. Turn this on for real LAN deployments.
    'strict_device_binding' => filter_var(env('POS_STRICT_DEVICE_BINDING', false), FILTER_VALIDATE_BOOL),

    'session_timeout_minutes' => (int) env('POS_SESSION_TIMEOUT', 480),
];
