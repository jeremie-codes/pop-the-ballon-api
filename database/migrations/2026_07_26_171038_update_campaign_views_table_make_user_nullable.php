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
        Schema::table('campaign_views', function (Blueprint $table) {

            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->change();
            $table->string('visitor_id')
                ->nullable()
                ->after('user_id');
            $table->string('ip_address')
                ->nullable();
            $table->text('user_agent')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
