<?php
/**
 * RiverVigil API — Standalone Prototype & Verification Server
 * 
 * Modul: Praktikum 3 — Merancang API Contract
 * Mata Kuliah: Manajemen Layanan Web / Web Service Management
 * 
 * Jalankan dengan:
 * C:\xampp\php\php.exe -S 127.0.0.1:8000 server.php
 */

// Set header standar CORS & JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Client-App, Authorization, X-Request-ID');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper function untuk mengirim response JSON seragam (Standard Envelope)
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

// Helper untuk membaca dan memvalidasi JSON body request
function getJsonPayload() {
    $rawInput = file_get_contents('php://input');

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

    return $payload;
}

// ==========================================
// DATASTORE IN-MEMORY (MOCK DATA)
// ==========================================

// 1. Data Pos Pantau Sungai (Stations)
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
        'updated_at' => '2026-09-25T10:30:00+07:00'
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
        'updated_at' => '2026-09-25T10:25:00+07:00'
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
        'updated_at' => '2026-09-25T10:28:00+07:00'
    ]
];

// 2. Data Riwayat Pembacaan Telemetri (Telemetry Logs)
$telemetries = [
    'STA-KATULAMPA' => [
        [
            'telemetry_id' => 'TLM-20260925-0101',
            'station_id' => 'STA-KATULAMPA',
            'water_level_cm' => 120.0,
            'flow_rate_m3s' => 54.2,
            'rainfall_intensity_mmh' => 25.0,
            'calculated_alert' => 'siaga_3',
            'recorded_at' => '2026-09-25T10:30:00+07:00'
        ],
        [
            'telemetry_id' => 'TLM-20260925-0100',
            'station_id' => 'STA-KATULAMPA',
            'water_level_cm' => 105.0,
            'flow_rate_m3s' => 45.0,
            'rainfall_intensity_mmh' => 40.5,
            'calculated_alert' => 'normal',
            'recorded_at' => '2026-09-25T10:00:00+07:00'
        ]
    ],
    'STA-DEPOK' => [
        [
            'telemetry_id' => 'TLM-20260925-0201',
            'station_id' => 'STA-DEPOK',
            'water_level_cm' => 185.0,
            'flow_rate_m3s' => 78.1,
            'rainfall_intensity_mmh' => 15.0,
            'calculated_alert' => 'siaga_3',
            'recorded_at' => '2026-09-25T10:25:00+07:00'
        ]
    ]
];

// 3. Data Peringatan Dini Bencana (Alerts)
$alerts = [
    'ALT-20260925-01' => [
        'alert_id' => 'ALT-20260925-01',
        'station_id' => 'STA-KATULAMPA',
        'station_name' => 'Bendung Katulampa',
        'river_basin' => 'ciliwung',
        'severity' => 'siaga_3',
        'status' => 'active',
        'description' => 'Ketinggian air mencapai 120 cm (Siaga 3) akibat hujan deras di kawasan Puncak Bogor.',
        'estimated_arrival_depok' => '2026-09-25T14:30:00+07:00',
        'estimated_arrival_manggarai' => '2026-09-25T18:30:00+07:00',
        'published_at' => '2026-09-25T10:35:00+07:00',
        'resolved_at' => null
    ],
    'ALT-20260925-02' => [
        'alert_id' => 'ALT-20260925-02',
        'station_id' => 'STA-DEPOK',
        'station_name' => 'Pos Pantau Depok',
        'river_basin' => 'ciliwung',
        'severity' => 'siaga_3',
        'status' => 'active',
        'description' => 'Ketinggian air mencapai 185 cm, siaga 3 waspada banjir kiriman ke arah Jakarta.',
        'estimated_arrival_depok' => null,
        'estimated_arrival_manggarai' => '2026-09-25T16:30:00+07:00',
        'published_at' => '2026-09-25T10:30:00+07:00',
        'resolved_at' => null
    ]
];

// 4. Data Posko Pengungsian & Logistik (Shelters)
$shelters = [
    'SHL-001' => [
        'shelter_id' => 'SHL-001',
        'name' => 'GOR Otista Jatinegara',
        'address' => 'Jl. Otto Iskandardinata No. 121, Jakarta Timur',
        'latitude' => -6.230154,
        'longitude' => 106.868214,
        'capacity_people' => 500,
        'current_occupants' => 45,
        'status' => 'ready',
        'contact_person' => 'Bambang Sudibyo (0812-3456-7890)',
        'facilities' => ['Dapur Umum', 'Tenda Medis', 'MCK Portabel', 'Logistik Bayi']
    ],
    'SHL-002' => [
        'shelter_id' => 'SHL-002',
        'name' => 'Masjid Jami Attahiriyah',
        'address' => 'Jl. Melayu Kecil No. 4, Tebet, Jakarta Selatan',
        'latitude' => -6.226841,
        'longitude' => 106.860112,
        'capacity_people' => 300,
        'current_occupants' => 0,
        'status' => 'ready',
        'contact_person' => 'H. Suryadi (0813-8899-1122)',
        'facilities' => ['Aula Luas', 'Tempat Wudhu/MCK', 'Dapur Darurat']
    ]
];

