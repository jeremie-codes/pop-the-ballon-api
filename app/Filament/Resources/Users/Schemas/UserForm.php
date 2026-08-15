<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->default(null),
                TextInput::make('last_name')
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('google_id')
                    ->default(null),
                TextInput::make('username')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->default(null),
                TextInput::make('password_reset_otp')
                    ->password()
                    ->default(null),
                TextInput::make('password_reset_attempts')
                    ->password()
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('password_reset_requests')
                    ->password()
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('password_reset_last_sent_at'),
                DateTimePicker::make('password_reset_blocked_until'),
                DateTimePicker::make('password_reset_otp_expires_at'),
                DatePicker::make('birth_date'),
                TextInput::make('gender')
                    ->default(null),
                TextInput::make('city')
                    ->default(null),
                TextInput::make('country')
                    ->default(null),
                TextInput::make('intention')
                    ->default(null),
                Textarea::make('bio')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('verified')
                    ->required(),
                DateTimePicker::make('last_seen_at'),
                TextInput::make('delete_reason')
                    ->default(null),
                Select::make('role')
                    ->options(['user' => 'User', 'admin' => 'Admin', 'support' => 'Support', 'moderator' => 'Moderator'])
                    ->default('user')
                    ->required(),
                Toggle::make('is_visible')
                    ->required(),
                Toggle::make('is_staff')
                    ->required(),
            ]);
    }
}
