<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('utility_type', 32)->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('consumption_value', 14, 3)->nullable();
            $table->string('consumption_unit', 16)->nullable();
            $table->decimal('amount_huf', 14, 2)->nullable();
            $table->string('meter_serial')->nullable();
            $table->timestamps();

            $table->index(['utility_type', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_readings');
    }
};
