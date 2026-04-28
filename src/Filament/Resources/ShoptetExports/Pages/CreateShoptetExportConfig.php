<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Pages;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExportConfigResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateShoptetExportConfig extends CreateRecord
{
    protected static string $resource = ShoptetExportConfigResource::class;
}
