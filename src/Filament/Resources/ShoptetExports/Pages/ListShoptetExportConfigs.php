<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Pages;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExportConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListShoptetExportConfigs extends ListRecords
{
    protected static string $resource = ShoptetExportConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
