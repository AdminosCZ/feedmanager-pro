<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Pages;

use Adminos\Modules\FeedmanagerPro\Filament\Resources\PartnerResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_token')
                ->label(__('feedmanager::feedmanager.actions.regenerate_token'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('feedmanager::feedmanager.actions.regenerate_token_confirm_heading'))
                ->modalDescription(__('feedmanager::feedmanager.actions.regenerate_token_confirm'))
                ->action(function (): void {
                    $this->record->regenerateToken();
                    $this->refreshFormData(['access_token']);
                }),
            DeleteAction::make(),
        ];
    }
}
