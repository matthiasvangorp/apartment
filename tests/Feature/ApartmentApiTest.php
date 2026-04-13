<?php

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\Document;
use App\Models\MaintenanceTask;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApartmentApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-token-abc123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['apartment.api_token' => $this->token]);
    }

    private function authed(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_endpoints_require_bearer_token(): void
    {
        $this->getJson('/api/v1/appliances')->assertStatus(401);
        $this->getJson('/api/v1/utility/summary?type=electricity')->assertStatus(401);
        $this->getJson('/api/v1/maintenance/upcoming')->assertStatus(401);
        $this->getJson('/api/v1/documents/search')->assertStatus(401);
    }

    public function test_wrong_token_is_rejected(): void
    {
        $this->getJson('/api/v1/appliances', ['Authorization' => 'Bearer wrong'])
            ->assertStatus(401);
    }

    public function test_appliances_returns_seeded_data(): void
    {
        $appliance = Appliance::factory()->create(['name' => 'Test fridge']);
        MaintenanceTask::factory()->for($appliance)->create([
            'title' => 'Defrost',
            'next_due_on' => '2026-09-01',
            'cadence_months' => 6,
        ]);

        $resp = $this->getJson('/api/v1/appliances', $this->authed())->assertOk();
        $resp->assertJsonPath('appliances.0.name', 'Test fridge');
        $resp->assertJsonPath('appliances.0.next_maintenance_due', '2026-09-01');
        $resp->assertJsonCount(1, 'appliances.0.tasks');
    }

    public function test_utility_summary_returns_stats_and_trend(): void
    {
        $doc = Document::factory()->create();
        UtilityReading::factory()->for($doc)->create([
            'utility_type' => 'electricity',
            'period_start' => '2025-06-01',
            'period_end' => '2025-06-30',
            'consumption_value' => 200,
            'consumption_unit' => 'kWh',
            'amount_huf' => 5000,
        ]);

        $resp = $this->getJson('/api/v1/utility/summary?type=electricity', $this->authed())->assertOk();
        $resp->assertJsonPath('type', 'electricity');
        $resp->assertJsonPath('available', true);
        $resp->assertJsonPath('last_value', 200);
        $resp->assertJsonCount(1, 'trend');
    }

    public function test_utility_summary_rejects_invalid_type(): void
    {
        $this->getJson('/api/v1/utility/summary?type=plutonium', $this->authed())->assertStatus(422);
    }

    public function test_utility_readings_filters_by_date_range(): void
    {
        $doc = Document::factory()->create();
        UtilityReading::factory()->for($doc)->create(['utility_type' => 'electricity', 'period_end' => '2025-01-31', 'consumption_value' => 100]);
        UtilityReading::factory()->for($doc)->create(['utility_type' => 'electricity', 'period_end' => '2025-06-30', 'consumption_value' => 150]);
        UtilityReading::factory()->for($doc)->create(['utility_type' => 'electricity', 'period_end' => '2025-12-31', 'consumption_value' => 200]);

        $resp = $this->getJson('/api/v1/utility/readings?type=electricity&from=2025-06-01&to=2025-11-30', $this->authed())->assertOk();
        $resp->assertJsonCount(1, 'readings');
        $resp->assertJsonPath('readings.0.consumption', 150);
    }

    public function test_maintenance_upcoming_respects_window(): void
    {
        $appliance = Appliance::factory()->create();
        MaintenanceTask::factory()->for($appliance)->create(['next_due_on' => now()->addDays(10)->toDateString()]);
        MaintenanceTask::factory()->for($appliance)->create(['next_due_on' => now()->addDays(200)->toDateString()]);

        $resp = $this->getJson('/api/v1/maintenance/upcoming?within_days=30', $this->authed())->assertOk();
        $resp->assertJsonCount(1, 'tasks');
    }

    public function test_documents_search_filters_by_category(): void
    {
        Document::factory()->create(['category' => 'utility_invoice', 'title_en' => 'electricity bill']);
        Document::factory()->create(['category' => 'tax', 'title_en' => 'tax letter']);

        $resp = $this->getJson('/api/v1/documents/search?category=utility_invoice', $this->authed())->assertOk();
        $resp->assertJsonCount(1, 'results');
        $resp->assertJsonPath('results.0.category', 'utility_invoice');
    }

    public function test_document_show_returns_signed_download_url(): void
    {
        $doc = Document::factory()->create();
        $resp = $this->getJson('/api/v1/documents/'.$doc->id, $this->authed())->assertOk();
        $resp->assertJsonPath('id', $doc->id);
        $this->assertStringContainsString('signature=', $resp->json('download_url'));
    }

    public function test_unsigned_download_is_forbidden(): void
    {
        $doc = Document::factory()->create();
        $this->get('/api/v1/documents/'.$doc->id.'/download')->assertStatus(403);
    }
}
