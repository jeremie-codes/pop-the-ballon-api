<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('identity_type');
            $table->string('document_path');
            $table->string('selfie_path');
            $table->enum('status', ['pending','approved','rejected', 'cancelled'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('reference')->unique();
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