// 5. Data Laporan Masyarakat / Relawan (Crowdsourced Reports)
$reports = [
    'REP-20260925-01' => [
        'report_id' => 'REP-20260925-01',
        'reporter_name' => 'Agus Prayitno',
        'reporter_phone' => '081298765432',
        'location_name' => 'Bidara Cina RT 05 / RW 07',
        'inundation_height_cm' => 40,
        'notes' => 'Air luapan kali mulai masuk ke jalan permukiman warga setinggi betis.',
        'status' => 'verified',
        'created_at' => '2026-09-25T10:45:00+07:00'
    ]
];

// ==========================================
// ROUTING LOGIC & RESTFUL ENDPOINTS
// ==========================================

// 1. Root / Service Health Check
if ($requestUri === '/' || $requestUri === '/api' || $requestUri === '/api/v1') {
    sendResponse(200, 'success', 'RiverVigil API Service is operating normally.', [
        'service' => 'RiverVigil Disaster Early Warning & River Telemetry System',
        'version' => 'v1.0.0',
        'server_time' => date('c'),
        'api_contract_version' => '1.1.0',
        'contract_docs' => '/docs/api-contract.md',
        'active_resources' => [
            'stations' => '/api/v1/stations',
            'telemetry' => '/api/v1/telemetry',
            'alerts' => '/api/v1/alerts/active',
            'shelters' => '/api/v1/shelters',
            'reports' => '/api/v1/reports'
        ]
    ]);
}

// ------------------------------------------
// RESOURCE 1: STATIONS (/api/v1/stations)
// ------------------------------------------

// 2.1 GET /api/v1/stations — List All Stations with Query Filtering & Pagination
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

// 2.2 POST /api/v1/stations — Create New Station
if ($method === 'POST' && $requestUri === '/api/v1/stations') {
    $payload = getJsonPayload();
    $errors = [];

    if (empty($payload['id'])) {
        $errors[] = ['field' => 'id', 'issue' => 'Nilai ID stasiun pos pantau wajib diisi (misal: STA-BEKASI).'];
    } elseif (isset($stations[strtoupper($payload['id'])])) {
        $errors[] = ['field' => 'id', 'issue' => "Stasiun dengan ID '{$payload['id']}' sudah terdaftar."];
    }

    if (empty($payload['name'])) {
        $errors[] = ['field' => 'name', 'issue' => 'Nama stasiun pos pantau wajib diisi.'];
    }
    if (empty($payload['river_basin'])) {
        $errors[] = ['field' => 'river_basin', 'issue' => 'Nama Daerah Aliran Sungai (river_basin) wajib diisi.'];
    }
    if (empty($payload['location'])) {
        $errors[] = ['field' => 'location', 'issue' => 'Deskripsi lokasi stasiun wajib diisi.'];
    }
    if (!isset($payload['thresholds_cm']) || !is_array($payload['thresholds_cm'])) {
        $errors[] = ['field' => 'thresholds_cm', 'issue' => 'Object ambang batas siaga (thresholds_cm) wajib disediakan.'];
    }

    if (!empty($errors)) {
        sendResponse(422, 'error', 'Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.', null, $errors);
    }

    $stationId = strtoupper($payload['id']);
    $newStation = [
        'id' => $stationId,
        'name' => trim($payload['name']),
        'river_basin' => strtolower(trim($payload['river_basin'])),
        'location' => trim($payload['location']),
        'current_level_cm' => isset($payload['current_level_cm']) ? (float)$payload['current_level_cm'] : 0,
        'flow_rate_m3s' => isset($payload['flow_rate_m3s']) ? (float)$payload['flow_rate_m3s'] : 0.0,
        'alert_status' => 'normal',
        'thresholds_cm' => $payload['thresholds_cm'],
        'updated_at' => date('c')
    ];

    $stations[$stationId] = $newStation;
    header('Location: /api/v1/stations/' . $stationId);
    sendResponse(201, 'success', 'Pos pantau stasiun sungai baru berhasil didaftarkan.', $newStation);
}

