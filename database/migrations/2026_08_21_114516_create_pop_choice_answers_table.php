<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pop_choice_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pop_choice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('answer', ['a', 'b']);

            $table->timestamp('answered_at')->useCurrent();

            $table->timestamps();

            $table->unique([
                'user_id',
                'pop_choice_id',
            ]);

            $table->index([
                'user_id',
                'answered_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pop_choice_answers');
    }
};
