<?php

namespace Tests\Feature;

use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StationSeeder::class);
    }

    public function test_can_list_stations_with_200_ok(): void
    {
        $response = $this->getJson('/api/stations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'river_basin',
                        'location',
                        'current_level_cm',
                        'flow_rate_m3s',
                        'alert_status',
                        'thresholds_cm',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'page',
                    'limit',
                    'total_records',
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_can_filter_stations_by_river_basin(): void
    {
        $response = $this->getJson('/api/stations?river_basin=ciliwung');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'meta' => [
                    'total_records' => 1,
                ],
            ]);

        $this->assertEquals('ciliwung', $response->json('data.0.river_basin'));
    }

    public function test_can_get_single_station_detail_with_200_ok(): void
    {
        $response = $this->getJson('/api/stations/STA-KATULAMPA');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => 'STA-KATULAMPA',
                    'name' => 'Bendung Katulampa',
                    'river_basin' => 'ciliwung',
                ],
            ]);
    }

    public function test_returns_404_when_station_not_found(): void
    {
        $response = $this->getJson('/api/stations/STA-UNKNOWN');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => "Stasiun pos pantau dengan ID 'STA-UNKNOWN' tidak ditemukan.",
                'errors' => [
                    [
                        'field' => 'id',
                        'issue' => 'Resource station tidak ada dalam basis data.',
                    ],
                ],
            ]);
    }

    public function test_v1_prefixed_routes_work_identically(): void
    {
        $this->getJson('/api/v1/stations')->assertStatus(200);
        $this->getJson('/api/v1/stations/STA-KATULAMPA')->assertStatus(200);
    }

    public function test_can_create_station_with_201_created(): void
    {
        $payload = [
            'id' => 'STA-MANGA',
            'name' => 'Pintu Air Manggarai',
            'river_basin' => 'ciliwung',
            'location' => 'Jakarta Selatan, DKI Jakarta',
            'current_level_cm' => 650.0,
            'flow_rate_m3s' => 120.5,
            'alert_status' => 'normal',
            'thresholds_cm' => [
                'normal' => 750,
                'siaga_3' => 850,
                'siaga_2' => 950,
                'siaga_1' => 1050,
            ],
        ];

        $response = $this->postJson('/api/stations', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pos pantau stasiun sungai baru berhasil didaftarkan.',
                'data' => [
                    'id' => 'STA-MANGA',
                    'name' => 'Pintu Air Manggarai',
                    'river_basin' => 'ciliwung',
                    'current_level_cm' => 650.0,
                    'flow_rate_m3s' => 120.5,
                    'alert_status' => 'normal',
                ],
            ]);

        $this->assertDatabaseHas('stations', [
            'id' => 'STA-MANGA',
            'name' => 'Pintu Air Manggarai',
        ]);
    }

    public function test_validation_fails_on_duplicate_id_and_missing_fields_with_422(): void
    {
        $payload = [
            'id' => 'STA-KATULAMPA', // duplicate ID
            'name' => '',            // empty name
        ];

        $response = $this->postJson('/api/stations', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    '*' => ['field', 'issue'],
                ],
            ]);
    }

    public function test_can_partially_update_station_with_200_ok(): void
    {
        $payload = [
            'current_level_cm' => 140.5,
            'alert_status' => 'siaga_3',
        ];

        $response = $this->patchJson('/api/stations/STA-KATULAMPA', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data stasiun pos pantau berhasil diperbarui.',
                'data' => [
                    'id' => 'STA-KATULAMPA',
                    'current_level_cm' => 140.5,
                    'alert_status' => 'siaga_3',
                ],
            ]);

        $this->assertDatabaseHas('stations', [
            'id' => 'STA-KATULAMPA',
            'current_level_cm' => 140.5,
            'alert_status' => 'siaga_3',
        ]);
    }

    public function test_update_fails_with_invalid_alert_status_with_422(): void
    {
        $payload = [
            'alert_status' => 'status_tidak_valid',
        ];

        $response = $this->patchJson('/api/stations/STA-KATULAMPA', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_update_returns_404_when_station_not_found(): void
    {
        $response = $this->patchJson('/api/stations/STA-UNKNOWN', [
            'name' => 'Stasiun Fiktif',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => "Stasiun pos pantau dengan ID 'STA-UNKNOWN' tidak ditemukan.",
            ]);
    }

    public function test_can_delete_station_with_204_no_content(): void
    {
        $response = $this->deleteJson('/api/stations/STA-KATULAMPA');

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());

        $this->assertDatabaseMissing('stations', [
            'id' => 'STA-KATULAMPA',
        ]);

        $verify = $this->getJson('/api/stations/STA-KATULAMPA');
        $verify->assertStatus(404);
    }

    public function test_delete_returns_404_when_station_not_found(): void
    {
        $response = $this->deleteJson('/api/stations/STA-UNKNOWN');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => "Stasiun pos pantau dengan ID 'STA-UNKNOWN' tidak ditemukan.",
            ]);
    }

    public function test_can_paginate_stations_with_custom_per_page_and_page(): void
    {
        $response = $this->getJson('/api/stations?per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 2,
                    'last_page' => 3,
                    'total_records' => 5,
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertNotNull($response->json('links.next'));

        $page2 = $this->getJson('/api/stations?per_page=2&page=2');
        $page2->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 2,
                ],
            ]);
        $this->assertCount(2, $page2->json('data'));
        $this->assertNotNull($page2->json('links.prev'));
    }

    public function test_can_search_stations_by_name_and_location(): void
    {
        // Search by name
        $nameSearch = $this->getJson('/api/stations?search=Bendung');
        $nameSearch->assertStatus(200);
        $this->assertEquals('STA-KATULAMPA', $nameSearch->json('data.0.id'));

        // Search by location
        $locationSearch = $this->getJson('/api/stations?search=Jakarta');
        $locationSearch->assertStatus(200);
        $this->assertCount(2, $locationSearch->json('data'));
    }

    public function test_can_sort_stations_ascending_and_descending(): void
    {
        $desc = $this->getJson('/api/stations?sort=name&direction=desc&per_page=10');
        $desc->assertStatus(200);
        $namesDesc = collect($desc->json('data'))->pluck('name')->toArray();
        $sortedDesc = $namesDesc;
        rsort($sortedDesc);
        $this->assertEquals($sortedDesc, $namesDesc);

        $asc = $this->getJson('/api/stations?sort=name&direction=asc&per_page=10');
        $asc->assertStatus(200);
        $namesAsc = collect($asc->json('data'))->pluck('name')->toArray();
        $sortedAsc = $namesAsc;
        sort($sortedAsc);
        $this->assertEquals($sortedAsc, $namesAsc);
    }

    public function test_query_validation_fails_on_invalid_sort_with_422(): void
    {
        $response = $this->getJson('/api/stations?sort=password');

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi query parameter gagal. Periksa kembali parameter yang dikirimkan.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    '*' => ['field', 'issue'],
                ],
            ]);
    }

    public function test_query_validation_fails_on_invalid_per_page_with_422(): void
    {
        $response = $this->getJson('/api/stations?per_page=100');

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_can_combine_search_sort_and_pagination_with_query_preserved(): void
    {
        $response = $this->getJson('/api/stations?search=Pos&sort=name&direction=asc&per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 2,
                    'total_records' => 3,
                ],
            ]);

        $this->assertStringContainsString('search=Pos', $response->json('links.first'));
        $this->assertStringContainsString('sort=name', $response->json('links.first'));
        $this->assertStringContainsString('direction=asc', $response->json('links.first'));
        $this->assertStringContainsString('per_page=2', $response->json('links.first'));
    }
}
