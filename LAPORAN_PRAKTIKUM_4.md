# Laporan Praktikum 4 — Endpoint Read-only dengan Laravel

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Modul:** Praktikum 4 — Endpoint Read-only dengan Laravel  
**Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
**Versi API:** `v1.1.0`  
**Base URL:** `http://127.0.0.1:8000/api` dan `http://127.0.0.1:8000/api/v1`  
**Resource Terpilih:** `Station` (Pos Pantau Ketinggian Sungai & Banjir)  
**Waktu Praktikum:** 120 menit  

---

## 1. Ringkasan & Tujuan Praktikum

Setelah menyelesaikan tahapan praktikum ini, mahasiswa berhasil:
1. **Menerjemahkan API Contract menjadi Implementasi Laravel:** Mengonversi kontrak RESTful `Station` dari Minggu 3 menjadi dua endpoint yang beroperasi penuh pada framework Laravel 12/13.
2. **Merancang Skema Basis Data dengan Migration:** Menulis definisi tabel `stations` secara deklaratif menggunakan Laravel Migration dengan primary key string non-incrementing (`STA-KATULAMPA`), field numerik, dan kolom JSON `thresholds_cm`.
3. **Membangun Model Eloquent:** Menyiapkan model `Station` dengan konfigurasi keyType string dan attribute casting tanpa menambahkan properti mass assignment `$fillable` yang belum dibutuhkan pada endpoint read-only.
4. **Menyiapkan Data Development dengan Seeder:** Menyuntikkan minimal dua rekaman pengujian realistis ke database development SQLite melalui `StationSeeder`.
5. **Mengembangkan Controller & Response Envelope:** Membangun `StationController` yang mengimplementasikan method `index()` dan `show()` dengan data mapping terstruktur, pembungkusan wrapper sukses (`status`, `message`, `data`, `meta`), dan penanganan error `404 Not Found` terstandar.
6. **Mendaftarkan Routing API:** Mengaktifkan API routing Laravel (`php artisan install:api`) serta mendaftarkan rute pada `routes/api.php`.
7. **Memverifikasi Kepatuhan Kontrak:** Menguji respons `200 OK` (list & detail) serta `404 Not Found` menggunakan Postman dan Feature Test otomatis.
8. **Menerapkan Git Version Control yang Bersih:** Memastikan hanya berkas kode relevan Minggu 4 yang di-stage dan di-commit tanpa menyertakan berkas kredensial (`.env`) atau basis data lokal.

---

## 2. Struktur Komponen yang Dihasilkan

| Komponen | Path File | Peran & Deskripsi |
|---|---|---|
| **Model** | `app/Models/Station.php` | Model Eloquent untuk entitas pos pantau sungai dengan casting JSON & float. |
| **Migration** | `database/migrations/*_create_stations_table.php` | Blueprint struktur tabel `stations` dengan string primary key dan kolom parameter air. |
| **Seeder** | `database/seeders/StationSeeder.php` | Penyedia 2 record dummy (`STA-KATULAMPA` dan `STA-BEKASI`) untuk tahap development. |
| **Controller** | `app/Http/Controllers/StationController.php` | Controller API dengan method `index()` (daftar & filter) dan `show()` (detail & error handling 404). |
| **Route** | `routes/api.php` | Definisi rute `GET /api/stations` dan `GET /api/stations/{station}` beserta alias `api/v1`. |
| **Dokumentasi** | `docs/minggu-4-praktikum.md` | Catatan verifikasi praktikum, tabel perbandingan kontrak, dan bukti pengujian. |
| **Automated Test** | `tests/Feature/StationApiTest.php` | Test suite 5 skenario pengujian dengan 38 assertions (semua lolos). |

---

## 3. Implementasi Kode Utama

### 3.1 Migration (`database/migrations/..._create_stations_table.php`)
```php
public function up(): void
{
    Schema::create('stations', function (Blueprint $table) {
        $table->string('id', 50)->primary();
        $table->string('name', 150);
        $table->string('river_basin', 100);
        $table->string('location', 200);
        $table->double('current_level_cm');
        $table->double('flow_rate_m3s');
        $table->string('alert_status', 50)->default('normal');
        $table->json('thresholds_cm');
        $table->timestamps();
    });
}
```

### 3.2 Model (`app/Models/Station.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'thresholds_cm' => 'array',
        'current_level_cm' => 'float',
        'flow_rate_m3s' => 'float',
    ];
}
```

### 3.3 Seeder (`database/seeders/StationSeeder.php`)
```php
public function run(): void
{
    DB::table('stations')->insert([
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
    ]);
}
```

### 3.4 Controller (`app/Http/Controllers/StationController.php`)
```php
public function index(Request $request): JsonResponse
{
    $query = Station::query();

    if ($request->filled('river_basin')) {
        $query->where('river_basin', $request->query('river_basin'));
    }

    if ($request->filled('status')) {
        $query->where('alert_status', $request->query('status'));
    }

    $totalRecords = (clone $query)->count();

    $stations = $query
        ->orderBy('id')
        ->get()
        ->map(fn (Station $station): array => $this->stationData($station));

    return response()->json([
        'status' => 'success',
        'message' => 'Daftar stasiun pos pantau berhasil diambil.',
        'data' => $stations,
        'meta' => [
            'page' => (int) $request->query('page', 1),
            'limit' => (int) $request->query('limit', 10),
            'total_records' => $totalRecords,
        ],
    ]);
}

