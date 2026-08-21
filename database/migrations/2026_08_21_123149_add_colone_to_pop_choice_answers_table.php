<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pop_choice_answers', function (Blueprint $table) {
            $table->foreignId('pop_choice_session_id')
                ->nullable()
                ->after('pop_choice_id')
                ->constrained('pop_choice_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pop_choice_answers', function (Blueprint $table) {
            $table->dropForeign([
                'pop_choice_session_id',
            ]);

            $table->dropColumn('pop_choice_session_id');
        });
    }
};