// 2.3 GET /api/v1/stations/{id} — Station Details
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

// 2.4 PUT /api/v1/stations/{id} — Update Station Entirely
if ($method === 'PUT' && preg_match('#^/api/v1/stations/([^/]+)$#', $requestUri, $matches)) {
    $stationId = strtoupper($matches[1]);
    if (!isset($stations[$stationId])) {
        sendResponse(404, 'error', "Stasiun pos pantau dengan ID '{$matches[1]}' tidak ditemukan.", null, [
            [
                'field' => 'id',
                'issue' => 'Resource station yang akan diperbarui tidak ada dalam basis data.'
            ]
        ]);
    }

    $payload = getJsonPayload();
    $errors = [];

    if (empty($payload['name'])) {
        $errors[] = ['field' => 'name', 'issue' => 'Nama stasiun pos pantau wajib diisi.'];
    }
    if (empty($payload['river_basin'])) {
        $errors[] = ['field' => 'river_basin', 'issue' => 'Nama Daerah Aliran Sungai wajib diisi.'];
    }
    if (empty($payload['location'])) {
        $errors[] = ['field' => 'location', 'issue' => 'Deskripsi lokasi wajib diisi.'];
    }

    if (!empty($errors)) {
        sendResponse(422, 'error', 'Validasi pembaruan stasiun gagal.', null, $errors);
    }

    $stations[$stationId]['name'] = trim($payload['name']);
    $stations[$stationId]['river_basin'] = strtolower(trim($payload['river_basin']));
    $stations[$stationId]['location'] = trim($payload['location']);
    if (isset($payload['thresholds_cm']) && is_array($payload['thresholds_cm'])) {
        $stations[$stationId]['thresholds_cm'] = $payload['thresholds_cm'];
    }
    $stations[$stationId]['updated_at'] = date('c');

    sendResponse(200, 'success', "Data stasiun pos pantau '{$stationId}' berhasil diperbarui.", $stations[$stationId]);
}

// 2.5 DELETE /api/v1/stations/{id} — Delete Station
if ($method === 'DELETE' && preg_match('#^/api/v1/stations/([^/]+)$#', $requestUri, $matches)) {
    $stationId = strtoupper($matches[1]);
    if (!isset($stations[$stationId])) {
        sendResponse(404, 'error', "Stasiun pos pantau dengan ID '{$matches[1]}' tidak ditemukan.", null, [
            [
                'field' => 'id',
                'issue' => 'Resource station yang akan dihapus tidak ditemukan.'
            ]
        ]);
    }

    unset($stations[$stationId]);
    sendResponse(200, 'success', "Stasiun pos pantau '{$stationId}' berhasil dihapus dari sistem.");
}

// 2.6 GET /api/v1/stations/{id}/telemetry — Sub-resource: Historical Readings for Specific Station
if ($method === 'GET' && preg_match('#^/api/v1/stations/([^/]+)/telemetry$#', $requestUri, $matches)) {
    $stationId = strtoupper($matches[1]);
    if (!isset($stations[$stationId])) {
        sendResponse(404, 'error', "Stasiun pos pantau dengan ID '{$matches[1]}' tidak ditemukan.", null, [
            [
                'field' => 'id',
                'issue' => 'Resource induk stasiun tidak ditemukan.'
            ]
        ]);
    }

    $data = isset($telemetries[$stationId]) ? $telemetries[$stationId] : [];
    sendResponse(200, 'success', "Riwayat data telemetri untuk stasiun '{$stationId}' berhasil diambil.", $data, null, [
        'station_id' => $stationId,
        'total_logs' => count($data)
    ]);
}

// ------------------------------------------
// RESOURCE 2: TELEMETRY (/api/v1/telemetry)
// ------------------------------------------

