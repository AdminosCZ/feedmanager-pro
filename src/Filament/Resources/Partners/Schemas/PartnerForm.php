<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Schemas;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PartnerForm
{
    private const HINT_ICON = 'heroicon-m-question-mark-circle';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.identity'))
                ->columns(2)
                ->components([
                    TextInput::make('company_name')
                        ->label(__('feedmanager::feedmanager.fields.company_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('tier')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.tier'))
                        ->options([
                            Partner::TIER_STANDARD => __('feedmanager-pro::feedmanager-pro.partners.tier.standard'),
                            Partner::TIER_VIP => __('feedmanager-pro::feedmanager-pro.partners.tier.vip'),
                        ])
                        ->default(Partner::TIER_STANDARD)
                        ->required()
                        ->native(false)
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.tier'))
                        ->columnSpanFull(),
                    TextInput::make('ico')
                        ->label(__('feedmanager::feedmanager.fields.ico'))
                        ->maxLength(16),
                    TextInput::make('dic')
                        ->label(__('feedmanager::feedmanager.fields.dic'))
                        ->maxLength(16),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.contact'))
                ->columns(2)
                ->components([
                    TextInput::make('contact_name')
                        ->label(__('feedmanager::feedmanager.fields.contact_name'))
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(__('feedmanager::feedmanager.fields.email'))
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(__('feedmanager::feedmanager.fields.phone'))
                        ->tel()
                        ->maxLength(64),
                    TextInput::make('street')
                        ->label(__('feedmanager::feedmanager.fields.street'))
                        ->maxLength(255),
                    TextInput::make('city')
                        ->label(__('feedmanager::feedmanager.fields.city'))
                        ->maxLength(255),
                    TextInput::make('zip')
                        ->label(__('feedmanager::feedmanager.fields.zip'))
                        ->maxLength(16),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.feed_access'))
                ->description(__('feedmanager-pro::feedmanager-pro.partners.feed_access_help'))
                ->columns(3)
                ->components([
                    TextInput::make('access_token')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.access_token'))
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn (): string => Partner::generateToken())
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.access_token'))
                        ->columnSpanFull(),
                    TextInput::make('feed_username')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_username'))
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn (): string => Partner::generateUsername())
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.feed_username'))
                        ->columnSpan(1),
                    TextInput::make('feed_password')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_password'))
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn (): string => Partner::generatePassword())
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.feed_password'))
                        ->columnSpan(2),
                    Toggle::make('feeds_active')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.feeds_active'))
                        ->default(true)
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.feeds_active')),
                    TextInput::make('feed_full_limit')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_full_limit'))
                        ->numeric()
                        ->minValue(0)
                        ->default(10)
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.feed_full_limit')),
                    TextInput::make('feed_stock_limit')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.feed_stock_limit'))
                        ->numeric()
                        ->minValue(0)
                        ->default(50)
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.feed_stock_limit')),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.thresholds'))
                ->description(__('feedmanager-pro::feedmanager-pro.partners.thresholds_help'))
                ->columns(3)
                ->components([
                    TextInput::make('default_low_stock_threshold')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.default_low_stock_threshold'))
                        ->numeric()
                        ->minValue(0)
                        ->default(5)
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.default_low_stock_threshold')),
                    TextInput::make('default_low_stock_availability')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.default_low_stock_availability'))
                        ->maxLength(64)
                        ->default('Na dotaz')
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.default_low_stock_availability')),
                    TextInput::make('default_out_of_stock_availability')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.default_out_of_stock_availability'))
                        ->maxLength(64)
                        ->default('Vyprodáno')
                        ->hintIcon(self::HINT_ICON, tooltip: __('feedmanager-pro::feedmanager-pro.helpers.default_out_of_stock_availability')),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.partners.sections.notes'))
                ->collapsed()
                ->components([
                    Textarea::make('notes')
                        ->label(__('feedmanager::feedmanager.fields.notes'))
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
