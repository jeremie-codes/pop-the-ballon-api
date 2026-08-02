<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {

            $table->id();

            $table->enum('owner_type', [
                'admin',
                'partner',
                'user'
            ]);

            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('title');

            $table->text('description')->nullable();

            $table->enum('campaign_type', [
                'feature',
                'commercial',
                'sponsored'
            ]);

            $table->enum('media_type', [
                'image',
                'carousel',
                'video'
            ]);

            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'active',
                'paused',
                'rejected',
                'expired'
            ])->default('draft');

            $table->string('button_text')->nullable();

            $table->enum('target_type', [
                'url',
                'feature',
                'premium',
                'marketplace',
                'profile',
                'external'
            ])->nullable();

            $table->string('target_value')->nullable();

            $table->integer('priority')->default(0);

            $table->decimal('budget',12,2)->nullable();

            $table->decimal('price',12,2)->nullable();

            $table->unsignedBigInteger('views_count')->default(0);

            $table->unsignedBigInteger('clicks_count')->default(0);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('start_at')->nullable();

            $table->timestamp('end_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
