<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('owner_type')
                    ->options(['admin' => 'Admin', 'partner' => 'Partner', 'user' => 'User'])
                    ->required(),
                TextInput::make('owner_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('campaign_type')
                    ->options(['feature' => 'Feature', 'commercial' => 'Commercial', 'sponsored' => 'Sponsored'])
                    ->required(),
                Select::make('media_type')
                    ->options(['image' => 'Image', 'carousel' => 'Carousel', 'video' => 'Video'])
                    ->required(),
                Select::make('status')
                    ->options([
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'active' => 'Active',
            'paused' => 'Paused',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ])
                    ->default('draft')
                    ->required(),
                TextInput::make('button_text')
                    ->default(null),
                Select::make('target_type')
                    ->options([
            'url' => 'Url',
            'feature' => 'Feature',
            'premium' => 'Premium',
            'marketplace' => 'Marketplace',
            'profile' => 'Profile',
            'external' => 'External',
        ])
                    ->default(null),
                TextInput::make('target_value')
                    ->default(null),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('budget')
                    ->numeric()
                    ->default(null),
                TextInput::make('price')
                    ->numeric()
                    ->default(null)
                    ->prefix('$'),
                TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('clicks_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('start_at'),
                DateTimePicker::make('end_at'),
            ]);
    }
}
