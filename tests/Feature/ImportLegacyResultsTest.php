<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\RideResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLegacyResultsTest extends TestCase
{
    use RefreshDatabase;

    private string $dumpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dumpPath = storage_path('framework/testing/legacy-import.sql');
        file_put_contents($this->dumpPath, <<<'SQL'
INSERT INTO `ride_results` (`id`, `login`, `distance`, `week`, `year`, `created_at`, `updated_at`) VALUES
(10, 'Тестовий Учасник ', 42.5, 70, 2025, '2026-05-12 10:00:00', '2026-05-12 10:00:00'),
(11, 'Тестовий Учасник ', 0, 70, 2025, '2026-05-13 10:00:00', '2026-05-13 10:00:00'),
(12, 'Інший Учасник', 15, 1, 2025, '2025-01-02 10:00:00', '2025-01-02 10:00:00');
INSERT INTO `ride_users` (`id`, `login`, `total_distance`, `coins`, `join_week`, `join_year`, `created_at`, `updated_at`) VALUES
(5, 'Тестовий Учасник ', 42.5, 0.42, 1, 2022, '2022-07-18 00:00:00', '2026-05-12 10:00:00'),
(6, 'Інший Учасник', 15, 0.15, 1, 2022, '2022-07-18 00:00:00', '2025-01-02 10:00:00');
SQL);
    }

    protected function tearDown(): void
    {
        @unlink($this->dumpPath);
        parent::tearDown();
    }

    public function test_imports_calendar_year_by_date_and_is_idempotent(): void
    {
        $this->artisan('legacy:import-results', ['path' => $this->dumpPath, '--year' => 2026])
            ->assertSuccessful();

        $this->assertSame(1, Participant::count());
        $this->assertSame(1, RideResult::count());
        $this->assertDatabaseHas('ride_results', [
            'legacy_source' => 'legacy_ride_results',
            'legacy_id' => 10,
            'distance_km' => 42.50,
        ]);
        $this->assertDatabaseHas('weekly_periods', [
            'year' => 2026,
            'week_number' => 20,
            'start_date' => '2026-05-11 00:00:00',
            'end_date' => '2026-05-18 00:00:00',
        ]);

        $this->artisan('legacy:import-results', ['path' => $this->dumpPath, '--year' => 2026])
            ->assertSuccessful();

        $this->assertSame(1, Participant::count());
        $this->assertSame(1, RideResult::count());
    }
}
