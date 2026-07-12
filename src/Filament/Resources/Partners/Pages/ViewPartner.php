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
                    ? __('feedmanager-pro::feedmanager-pro.actions.disable_feeds')
                    : __('feedmanager-pro::feedmanager-pro.actions.enable_feeds'))
                ->icon(fn (Partner $record): string => $record->feeds_active
                    ? 'heroicon-o-pause-circle'
                    : 'heroicon-o-play-circle')
                ->color(fn (Partner $record): string => $record->feeds_active ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(function (Partner $record): void {
                    $record->update(['feeds_active' => ! $record->feeds_active]);
                    Notification::make()
                        ->title($record->feeds_active
                            ? __('feedmanager-pro::feedmanager-pro.notifications.feeds_enabled')
                            : __('feedmanager-pro::feedmanager-pro.notifications.feeds_disabled'))
                        ->success()
                        ->send();
                }),
            Action::make('regenerate_credentials')
                ->label(__('feedmanager-pro::feedmanager-pro.actions.regenerate_credentials'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('feedmanager-pro::feedmanager-pro.actions.regenerate_credentials_confirm_heading'))
                ->modalDescription(__('feedmanager-pro::feedmanager-pro.actions.regenerate_credentials_confirm'))
                ->action(function (Partner $record): void {
                    $record->regenerateCredentials();
                    Notification::make()
                        ->title(__('feedmanager-pro::feedmanager-pro.notifications.credentials_regenerated'))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
