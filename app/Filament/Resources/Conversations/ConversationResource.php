<?php

namespace App\Filament\Resources\Conversations;

use App\Filament\Resources\Conversations\Pages\ChatConversation;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Conversations\Tables\ConversationsTable;
use App\Models\Conversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon =
    Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Support';

    protected static ?string $modelLabel = 'Conversation';

    protected static ?string $pluralModelLabel = 'Conversations support';

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return ConversationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'support')
            ->with([
                'userOne',
                'userTwo',
                'lastMessage',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'chat' => ChatConversation::route('/{record}/chat'),
        ];
    }
}