// 3.1 POST /api/v1/telemetry — Ingest Telemetry Sensor Reading
if ($method === 'POST' && $requestUri === '/api/v1/telemetry') {
    $payload = getJsonPayload();

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

    $stationKey = strtoupper($payload['station_id']);
    $waterLevel = (float)$payload['water_level_cm'];
    $flowRate = (float)$payload['flow_rate_m3s'];
    $rainfall = isset($payload['rainfall_intensity_mmh']) ? (float)$payload['rainfall_intensity_mmh'] : 0.0;

    // Kalkulasi status siaga berdasarkan ambang batas stasiun
    $stThresholds = $stations[$stationKey]['thresholds_cm'];
    $calcAlert = 'normal';
    if ($waterLevel >= $stThresholds['siaga_1']) {
        $calcAlert = 'siaga_1';
    } elseif ($waterLevel >= $stThresholds['siaga_2']) {
        $calcAlert = 'siaga_2';
    } elseif ($waterLevel >= $stThresholds['siaga_3']) {
        $calcAlert = 'siaga_3';
    }

    // Perbarui status terkini pada pos pantau terkait
    $stations[$stationKey]['current_level_cm'] = $waterLevel;
    $stations[$stationKey]['flow_rate_m3s'] = $flowRate;
    $stations[$stationKey]['alert_status'] = $calcAlert;
    $stations[$stationKey]['updated_at'] = date('c');

    $telemetryId = 'TLM-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 4, '0', STR_PAD_LEFT);
    $createdData = [
        'telemetry_id' => $telemetryId,
        'station_id' => $stationKey,
        'water_level_cm' => $waterLevel,
        'flow_rate_m3s' => $flowRate,
        'rainfall_intensity_mmh' => $rainfall,
        'calculated_alert' => $calcAlert,
        'recorded_at' => $payload['recorded_at'],
        'created_at' => date('c')
    ];

    if (!isset($telemetries[$stationKey])) {
        $telemetries[$stationKey] = [];
    }
    array_unshift($telemetries[$stationKey], $createdData);

    sendResponse(201, 'success', 'Data telemetri sensor berhasil dicatat ke sistem.', $createdData);
}

// ------------------------------------------
// RESOURCE 3: ALERTS (/api/v1/alerts)
// ------------------------------------------

// 4.1 GET /api/v1/alerts/active — Active Early Warning Alerts
if ($method === 'GET' && $requestUri === '/api/v1/alerts/active') {
    $activeAlerts = array_values(array_filter($alerts, function ($a) {
        return $a['status'] === 'active';
    }));

    sendResponse(200, 'success', 'Daftar status peringatan dini aktif berhasil dimuat.', $activeAlerts, null, [
        'total_active' => count($activeAlerts)
    ]);
}

// 4.2 POST /api/v1/alerts — Broadcast New Alert
if ($method === 'POST' && $requestUri === '/api/v1/alerts') {
    $payload = getJsonPayload();
    $errors = [];

    if (empty($payload['station_id'])) {
        $errors[] = ['field' => 'station_id', 'issue' => 'Nilai station_id pos pantau wajib diisi.'];
    } elseif (!isset($stations[strtoupper($payload['station_id'])])) {
        $errors[] = ['field' => 'station_id', 'issue' => "Pos pantau dengan ID '{$payload['station_id']}' tidak ditemukan."];
    }

    if (empty($payload['severity']) || !in_array($payload['severity'], ['siaga_1', 'siaga_2', 'siaga_3'])) {
        $errors[] = ['field' => 'severity', 'issue' => 'Tingkat bahaya (severity) harus salah satu dari: siaga_1, siaga_2, siaga_3.'];
    }

    if (empty($payload['description'])) {
        $errors[] = ['field' => 'description', 'issue' => 'Deskripsi peringatan dini wajib diisi.'];
    }

    if (!empty($errors)) {
        sendResponse(422, 'error', 'Validasi peringatan dini gagal.', null, $errors);
    }

    $alertId = 'ALT-' . date('Ymd') . '-' . str_pad((string)rand(10, 99), 2, '0', STR_PAD_LEFT);
    $st = $stations[strtoupper($payload['station_id'])];

    $newAlert = [
        'alert_id' => $alertId,
        'station_id' => $st['id'],
        'station_name' => $st['name'],
        'river_basin' => $st['river_basin'],
        'severity' => $payload['severity'],
        'status' => 'active',
        'description' => trim($payload['description']),
        'estimated_arrival_depok' => isset($payload['estimated_arrival_depok']) ? $payload['estimated_arrival_depok'] : null,
        'estimated_arrival_manggarai' => isset($payload['estimated_arrival_manggarai']) ? $payload['estimated_arrival_manggarai'] : null,
        'published_at' => date('c'),
        'resolved_at' => null
    ];

    $alerts[$alertId] = $newAlert;
    sendResponse(201, 'success', 'Peringatan dini banjir berhasil diterbitkan.', $newAlert);
}

