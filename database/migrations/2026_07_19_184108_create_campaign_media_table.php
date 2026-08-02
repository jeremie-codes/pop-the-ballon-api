    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('campaign_media', function (Blueprint $table) {

                $table->id();

                $table->foreignId('campaign_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->enum('type',[
                    'image',
                    'video'
                ]);

                $table->string('path');

                $table->integer('order')->default(0);

                $table->timestamps();
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('campaign_media');
        }
    };
