<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Pages\CreatePartner;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Pages\EditPartner;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Pages\ListPartners;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Pages\ViewPartner;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\RelationManagers\DownloadLogsRelationManager;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Schemas\PartnerForm;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Tables\PartnersTable;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * @api
 */
final class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('feedmanager-pro::feedmanager-pro.partners.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('feedmanager-pro::feedmanager-pro.partners.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('feedmanager-pro::feedmanager-pro.partners.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('feedmanager-pro::feedmanager-pro.navigation.b2b');
    }

    public static function form(Schema $schema): Schema
    {
        return PartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DownloadLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'view' => ViewPartner::route('/{record}'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}
