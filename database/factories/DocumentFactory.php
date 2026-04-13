<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $text = $this->faker->paragraph(6);

        return [
            'category' => $this->faker->randomElement(['utility_invoice', 'contract', 'appliance_manual', 'building_notice', 'tax', 'insurance', 'other']),
            'title_en' => $this->faker->sentence(4),
            'summary_en' => $this->faker->paragraph(2),
            'counterparty' => $this->faker->company(),
            'issued_on' => $this->faker->dateTimeBetween('-2 years')->format('Y-m-d'),
            'period_start' => null,
            'period_end' => null,
            'amount_huf' => $this->faker->randomFloat(2, 1000, 200000),
            'currency' => 'HUF',
            'raw_text' => $text,
            'raw_text_sha' => hash('sha256', $text.Str::random()),
            'storage_path' => 'apartment/knowledge/other/'.Str::random(8).'.pdf',
            'original_filename' => $this->faker->word().'.pdf',
            'ingested_at' => now(),
            'meta' => [],
        ];
    }
}