// 4.3 PATCH /api/v1/alerts/{id}/resolve — Resolve / Revoke Alert
if ($method === 'PATCH' && preg_match('#^/api/v1/alerts/([^/]+)/resolve$#', $requestUri, $matches)) {
    $alertId = strtoupper($matches[1]);
    if (!isset($alerts[$alertId])) {
        sendResponse(404, 'error', "Peringatan dini dengan ID '{$matches[1]}' tidak ditemukan.", null, [
            [
                'field' => 'id',
                'issue' => 'Resource alert tidak ada dalam basis data.'
            ]
        ]);
    }

    $alerts[$alertId]['status'] = 'resolved';
    $alerts[$alertId]['resolved_at'] = date('c');

    sendResponse(200, 'success', "Peringatan dini '{$alertId}' berhasil diselesaikan (status dicabut).", $alerts[$alertId]);
}

// ------------------------------------------
// RESOURCE 4: SHELTERS (/api/v1/shelters)
// ------------------------------------------

// 5.1 GET /api/v1/shelters — List Evacuation Shelters
if ($method === 'GET' && $requestUri === '/api/v1/shelters') {
    $shelterList = array_values($shelters);
    sendResponse(200, 'success', 'Daftar posko pengungsian tanggap darurat berhasil diambil.', $shelterList, null, [
        'total_shelters' => count($shelterList)
    ]);
}

// 5.2 GET /api/v1/shelters/{id} — Detail Specific Shelter
if ($method === 'GET' && preg_match('#^/api/v1/shelters/([^/]+)$#', $requestUri, $matches)) {
    $shelterId = strtoupper($matches[1]);
    if (isset($shelters[$shelterId])) {
        sendResponse(200, 'success', 'Detail posko pengungsian ditemukan.', $shelters[$shelterId]);
    } else {
        sendResponse(404, 'error', "Posko pengungsian dengan ID '{$matches[1]}' tidak ditemukan.", null, [
            [
                'field' => 'id',
                'issue' => 'Resource shelter tidak terdaftar.'
            ]
        ]);
    }
}

// ------------------------------------------
// RESOURCE 5: REPORTS (/api/v1/reports)
// ------------------------------------------

// 6.1 GET /api/v1/reports — List Crowdsourced Community Flood Reports
if ($method === 'GET' && $requestUri === '/api/v1/reports') {
    $reportList = array_values($reports);
    sendResponse(200, 'success', 'Daftar laporan banjir masyarakat berhasil dimuat.', $reportList, null, [
        'total_reports' => count($reportList)
    ]);
}

// 6.2 POST /api/v1/reports — Submit Citizen Flood Report
if ($method === 'POST' && $requestUri === '/api/v1/reports') {
    $payload = getJsonPayload();
    $errors = [];

    if (empty($payload['reporter_name'])) {
        $errors[] = ['field' => 'reporter_name', 'issue' => 'Nama pelapor wajib diisi.'];
    }
    if (empty($payload['location_name'])) {
        $errors[] = ['field' => 'location_name', 'issue' => 'Nama lokasi genangan banjir wajib diisi.'];
    }
    if (!isset($payload['inundation_height_cm']) || !is_numeric($payload['inundation_height_cm']) || $payload['inundation_height_cm'] < 0) {
        $errors[] = ['field' => 'inundation_height_cm', 'issue' => 'Ketinggian genangan air (inundation_height_cm) wajib numerik >= 0.'];
    }

    if (!empty($errors)) {
        sendResponse(422, 'error', 'Validasi formulir laporan banjir warga gagal.', null, $errors);
    }

    $repId = 'REP-' . date('Ymd') . '-' . str_pad((string)rand(10, 99), 2, '0', STR_PAD_LEFT);
    $newReport = [
        'report_id' => $repId,
        'reporter_name' => trim($payload['reporter_name']),
        'reporter_phone' => isset($payload['reporter_phone']) ? trim($payload['reporter_phone']) : null,
        'location_name' => trim($payload['location_name']),
        'inundation_height_cm' => (float)$payload['inundation_height_cm'],
        'notes' => isset($payload['notes']) ? trim($payload['notes']) : '',
        'status' => 'pending_verification',
        'created_at' => date('c')
    ];

    $reports[$repId] = $newReport;
    sendResponse(201, 'success', 'Laporan genangan banjir berhasil dikirim dan menunggu verifikasi petugas.', $newReport);
}

// ------------------------------------------
// FALLBACK ROUTE: 404 NOT FOUND
// ------------------------------------------
sendResponse(404, 'error', "Endpoint '{$method} {$requestUri}' tidak terdaftar pada layanan RiverVigil API.", null, [
    [
        'field' => 'path',
        'issue' => 'Rute API tidak ditemukan atau method HTTP tidak diizinkan.'
    ]
]);
