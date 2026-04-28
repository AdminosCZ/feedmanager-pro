<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Tables;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class PartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('feedmanager::feedmanager.fields.company_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ico')
                    ->label(__('feedmanager::feedmanager.fields.ico'))
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label(__('feedmanager::feedmanager.fields.contact_name'))
                    ->toggleable(),
                IconColumn::make('feeds_active')
                    ->label(__('feedmanager::feedmanager.fields.feeds_active'))
                    ->boolean(),
                TextColumn::make('feed_full_limit')
                    ->label(__('feedmanager::feedmanager.fields.feed_full_limit'))
                    ->alignEnd(),
                TextColumn::make('feed_stock_limit')
                    ->label(__('feedmanager::feedmanager.fields.feed_stock_limit'))
                    ->alignEnd(),
                TextColumn::make('access_token')
                    ->label(__('feedmanager::feedmanager.fields.access_token'))
                    ->copyable()
                    ->fontFamily('mono')
                    ->limit(12)
                    ->tooltip(fn (Partner $record): string => $record->access_token)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('feedmanager::feedmanager.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('feeds_active')
                    ->label(__('feedmanager::feedmanager.fields.feeds_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('regenerate_token')
                    ->label(__('feedmanager::feedmanager.actions.regenerate_token'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('feedmanager::feedmanager.actions.regenerate_token_confirm_heading'))
                    ->modalDescription(__('feedmanager::feedmanager.actions.regenerate_token_confirm'))
                    ->action(fn (Partner $record) => $record->regenerateToken()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
