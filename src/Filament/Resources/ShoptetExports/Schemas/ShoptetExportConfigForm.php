<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\ShoptetExports\Schemas;

use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ShoptetExportConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('feedmanager-pro::feedmanager-pro.shoptet_exports.sections.identity'))
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.shoptet_export_name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                            if (! filled($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label(__('feedmanager::feedmanager.fields.slug'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->helperText(__('feedmanager-pro::feedmanager-pro.helpers.shoptet_export_slug')),
                    Select::make('feed_type')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.shoptet_feed_type'))
                        ->helperText(__('feedmanager-pro::feedmanager-pro.helpers.shoptet_feed_type'))
                        ->options([
                            ShoptetExportConfig::FEED_FULL => __('feedmanager-pro::feedmanager-pro.shoptet_feed_types.full'),
                            ShoptetExportConfig::FEED_STOCK => __('feedmanager-pro::feedmanager-pro.shoptet_feed_types.stock'),
                        ])
                        ->default(ShoptetExportConfig::FEED_FULL)
                        ->native(false)
                        ->required(),
                    Toggle::make('is_active')
                        ->label(__('feedmanager::feedmanager.fields.is_active'))
                        ->default(true),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.shoptet_exports.sections.access'))
                ->components([
                    TextInput::make('access_token')
                        ->label(__('feedmanager-pro::feedmanager-pro.fields.access_token'))
                        ->helperText(__('feedmanager-pro::feedmanager-pro.helpers.shoptet_access_token'))
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn (): string => ShoptetExportConfig::generateToken())
                        ->columnSpanFull(),
                ]),

            Section::make(__('feedmanager-pro::feedmanager-pro.shoptet_exports.sections.notes'))
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
