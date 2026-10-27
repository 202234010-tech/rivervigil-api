<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stations')->upsert([
            [
                'id' => 'STA-KATULAMPA',
                'name' => 'Bendung Katulampa',
                'river_basin' => 'ciliwung',
                'location' => 'Bogor, Jawa Barat',
                'current_level_cm' => 120.0,
                'flow_rate_m3s' => 54.2,
                'alert_status' => 'siaga_3',
                'thresholds_cm' => json_encode([
                    'normal' => 80,
                    'siaga_3' => 120,
                    'siaga_2' => 150,
                    'siaga_1' => 200,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'STA-BEKASI',
                'name' => 'Pos Pantau Kali Bekasi',
                'river_basin' => 'bekasi',
                'location' => 'Kota Bekasi, Jawa Barat',
                'current_level_cm' => 90.0,
                'flow_rate_m3s' => 32.5,
                'alert_status' => 'normal',
                'thresholds_cm' => json_encode([
                    'normal' => 100,
                    'siaga_3' => 200,
                    'siaga_2' => 300,
                    'siaga_1' => 400,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'STA-CISADANE',
                'name' => 'Pintu Air 10 Cisadane',
                'river_basin' => 'cisadane',
                'location' => 'Tangerang, Banten',
                'current_level_cm' => 175.0,
                'flow_rate_m3s' => 88.0,
                'alert_status' => 'siaga_2',
                'thresholds_cm' => json_encode([
                    'normal' => 120,
                    'siaga_3' => 160,
                    'siaga_2' => 200,
                    'siaga_1' => 250,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'STA-PESANG',
                'name' => 'Pos Pantau Pesanggrahan',
                'river_basin' => 'pesanggrahan',
                'location' => 'Jakarta Selatan, DKI Jakarta',
                'current_level_cm' => 110.0,
                'flow_rate_m3s' => 45.0,
                'alert_status' => 'siaga_3',
                'thresholds_cm' => json_encode([
                    'normal' => 90,
                    'siaga_3' => 130,
                    'siaga_2' => 170,
                    'siaga_1' => 220,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'STA-ANGKE',
                'name' => 'Pos Pantau Cengkareng Drain',
                'river_basin' => 'angke',
                'location' => 'Jakarta Barat, DKI Jakarta',
                'current_level_cm' => 65.0,
                'flow_rate_m3s' => 18.4,
                'alert_status' => 'normal',
                'thresholds_cm' => json_encode([
                    'normal' => 70,
                    'siaga_3' => 110,
                    'siaga_2' => 150,
                    'siaga_1' => 190,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['id']);
    }
}
