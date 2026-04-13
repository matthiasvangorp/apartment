<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->index();
            $table->string('title_en')->nullable();
            $table->text('summary_en')->nullable();
            $table->string('counterparty')->nullable()->index();
            $table->date('issued_on')->nullable()->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('amount_huf', 14, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->longText('raw_text')->nullable();
            $table->char('raw_text_sha', 64)->unique();
            $table->string('storage_path');
            $table->string('original_filename');
            $table->timestamp('ingested_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE documents ADD FULLTEXT documents_fulltext (title_en, summary_en, raw_text)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
