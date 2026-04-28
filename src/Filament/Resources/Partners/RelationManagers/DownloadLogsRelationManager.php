<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DownloadLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'downloadLogs';

    public function form(Schema $schema): Schema
    {
        // Read-only relation; no form fields needed.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('feedmanager::feedmanager.partners.recent_downloads'))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('feedmanager::feedmanager.fields.downloaded_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('feed_type')
                    ->label(__('feedmanager::feedmanager.fields.feed_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full' => 'primary',
                        'stock' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status_code')
                    ->label(__('feedmanager::feedmanager.fields.status_code'))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 500 => 'danger',
                        $state >= 400 => 'warning',
                        $state >= 200 => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('product_count')
                    ->label(__('feedmanager::feedmanager.fields.product_count'))
                    ->alignEnd(),
                TextColumn::make('ip')
                    ->label(__('feedmanager::feedmanager.fields.ip'))
                    ->fontFamily('mono')
                    ->toggleable(),
                TextColumn::make('user_agent')
                    ->label(__('feedmanager::feedmanager.fields.user_agent'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
