<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\PartnerResource;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExportConfigResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Filament plugin that contributes the B2B partner Resource on top of the
 * base feedmanager admin surface.
 *
 * @api
 */
final class FeedmanagerProPlugin implements Plugin
{
    public function getId(): string
    {
        return 'feedmanager-pro';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PartnerResource::class,
            ShoptetExportConfigResource::class,
        ]);

        // Group „B2B" sama o sobě registruje klient v AdminPanelProvider —
        // klient ví, které moduly má, a má v rukou pořadí groups. Plugin
        // jen poskytuje resources, které se do té groupy automaticky napárují
        // přes `getNavigationGroup()` na resourcech.
    }

    public function boot(Panel $panel): void
    {
    }

    public static function make(): self
    {
        return new self();
    }
}
