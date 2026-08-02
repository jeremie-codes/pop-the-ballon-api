<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            // Type du message
            $table->string('type')
                ->default('text')
                ->after('sender_id');

            // Texte du message
            $table->text('body')
                ->nullable()
                ->change();

            // Fichier (audio, image...)
            $table->string('attachment')
                ->nullable()
                ->after('body');

            // Durée en secondes
            $table->integer('attachment_duration')
                ->nullable()
                ->after('attachment');

            // Taille en octets
            $table->unsignedBigInteger('attachment_size')
                ->nullable()
                ->after('attachment_duration');

            // Mime type
            $table->string('attachment_mime')
                ->nullable()
                ->after('attachment_size');

        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            $table->dropColumn([
                'type',
                'attachment',
                'attachment_duration',
                'attachment_size',
                'attachment_mime',
            ]);

            $table->text('body')->nullable(false)->change();
        });
    }
};
