<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Pages;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\PartnerResource;
use Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Schemas\PartnerInfolist;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

final class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    public function infolist(Schema $schema): Schema
    {
        return PartnerInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle_feeds')
                ->label(fn (Partner $record): string => $record->feeds_active
                    ? __('feedmanager::feedmanager.actions.disable_feeds')
                    : __('feedmanager::feedmanager.actions.enable_feeds'))
                ->icon(fn (Partner $record): string => $record->feeds_active
                    ? 'heroicon-o-pause-circle'
                    : 'heroicon-o-play-circle')
                ->color(fn (Partner $record): string => $record->feeds_active ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(function (Partner $record): void {
                    $record->update(['feeds_active' => ! $record->feeds_active]);
                    Notification::make()
                        ->title($record->feeds_active
                            ? __('feedmanager::feedmanager.notifications.feeds_enabled')
                            : __('feedmanager::feedmanager.notifications.feeds_disabled'))
                        ->success()
                        ->send();
                }),
            Action::make('regenerate_token')
                ->label(__('feedmanager::feedmanager.actions.regenerate_token'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('feedmanager::feedmanager.actions.regenerate_token_confirm_heading'))
                ->modalDescription(__('feedmanager::feedmanager.actions.regenerate_token_confirm'))
                ->action(function (Partner $record): void {
                    $record->regenerateToken();
                    Notification::make()
                        ->title(__('feedmanager::feedmanager.notifications.token_regenerated'))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
