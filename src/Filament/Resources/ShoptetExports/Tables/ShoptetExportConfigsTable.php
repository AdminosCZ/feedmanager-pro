<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Tables;

use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ShoptetExportConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.shoptet_export_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('feed_type')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.shoptet_feed_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ShoptetExportConfig::FEED_FULL => __('feedmanager-pro::feedmanager-pro.shoptet_feed_types.full'),
                        ShoptetExportConfig::FEED_STOCK => __('feedmanager-pro::feedmanager-pro.shoptet_feed_types.stock'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        ShoptetExportConfig::FEED_FULL => 'primary',
                        ShoptetExportConfig::FEED_STOCK => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label(__('feedmanager::feedmanager.fields.is_active'))
                    ->boolean(),
                TextColumn::make('last_run_at')
                    ->label(__('feedmanager::feedmanager.fields.last_run_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('last_count')
                    ->label(__('feedmanager::feedmanager.fields.last_count'))
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('last_status')
                    ->label(__('feedmanager::feedmanager.fields.last_status'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        ShoptetExportConfig::STATUS_SUCCESS => 'success',
                        ShoptetExportConfig::STATUS_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('feedmanager::feedmanager.fields.is_active')),
                SelectFilter::make('feed_type')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.shoptet_feed_type'))
                    ->options([
                        ShoptetExportConfig::FEED_FULL => __('feedmanager-pro::feedmanager-pro.shoptet_feed_types.full'),
                        ShoptetExportConfig::FEED_STOCK => __('feedmanager-pro::feedmanager-pro.shoptet_feed_types.stock'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
