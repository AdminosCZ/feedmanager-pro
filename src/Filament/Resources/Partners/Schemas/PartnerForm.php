<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Filament\Resources\Partners\Schemas;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('feedmanager::feedmanager.partners.sections.identity'))
                ->columns(2)
                ->components([
                    TextInput::make('company_name')
                        ->label(__('feedmanager::feedmanager.fields.company_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('ico')
                        ->label(__('feedmanager::feedmanager.fields.ico'))
                        ->maxLength(16),
                    TextInput::make('dic')
                        ->label(__('feedmanager::feedmanager.fields.dic'))
                        ->maxLength(16),
                ]),

            Section::make(__('feedmanager::feedmanager.partners.sections.contact'))
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

            Section::make(__('feedmanager::feedmanager.partners.sections.feed_access'))
                ->columns(3)
                ->components([
                    TextInput::make('access_token')
                        ->label(__('feedmanager::feedmanager.fields.access_token'))
                        ->helperText(__('feedmanager::feedmanager.helpers.access_token'))
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn (): string => Partner::generateToken())
                        ->columnSpanFull(),
                    Toggle::make('feeds_active')
                        ->label(__('feedmanager::feedmanager.fields.feeds_active'))
                        ->helperText(__('feedmanager::feedmanager.helpers.feeds_active'))
                        ->default(true),
                    TextInput::make('feed_full_limit')
                        ->label(__('feedmanager::feedmanager.fields.feed_full_limit'))
                        ->helperText(__('feedmanager::feedmanager.helpers.feed_full_limit'))
                        ->numeric()
                        ->minValue(0)
                        ->default(10),
                    TextInput::make('feed_stock_limit')
                        ->label(__('feedmanager::feedmanager.fields.feed_stock_limit'))
                        ->helperText(__('feedmanager::feedmanager.helpers.feed_stock_limit'))
                        ->numeric()
                        ->minValue(0)
                        ->default(50),
                ]),

            Section::make(__('feedmanager::feedmanager.partners.sections.notes'))
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
