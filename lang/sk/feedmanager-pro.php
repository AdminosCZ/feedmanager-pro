<?php

return [
    'navigation' => [
        'b2b' => 'B2B',
    ],

    'partners' => [
        'label' => 'B2B partner',
        'plural_label' => 'B2B partneri',
        'navigation_label' => 'B2B partneri',
        'sections' => [
            'identity' => 'Identifikácia firmy',
            'contact' => 'Kontakt a adresa',
            'feed_access' => 'Prístup k feedu',
            'thresholds' => 'Nastavenie dostupnosti',
            'notes' => 'Poznámky',
        ],
        'tier' => [
            'standard' => 'Štandardný',
            'vip' => 'VIP',
        ],
        'thresholds_help' => 'Keď má produkt menej kusov ako threshold, namiesto skutočného počtu sa partnerovi zobrazí náhradný text (napr. „Na dopyt"). Per-product threshold na detaile produktu môže tento default zvýšiť, nie znížiť.',
        'feed_access_help' => 'URL je absolútna a v produkcii vždy cez HTTPS. Partner sa k feedu autentizuje HTTP Basic Auth pomocou používateľa a hesla nižšie — bez nich vráti endpoint 401. Heslo je v DB šifrované cez APP_KEY.',
        'status' => [
            'feeds_on' => 'Zapnuté',
            'feeds_off' => 'Vypnuté',
        ],
        'full_feed_url' => 'Full feed URL',
        'stock_feed_url' => 'Stock feed URL',
        'no_notes' => '— bez poznámky —',
        'no_credentials' => '— nenastavené —',
        'recent_downloads' => 'Posledné stiahnutia',
    ],

    'shoptet_exports' => [
        'label' => 'Shoptet auto-import',
        'plural_label' => 'Shoptet auto-importy',
        'navigation_label' => 'Shoptet auto-importy',
        'sections' => [
            'identity' => 'Identifikácia exportu',
            'access' => 'URL pre Shoptet auto-import',
            'notes' => 'Poznámky',
        ],
        'regenerate_warning' => 'Aktuálna URL prestane fungovať. V Shoptet admine bude treba zadať nový odkaz. Akciu nemožno vrátiť späť.',
    ],

    'shoptet_feed_types' => [
        'full' => 'Úplný (1×/deň)',
        'stock' => 'Aktualizačný — ceny + sklad (1–16×/deň)',
    ],

    'fields' => [
        'tier' => 'Úroveň partnera',
        'access_token' => 'Prístupový token (UUID)',
        'feed_username' => 'Používateľ (Basic Auth)',
        'feed_password' => 'Heslo (Basic Auth)',
        'feeds_active' => 'Feedy aktívne',
        'feed_full_limit' => 'Limit full feedov (24h)',
        'feed_stock_limit' => 'Limit stock feedov (24h)',
        'default_low_stock_threshold' => 'Threshold zásoby (ks)',
        'default_low_stock_availability' => 'Dostupnosť pri nízkej zásobe',
        'default_out_of_stock_availability' => 'Dostupnosť pri vypredaní',
        'shoptet_export_name' => 'Názov exportu',
        'shoptet_feed_type' => 'Typ feedu',
    ],

    'helpers' => [
        'tier' => 'Štandardný partner = vyšší threshold (default 5 ks). VIP = nižší threshold (typicky 2 ks) a môže vidieť menšie zásoby. V budúcnosti sa bude prideľovať automaticky podľa obratu.',
        'access_token' => 'UUID, ktoré je súčasťou URL feedu. Generuje sa pri uložení; cez „Regenerovať prístup" ho možno obmeniť (rozbije aktuálne napojenie).',
        'feed_username' => 'HTTP Basic Auth používateľ, ktorého partner použije na stiahnutie feedu. Generuje sa automaticky, dá sa regenerovať spolu s heslom.',
        'feed_password' => 'HTTP Basic Auth heslo. Spolu s používateľom chráni feed proti svojvoľnému šíreniu URL — leak URL bez hesla neznamená prístup k dátam. V DB šifrované cez APP_KEY.',
        'feeds_active' => 'Vypnutím okamžite zablokujete partnerovi prístup k feedu (HTTP 403).',
        'feed_full_limit' => 'Maximálny počet úspešných stiahnutí full feedu za posledných 24 hodín. 0 = zakázané.',
        'feed_stock_limit' => 'Maximálny počet úspešných stiahnutí stock feedu za posledných 24 hodín. 0 = zakázané.',
        'shoptet_access_token' => 'Token v URL `/feeds/shoptet/{token}.xml`. Generuje sa pri uložení; cez akciu „Regenerovať" ho možno obmeniť (rozbije aktuálne Shoptet napojenie).',
        'shoptet_export_slug' => 'Krátky technický identifikátor (kebab-case). V URL sa nepoužíva — URL používa access_token. Slúži len na internú referenciu.',
        'shoptet_feed_type' => 'Úplný = celý katalóg 1×/deň. Aktualizačný = iba ceny + sklad + dostupnosť, možno poslať 1–16× denne podľa Shoptet tarifu klienta.',
        'default_low_stock_threshold' => 'Keď má produkt tento počet kusov (alebo menej), partnerovi sa namiesto počtu zobrazí náhradný text. 0 = vypnuté.',
        'default_low_stock_availability' => 'Čo sa zobrazí v elemente <availability>, keď je zásoba pod threshold. Napr. „Na dopyt".',
        'default_out_of_stock_availability' => 'Čo sa zobrazí, keď je zásoba 0. Napr. „Vypredané".',
    ],

    'actions' => [
        'regenerate_token' => 'Regenerovať token',
        'regenerate_token_confirm_heading' => 'Regenerovať prístupový token?',
        'regenerate_token_confirm' => 'Aktuálne URL prestane partnerovi fungovať. Bude potrebné mu poslať nový token. Akciu nemožno vrátiť späť.',

        'regenerate_credentials' => 'Regenerovať prístup',
        'regenerate_credentials_confirm_heading' => 'Regenerovať prístupové údaje partnera?',
        'regenerate_credentials_confirm' => 'Vygeneruje sa nový token v URL aj nové Basic Auth heslo. Aktuálne URL ani heslo už partnerovi fungovať nebudú — bude potrebné mu poslať všetky tri hodnoty znova. Akciu nemožno vrátiť späť.',

        'enable_feeds' => 'Zapnúť feedy',
        'disable_feeds' => 'Vypnúť feedy',
    ],

    'notifications' => [
        'token_regenerated' => 'Nový token bol vygenerovaný.',
        'credentials_regenerated' => 'Nový token aj Basic Auth heslo boli vygenerované.',
        'feeds_enabled' => 'Feedy partnera zapnuté.',
        'feeds_disabled' => 'Feedy partnera vypnuté.',
    ],
];
