<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Pages;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExportConfigResource;
use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditShoptetExportConfig extends EditRecord
{
    protected static string $resource = ShoptetExportConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_token')
                ->label(__('feedmanager::feedmanager.actions.regenerate_token'))
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription(__('feedmanager::feedmanager.shoptet_exports.regenerate_warning'))
                ->action(function (ShoptetExportConfig $record): void {
                    $record->regenerateToken();
                    Notification::make()
                        ->title(__('feedmanager::feedmanager.notifications.token_regenerated'))
                        ->success()
                        ->send();
                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
