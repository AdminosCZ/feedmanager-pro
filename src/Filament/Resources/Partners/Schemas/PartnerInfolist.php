<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Schemas;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PartnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(4)->components([
                TextEntry::make('tier')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.tier'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Partner::TIER_VIP => __('feedmanager-pro::feedmanager-pro.partners.tier.vip'),
                        default => __('feedmanager-pro::feedmanager-pro.partners.tier.standard'),
                    })
                    ->color(fn (?string $state): string => $state === Partner::TIER_VIP ? 'warning' : 'gray'),
                TextEntry::make('feeds_active')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.feeds_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('feedmanager-pro::feedmanager-pro.partners.status.feeds_on')
                        : __('feedmanager-pro::feedmanager-pro.partners.status.feeds_off'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextEntry::make('feed_full_limit')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_full_limit'))
                    ->state(fn (Partner $record): string => sprintf(
                        '%d / %d',
                        $record->recentSuccessfulDownloadCount(Partner::FEED_FULL),
                        $record->feed_full_limit,
                    )),
                TextEntry::make('feed_stock_limit')
                    ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_stock_limit'))
                    ->state(fn (Partner $record): string => sprintf(
                        '%d / %d',
                        $record->recentSuccessfulDownloadCount(Partner::FEED_STOCK),
                        $record->feed_stock_limit,
                    )),
            ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.feed_access'))
                ->description(__('feedmanager-pro::feedmanager-pro.partners.feed_access_help'))
                ->components([
                    TextEntry::make('full_feed_url')
                        ->label(__('feedmanager-pro::feedmanager-pro.partners.full_feed_url'))
                        ->state(fn (Partner $record): string => $record->fullFeedUrl())
                        ->copyable()
                        ->fontFamily('mono'),
                    TextEntry::make('stock_feed_url')
                        ->label(__('feedmanager-pro::feedmanager-pro.partners.stock_feed_url'))
                        ->state(fn (Partner $record): string => $record->stockFeedUrl())
                        ->copyable()
                        ->fontFamily('mono'),
                    Grid::make(2)->components([
                        TextEntry::make('feed_username')
                            ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_username'))
                            ->copyable()
                            ->fontFamily('mono')
                            ->placeholder(__('feedmanager-pro::feedmanager-pro.partners.no_credentials')),
                        TextEntry::make('feed_password')
                            ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_password'))
                            ->copyable()
                            ->fontFamily('mono')
                            ->placeholder(__('feedmanager-pro::feedmanager-pro.partners.no_credentials')),
                    ]),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.identity'))
                ->columns(2)
                ->components([
                    TextEntry::make('company_name')
                        ->label(__('feedmanager::feedmanager.fields.company_name')),
                    TextEntry::make('ico')
                        ->label(__('feedmanager::feedmanager.fields.ico')),
                    TextEntry::make('dic')
                        ->label(__('feedmanager::feedmanager.fields.dic')),
                    TextEntry::make('contact_name')
                        ->label(__('feedmanager::feedmanager.fields.contact_name')),
                    TextEntry::make('email')
                        ->label(__('feedmanager::feedmanager.fields.email')),
                    TextEntry::make('phone')
                        ->label(__('feedmanager::feedmanager.fields.phone')),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.notes'))
                ->collapsed(fn (Partner $record): bool => empty($record->notes))
                ->components([
                    TextEntry::make('notes')
                        ->label(__('feedmanager::feedmanager.fields.notes'))
                        ->columnSpanFull()
                        ->placeholder(__('feedmanager-pro::feedmanager-pro.partners.no_notes')),
                ]),
        ]);
    }
}