public function show(string $station): JsonResponse
{
    $stationModel = Station::find($station);

    if ($stationModel === null) {
        return response()->json([
            'status' => 'error',
            'message' => "Stasiun pos pantau dengan ID '{$station}' tidak ditemukan.",
            'errors' => [
                [
                    'field' => 'id',
                    'issue' => 'Resource station tidak ada dalam basis data.',
                ],
            ],
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Detail stasiun pos pantau ditemukan.',
        'data' => $this->stationData($stationModel),
    ]);
}
```

### 3.5 Routes (`routes/api.php`)
```php
Route::get('/stations', [StationController::class, 'index']);
Route::get('/stations/{station}', [StationController::class, 'show']);

Route::prefix('v1')->group(function () {
    Route::get('/stations', [StationController::class, 'index']);
    Route::get('/stations/{station}', [StationController::class, 'show']);
});
```

---

## 4. Hasil Pengujian Endpoint

### 4.1 Uji 1: GET All Stations (`200 OK`)
- **Request:** `GET http://127.0.0.1:8000/api/stations`
- **Status:** `200 OK`
- **Response Snippet:**
```json
{
  "status": "success",
  "message": "Daftar stasiun pos pantau berhasil diambil.",
  "data": [
    {
      "id": "STA-BEKASI",
      "name": "Pos Pantau Kali Bekasi",
      "river_basin": "bekasi",
      "location": "Kota Bekasi, Jawa Barat",
      "current_level_cm": 90,
      "flow_rate_m3s": 32.5,
      "alert_status": "normal",
      "thresholds_cm": {
        "normal": 100,
        "siaga_3": 200,
        "siaga_2": 300,
        "siaga_1": 400
      },
      "updated_at": "2026-10-12T05:17:31.000000Z"
    },
    {
      "id": "STA-KATULAMPA",
      "name": "Bendung Katulampa",
      "river_basin": "ciliwung",
      "location": "Bogor, Jawa Barat",
      "current_level_cm": 120,
      "flow_rate_m3s": 54.2,
      "alert_status": "siaga_3",
      "thresholds_cm": {
        "normal": 80,
        "siaga_3": 120,
        "siaga_2": 150,
        "siaga_1": 200
      },
      "updated_at": "2026-10-12T05:17:31.000000Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total_records": 2
  }
}
```

### 4.2 Uji 2: GET Station Detail by ID (`200 OK`)
- **Request:** `GET http://127.0.0.1:8000/api/stations/STA-KATULAMPA`
- **Status:** `200 OK`
- **Response Snippet:**
```json
{
  "status": "success",
  "message": "Detail stasiun pos pantau ditemukan.",
  "data": {
    "id": "STA-KATULAMPA",
    "name": "Bendung Katulampa",
    "river_basin": "ciliwung",
    "location": "Bogor, Jawa Barat",
    "current_level_cm": 120,
    "flow_rate_m3s": 54.2,
    "alert_status": "siaga_3",
    "thresholds_cm": {
      "normal": 80,
      "siaga_3": 120,
      "siaga_2": 150,
      "siaga_1": 200
    },
    "updated_at": "2026-10-12T05:17:31.000000Z"
  }
}
```

### 4.3 Uji 3: GET Station Not Found (`404 Not Found`)
- **Request:** `GET http://127.0.0.1:8000/api/stations/STA-UNKNOWN`
- **Status:** `404 Not Found`
- **Response Snippet:**
```json
{
  "status": "error",
  "message": "Stasiun pos pantau dengan ID 'STA-UNKNOWN' tidak ditemukan.",
  "errors": [
    {
      "field": "id",
      "issue": "Resource station tidak ada dalam basis data."
    }
  ]
}
```

---

## 5. Jawaban Refleksi Praktikum

1. **Bagian Laravel mana yang menentukan URL endpoint?**  
   Berkas `routes/api.php` menentukan seluruh URL endpoint REST API beserta metode HTTP (`GET`, `POST`, dll.). Seluruh route yang didaftarkan di sini secara default menerima prefix `/api` dan middleware kelompok `api`.
2. **Bagian mana yang menentukan struktur database?**  
   File migrasi pada direktori `database/migrations/` (misalnya `2026_10_12_051630_create_stations_table.php`). Di dalam method `up()`, `Blueprint` mendefinisikan skema tabel, tipe kolom, indeks primary key, dan batasan integritas basis data.
3. **Mengapa actual response harus dibandingkan dengan contract?**  
   Perbandingan ini memastikan bahwa server nyata mematuhi spesifikasi antarmuka yang disepakati (*contract compliance*). Menjamin bahwa aplikasi klien (mobile warga, dashboard web BPBD, telemetri sensor) dapat melakukan parsing data secara andal tanpa mengalami *runtime error* akibat perubahan struktur payload tak terduga.
4. **Mengapa collection kosong tidak dianggap 404?**  
   Collection merepresentasikan himpunan/wadah resource. Jika filter kueri tidak menghasilkan baris atau tabel kosong, wadah tersebut tetap valid dan mengembalikan array kosong `[]` dengan kode `200 OK`. Sebaliknya, `404 Not Found` hanya diperuntukkan bagi kasus ketika URI rute tidak terdaftar atau resource tunggal spesifik pada URI `/stations/{id}` memang tidak ada di basis data.
5. **Apa yang akan lebih baik ditangani oleh API Resource pada Minggu 5?**  
   API Resource (`JsonResource`) memisahkan tanggung jawab serialisasi data dari controller. Fitur ini menangani enkapsulasi wrapper data secara otomatis, pemformatan tanggal ISO secara sentral, masking atribut rahasia, serta kondisionalisasi relasi (*whenLoaded*) secara elegan dan terorganisir.
