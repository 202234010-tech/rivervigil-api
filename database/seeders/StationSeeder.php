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
        ], ['id']);
    }
}
