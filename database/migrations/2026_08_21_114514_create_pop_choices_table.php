<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pop_choices', function (Blueprint $table) {
            $table->id();

            $table->text('question');

            $table->string('option_a');
            $table->string('option_b');

            $table->string('type')->default('discovery');
            $table->string('category');

            $table->unsignedInteger('weight')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'is_active',
                'type',
                'category',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pop_choices');
    }
};
