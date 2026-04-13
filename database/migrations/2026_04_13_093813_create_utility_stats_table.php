<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_stats', function (Blueprint $table) {
            $table->id();
            $table->string('utility_type', 32);
            $table->date('window_end');
            $table->decimal('rolling_avg_12m', 14, 3)->nullable();
            $table->decimal('last_value', 14, 3)->nullable();
            $table->decimal('yoy_delta', 8, 4)->nullable();
            $table->boolean('anomaly')->default(false);
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['utility_type', 'window_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_stats');
    }
};
