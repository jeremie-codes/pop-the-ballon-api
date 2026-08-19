<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Médias';

    protected static ?string $recordTitleAttribute = 'path';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Type de média')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Vidéo',
                    ])
                    ->required()
                    ->native(false),

                FileUpload::make('path')
                    ->label('Fichier')
                    ->disk('public')
                    ->directory('campaigns/media')
                    ->visibility('public')
                    ->imagePreviewHeight('200')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('order')
                    ->label('Ordre d’affichage')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Plus le nombre est petit, plus le média apparaît tôt.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                ImageColumn::make('path')
                    ->label('Aperçu')
                    ->disk('public')
                    ->square()
                    ->size(70),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn(?string $state) => match ($state) {
                            'image' => 'Image',
                            'video' => 'Vidéo',
                            default => $state,
                        }
                    ),

                TextColumn::make('path')
                    ->label('Fichier')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->path),

                TextColumn::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('order')
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un média')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
