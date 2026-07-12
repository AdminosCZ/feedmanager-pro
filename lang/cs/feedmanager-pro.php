<?php

return [
    'navigation' => [
        'b2b' => 'B2B',
    ],

    'partners' => [
        'label' => 'B2B partner',
        'plural_label' => 'B2B partneři',
        'navigation_label' => 'B2B partneři',
        'sections' => [
            'identity' => 'Identifikace firmy',
            'contact' => 'Kontakt a adresa',
            'feed_access' => 'Přístup k feedu',
            'thresholds' => 'Nastavení dostupnosti',
            'notes' => 'Poznámky',
        ],
        'tier' => [
            'standard' => 'Standardní',
            'vip' => 'VIP',
        ],
        'thresholds_help' => 'Když má produkt méně kusů než threshold, místo skutečného počtu se partnerovi zobrazí náhradní text (např. „Na dotaz"). Per-product threshold na detailu produktu může tento default zvýšit, ne snížit.',
        'feed_access_help' => 'URL je absolutní a v produkci vždy přes HTTPS. Partner se k feedu autentizuje HTTP Basic Auth pomocí uživatele a hesla níže — bez nich vrátí endpoint 401. Heslo je v DB šifrované přes APP_KEY.',
        'status' => [
            'feeds_on' => 'Zapnuto',
            'feeds_off' => 'Vypnuto',
        ],
        'full_feed_url' => 'Full feed URL',
        'stock_feed_url' => 'Stock feed URL',
        'no_notes' => '— bez poznámky —',
        'no_credentials' => '— nenastaveno —',
        'recent_downloads' => 'Poslední stažení',
    ],

    'shoptet_exports' => [
        'label' => 'Shoptet auto-import',
        'plural_label' => 'Shoptet auto-importy',
        'navigation_label' => 'Shoptet auto-importy',
        'sections' => [
            'identity' => 'Identifikace exportu',
            'access' => 'URL pro Shoptet auto-import',
            'notes' => 'Poznámky',
        ],
        'regenerate_warning' => 'Aktuální URL přestane fungovat. V Shoptet adminu bude potřeba zadat nový odkaz. Akci nelze vrátit zpět.',
    ],

    'shoptet_feed_types' => [
        'full' => 'Úplný (1×/den)',
        'stock' => 'Aktualizační — ceny + sklad (1–16×/den)',
    ],

    'fields' => [
        'tier' => 'Úroveň partnera',
        'access_token' => 'Přístupový token (UUID)',
        'feed_username' => 'Uživatel (Basic Auth)',
        'feed_password' => 'Heslo (Basic Auth)',
        'feeds_active' => 'Feedy aktivní',
        'feed_full_limit' => 'Limit full feedů (24h)',
        'feed_stock_limit' => 'Limit stock feedů (24h)',
        'default_low_stock_threshold' => 'Threshold zásoby (ks)',
        'default_low_stock_availability' => 'Dostupnost při nízké zásobě',
        'default_out_of_stock_availability' => 'Dostupnost při vyprodání',
        'shoptet_export_name' => 'Název exportu',
        'shoptet_feed_type' => 'Typ feedu',
    ],

    'helpers' => [
        'tier' => 'Standardní partner = vyšší threshold (default 5 ks). VIP = nižší threshold (typicky 2 ks) a může vidět menší zásoby. V budoucnu se bude přiřazovat automaticky podle obratu.',
        'access_token' => 'UUID, které je součástí URL feedu. Generuje se při uložení; přes „Regenerovat přístup" ho lze obměnit (rozbije současné napojení).',
        'feed_username' => 'HTTP Basic Auth uživatel, kterého partner použije pro stažení feedu. Generuje se automaticky, lze regenerovat společně s heslem.',
        'feed_password' => 'HTTP Basic Auth heslo. Spolu s uživatelem chrání feed proti svévolnému šíření URL — leak URL bez hesla neznamená přístup k datům. V DB šifrované přes APP_KEY.',
        'feeds_active' => 'Vypnutím okamžitě zablokujete partnerovi přístup k feedu (HTTP 403).',
        'feed_full_limit' => 'Maximální počet úspěšných stažení full feedu za posledních 24 hodin. 0 = zakázáno.',
        'feed_stock_limit' => 'Maximální počet úspěšných stažení stock feedu za posledních 24 hodin. 0 = zakázáno.',
        'shoptet_access_token' => 'Token v URL `/feeds/shoptet/{token}.xml`. Generuje se při uložení; přes akci „Regenerovat" ho můžeš obměnit (rozbije současné Shoptet napojení).',
        'shoptet_export_slug' => 'Krátký technický identifikátor (kebab-case). Nepoužívá se v URL — URL používá access_token. Slouží jen jako interní reference.',
        'shoptet_feed_type' => 'Úplný = celý katalog 1×/den. Aktualizační = pouze ceny + sklad + dostupnost, lze poslat 1–16× denně dle Shoptet tarifu klienta.',
        'default_low_stock_threshold' => 'Když má produkt tento počet kusů (nebo méně), partnerovi se místo počtu zobrazí náhradní text. 0 = vypnuto (vždy posílat skutečný počet).',
        'default_low_stock_availability' => 'Text, který se zobrazí v elementu <availability>, když je zásoba pod threshold. Např. „Na dotaz", „Skladem v omezeném množství".',
        'default_out_of_stock_availability' => 'Text, který se zobrazí, když je zásoba 0. Např. „Vyprodáno", „Není skladem".',
    ],

    'actions' => [
        'regenerate_token' => 'Regenerovat token',
        'regenerate_token_confirm_heading' => 'Regenerovat přístupový token?',
        'regenerate_token_confirm' => 'Stávající URL přestane partnerovi fungovat. Bude potřeba mu poslat nový token. Akci nelze vrátit zpět.',

        'regenerate_credentials' => 'Regenerovat přístup',
        'regenerate_credentials_confirm_heading' => 'Regenerovat přístupové údaje partnera?',
        'regenerate_credentials_confirm' => 'Vygeneruje se nový token v URL i nové Basic Auth heslo. Stávající URL ani heslo už partnerovi fungovat nebudou — bude potřeba mu poslat všechny tři hodnoty znovu. Akci nelze vrátit zpět.',

        'enable_feeds' => 'Zapnout feedy',
        'disable_feeds' => 'Vypnout feedy',
    ],

    'notifications' => [
        'token_regenerated' => 'Nový token byl vygenerován.',
        'credentials_regenerated' => 'Nový token i Basic Auth heslo byly vygenerovány.',
        'feeds_enabled' => 'Feedy partnera zapnuty.',
        'feeds_disabled' => 'Feedy partnera vypnuty.',
    ],
];
