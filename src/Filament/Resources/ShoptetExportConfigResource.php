<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Pages\CreateShoptetExportConfig;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Pages\EditShoptetExportConfig;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Pages\ListShoptetExportConfigs;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Schemas\ShoptetExportConfigForm;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Tables\ShoptetExportConfigsTable;
use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * @api
 */
final class ShoptetExportConfigResource extends Resource
{
    protected static ?string $model = ShoptetExportConfig::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return __('feedmanager::feedmanager.shoptet_exports.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('feedmanager::feedmanager.shoptet_exports.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('feedmanager::feedmanager.shoptet_exports.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('feedmanager::feedmanager.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return ShoptetExportConfigForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShoptetExportConfigsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShoptetExportConfigs::route('/'),
            'create' => CreateShoptetExportConfig::route('/create'),
            'edit' => EditShoptetExportConfig::route('/{record}/edit'),
        ];
    }
}
