<?php
/**
 * RiverVigil API — Standalone Prototype & Verification Server
 * 
 * Standalone router untuk verifikasi Tugas 2 (Eksplorasi HTTP & API Contract)
 * Jalankan dengan:
 * C:\xampp\php\php.exe -S 127.0.0.1:8000 server.php
 */

// Set header standar CORS & JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Client-App, Authorization');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper function untuk mengirim response JSON seragam
function sendResponse(int $statusCode, string $status, string $message, $data = null, $errors = null, $meta = null) {
    http_response_code($statusCode);
    $response = [
        'status' => $status,
        'message' => $message,
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    if ($meta !== null) {
        $response['meta'] = $meta;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Data Dummy Pos Pantau
$stations = [
    'STA-KATULAMPA' => [
        'id' => 'STA-KATULAMPA',
        'name' => 'Bendung Katulampa',
        'river_basin' => 'ciliwung',
        'location' => 'Bogor, Jawa Barat',
        'current_level_cm' => 120,
        'flow_rate_m3s' => 54.2,
        'alert_status' => 'siaga_3',
        'thresholds_cm' => [
            'normal' => 80,
            'siaga_3' => 120,
            'siaga_2' => 150,
            'siaga_1' => 200,
        ],
        'updated_at' => '2026-09-21T22:30:00+07:00'
    ],
    'STA-DEPOK' => [
        'id' => 'STA-DEPOK',
        'name' => 'Pos Pantau Depok',
        'river_basin' => 'ciliwung',
        'location' => 'Depok, Jawa Barat',
        'current_level_cm' => 185,
        'flow_rate_m3s' => 78.1,
        'alert_status' => 'siaga_3',
        'thresholds_cm' => [
            'normal' => 100,
            'siaga_3' => 175,
            'siaga_2' => 270,
            'siaga_1' => 350,
        ],
        'updated_at' => '2026-09-21T22:25:00+07:00'
    ],
    'STA-MANGGARAI' => [
        'id' => 'STA-MANGGARAI',
        'name' => 'Pintu Air Manggarai',
        'river_basin' => 'ciliwung',
        'location' => 'Jakarta Selatan, DKI Jakarta',
        'current_level_cm' => 720,
        'flow_rate_m3s' => 110.5,
        'alert_status' => 'normal',
        'thresholds_cm' => [
            'normal' => 750,
            'siaga_3' => 850,
            'siaga_2' => 950,
            'siaga_1' => 1050,
        ],
        'updated_at' => '2026-09-21T22:28:00+07:00'
    ]
];

// Routing Logic

// 1. Root / Health Check
if ($requestUri === '/' || $requestUri === '/api' || $requestUri === '/api/v1') {
    sendResponse(200, 'success', 'RiverVigil API Service is operating normally.', [
        'service' => 'RiverVigil REST API',
        'version' => 'v1.0.0',
        'server_time' => date('c'),
        'docs_url' => '/docs/api-contract.md'
    ]);
}

// 2. GET /api/v1/stations — List All Stations with Query Params
if ($method === 'GET' && $requestUri === '/api/v1/stations') {
    $basinFilter = isset($_GET['river_basin']) ? strtolower(trim($_GET['river_basin'])) : null;
    $statusFilter = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    $filtered = array_values(array_filter($stations, function ($item) use ($basinFilter, $statusFilter) {
        if ($basinFilter && strtolower($item['river_basin']) !== $basinFilter) {
            return false;
        }
        if ($statusFilter && strtolower($item['alert_status']) !== $statusFilter) {
            return false;
        }
        return true;
    }));

    sendResponse(200, 'success', 'Daftar stasiun pos pantau berhasil diambil.', $filtered, null, [
        'page' => $page,
        'limit' => $limit,
        'total_records' => count($filtered)
    ]);
}

// 3. GET /api/v1/stations/{id} — Station Details
if ($method === 'GET' && preg_match('#^/api/v1/stations/([^/]+)$#', $requestUri, $matches)) {
    $stationId = strtoupper($matches[1]);
    if (isset($stations[$stationId])) {
        sendResponse(200, 'success', 'Detail stasiun pos pantau ditemukan.', $stations[$stationId]);
    } else {
        sendResponse(404, 'error', "Stasiun pos pantau dengan ID '{$matches[1]}' tidak ditemukan.", null, [
            [
                'field' => 'id',
                'issue' => 'Resource station tidak ada dalam basis data.'
            ]
        ]);
    }
}

// 4. POST /api/v1/telemetry — Log Sensor Reading
if ($method === 'POST' && $requestUri === '/api/v1/telemetry') {
    $rawInput = file_get_contents('php://input');

    // Cek jika body kosong atau JSON malformed
    if (empty(trim($rawInput))) {
        sendResponse(400, 'error', 'Sintaks JSON tidak valid atau format request rusak.', null, [
            [
                'field' => 'payload',
                'issue' => 'Body request kosong atau tidak disertakan.'
            ]
        ]);
    }

    $payload = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(400, 'error', 'Sintaks JSON tidak valid atau format request rusak.', null, [
            [
                'field' => 'payload',
                'issue' => 'Payload yang dikirim bukan format JSON yang valid (malformed JSON: ' . json_last_error_msg() . ').'
            ]
        ]);
    }

    // Validasi field & tipe data
    $errors = [];
    if (empty($payload['station_id'])) {
        $errors[] = [
            'field' => 'station_id',
            'issue' => 'Nilai station_id wajib diisi.'
        ];
    } elseif (!isset($stations[strtoupper($payload['station_id'])])) {
        $errors[] = [
            'field' => 'station_id',
            'issue' => "Stasiun pos pantau dengan ID '{$payload['station_id']}' tidak terdaftar."
        ];
    }

    if (!isset($payload['water_level_cm']) || !is_numeric($payload['water_level_cm']) || $payload['water_level_cm'] < 0) {
        $errors[] = [
            'field' => 'water_level_cm',
            'issue' => 'Nilai water_level_cm wajib diisi numerik dan harus >= 0.'
        ];
    }

    if (!isset($payload['flow_rate_m3s']) || !is_numeric($payload['flow_rate_m3s']) || $payload['flow_rate_m3s'] < 0) {
        $errors[] = [
            'field' => 'flow_rate_m3s',
            'issue' => 'Nilai flow_rate_m3s wajib diisi numerik dan harus >= 0.'
        ];
    }

    if (empty($payload['recorded_at'])) {
        $errors[] = [
            'field' => 'recorded_at',
            'issue' => 'Field recorded_at (timestamp ISO 8601) wajib disertakan.'
        ];
    }

    // Jika terdapat kesalahan validasi logika -> 422 Unprocessable Content
    if (!empty($errors)) {
        sendResponse(422, 'error', 'Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.', null, $errors);
    }

    // Sukses -> 201 Created
    $telemetryId = 'TLM-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 4, '0', STR_PAD_LEFT);
    $waterLevel = (float)$payload['water_level_cm'];
    
    // Hitung status siaga dinamis
    $calcAlert = 'normal';
    if ($waterLevel >= 200) {
        $calcAlert = 'siaga_1';
    } elseif ($waterLevel >= 150) {
        $calcAlert = 'siaga_2';
    } elseif ($waterLevel >= 120) {
        $calcAlert = 'siaga_3';
    }

    $createdData = [
        'telemetry_id' => $telemetryId,
        'station_id' => strtoupper($payload['station_id']),
        'water_level_cm' => $waterLevel,
        'flow_rate_m3s' => (float)$payload['flow_rate_m3s'],
        'rainfall_intensity_mmh' => isset($payload['rainfall_intensity_mmh']) ? (float)$payload['rainfall_intensity_mmh'] : 0.0,
        'calculated_alert' => $calcAlert,
        'recorded_at' => $payload['recorded_at'],
        'created_at' => date('c')
    ];

    sendResponse(201, 'success', 'Data telemetri sensor berhasil dicatat ke sistem.', $createdData);
}

// 5. GET /api/v1/alerts/active — Active Alerts
if ($method === 'GET' && $requestUri === '/api/v1/alerts/active') {
    $activeAlerts = [
        [
            'alert_id' => 'ALT-20260921-01',
            'station_id' => 'STA-KATULAMPA',
            'station_name' => 'Bendung Katulampa',
            'river_basin' => 'ciliwung',
            'severity' => 'siaga_3',
            'description' => 'Ketinggian air mencapai 120 cm akibat hujan di hulu Puncak.',
            'estimated_arrival_depok' => '2026-09-22T02:30:00+07:00',
            'estimated_arrival_manggarai' => '2026-09-22T06:30:00+07:00'
        ],
        [
            'alert_id' => 'ALT-20260921-02',
            'station_id' => 'STA-DEPOK',
            'station_name' => 'Pos Pantau Depok',
            'river_basin' => 'ciliwung',
            'severity' => 'siaga_3',
            'description' => 'Ketinggian air mencapai 185 cm, status siaga 3.',
            'estimated_arrival_depok' => null,
            'estimated_arrival_manggarai' => '2026-09-22T04:30:00+07:00'
        ]
    ];

    sendResponse(200, 'success', 'Daftar status peringatan dini aktif berhasil dimuat.', $activeAlerts);
}

// 6. Endpoint Fallback / Route Not Found -> 404
sendResponse(404, 'error', "Endpoint '{$method} {$requestUri}' tidak terdaftar pada layanan RiverVigil API.", null, [
    [
        'field' => 'path',
        'issue' => 'Rute API tidak ditemukan atau method HTTP tidak diizinkan.'
    ]
]);
