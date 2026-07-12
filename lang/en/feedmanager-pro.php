<?php

return [
    'navigation' => [
        'b2b' => 'B2B',
    ],

    'partners' => [
        'label' => 'B2B partner',
        'plural_label' => 'B2B partners',
        'navigation_label' => 'B2B partners',
        'sections' => [
            'identity' => 'Company identity',
            'contact' => 'Contact & address',
            'feed_access' => 'Feed access',
            'thresholds' => 'Stock visibility',
            'notes' => 'Notes',
        ],
        'tier' => [
            'standard' => 'Standard',
            'vip' => 'VIP',
        ],
        'thresholds_help' => 'When a product\'s stock is at or below the threshold, the partner sees a fallback label (e.g. "On request") instead of the exact remaining count. A per-product threshold (set on the product detail) can raise this default but never lower it.',
        'feed_access_help' => 'The URL is absolute and forced to HTTPS in production. The partner authenticates using HTTP Basic Auth with the username/password below — without them the endpoint returns 401. The password is encrypted in the database via APP_KEY.',
        'status' => [
            'feeds_on' => 'On',
            'feeds_off' => 'Off',
        ],
        'full_feed_url' => 'Full feed URL',
        'stock_feed_url' => 'Stock feed URL',
        'no_notes' => '— no note —',
        'no_credentials' => '— not set —',
        'recent_downloads' => 'Recent downloads',
    ],

    'shoptet_exports' => [
        'label' => 'Shoptet auto-import',
        'plural_label' => 'Shoptet auto-imports',
        'navigation_label' => 'Shoptet auto-imports',
        'sections' => [
            'identity' => 'Export identity',
            'access' => 'URL for Shoptet auto-import',
            'notes' => 'Notes',
        ],
        'regenerate_warning' => 'The current URL will stop working. The new link must be entered into the Shoptet admin. This action cannot be undone.',
    ],

    'shoptet_feed_types' => [
        'full' => 'Full (1×/day)',
        'stock' => 'Update — prices + stock (1–16×/day)',
    ],

    'fields' => [
        'tier' => 'Partner tier',
        'access_token' => 'Access token (UUID)',
        'feed_username' => 'Username (Basic Auth)',
        'feed_password' => 'Password (Basic Auth)',
        'feeds_active' => 'Feeds active',
        'feed_full_limit' => 'Full-feed limit (24h)',
        'feed_stock_limit' => 'Stock-feed limit (24h)',
        'default_low_stock_threshold' => 'Low-stock threshold (pcs)',
        'default_low_stock_availability' => 'Low-stock availability label',
        'default_out_of_stock_availability' => 'Out-of-stock availability label',
        'shoptet_export_name' => 'Export name',
        'shoptet_feed_type' => 'Feed type',
    ],

    'helpers' => [
        'tier' => 'Standard tier = higher threshold (default 5 pcs). VIP = lower threshold (typically 2 pcs) and gets to see smaller stocks. Will eventually be assigned automatically based on rolling revenue.',
        'access_token' => 'UUID embedded in the feed URL. Generated on save; the "Regenerate access" action rotates it (breaks the current connection).',
        'feed_username' => 'HTTP Basic Auth username the partner uses to download the feed. Auto-generated; can be rotated together with the password.',
        'feed_password' => 'HTTP Basic Auth password. Together with the username it protects the feed from being shared casually — a leaked URL alone does not grant access. Encrypted in the database via APP_KEY.',
        'feeds_active' => 'Disabling immediately blocks the partner from the feed endpoint (HTTP 403).',
        'feed_full_limit' => 'Maximum number of successful full-feed downloads per 24 hours. 0 = blocked.',
        'feed_stock_limit' => 'Maximum number of successful stock-feed downloads per 24 hours. 0 = blocked.',
        'shoptet_access_token' => 'Token in URL `/feeds/shoptet/{token}.xml`. Generated on save; the "Regenerate" action rotates it (breaks the current Shoptet connection).',
        'shoptet_export_slug' => 'Short technical identifier (kebab-case). Not used in the URL — URL uses access_token. Internal reference only.',
        'shoptet_feed_type' => 'Full = entire catalogue 1×/day. Update = prices + stock + availability only, can be polled 1–16×/day depending on the client\'s Shoptet tariff.',
        'default_low_stock_threshold' => 'When the product has this many units (or fewer), the partner sees a fallback label instead of the count. 0 = disabled (always send the real count).',
        'default_low_stock_availability' => 'What appears in the <availability> element when stock is at or below threshold. E.g. "On request", "Limited stock".',
        'default_out_of_stock_availability' => 'What appears when stock is 0. E.g. "Sold out", "Out of stock".',
    ],

    'actions' => [
        'regenerate_token' => 'Regenerate token',
        'regenerate_token_confirm_heading' => 'Regenerate access token?',
        'regenerate_token_confirm' => 'The current URL will stop working for the partner. You will need to send them the new token. This cannot be undone.',

        'regenerate_credentials' => 'Regenerate access',
        'regenerate_credentials_confirm_heading' => 'Regenerate partner access credentials?',
        'regenerate_credentials_confirm' => 'A new URL token and a new Basic Auth password will be generated. The current URL and password will stop working for the partner — you\'ll need to send them all three values again. This cannot be undone.',

        'enable_feeds' => 'Enable feeds',
        'disable_feeds' => 'Disable feeds',
    ],

    'notifications' => [
        'token_regenerated' => 'New token generated.',
        'credentials_regenerated' => 'New token and Basic Auth password generated.',
        'feeds_enabled' => 'Partner feeds enabled.',
        'feeds_disabled' => 'Partner feeds disabled.',
    ],
];
