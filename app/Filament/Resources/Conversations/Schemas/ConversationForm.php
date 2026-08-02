<?php

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('match_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('user_one_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_two_id')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('last_message_at'),
                Select::make('type')
                    ->options(['match' => 'Match', 'support' => 'Support'])
                    ->default('match')
                    ->required(),
            ]);
    }
}
