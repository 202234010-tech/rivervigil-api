# Praktikum Minggu 4 — Read-only Laravel API

## Resource Station (RiverVigil API)

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Modul:** Praktikum 4 — Endpoint Read-only dengan Laravel  
**Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
**Resource Domain:** `Station` (`stations`)  
**Base URL:** `http://127.0.0.1:8000/api` (dan alias `http://127.0.0.1:8000/api/v1`)  

---

## Endpoint
- `GET /api/stations` (dan `GET /api/v1/stations`): Mengambil daftar pos pantau sungai dengan parameter filter opsional (`river_basin`, `status`, `page`, `limit`).
- `GET /api/stations/{station}` (dan `GET /api/v1/stations/{station}`): Mengambil rincian data tunggal pos pantau berdasarkan ID (`STA-KATULAMPA`, `STA-BEKASI`).

---

## Verification
- **200 list (Daftar Stasiun):** Berhasil (`HTTP/1.1 200 OK`)
  - URL: `GET http://127.0.0.1:8000/api/stations`
  - Body membungkus array objek stasiun dalam atribut `data` dan metadata pagination dalam atribut `meta`.
  - Filter pengujian: `GET http://127.0.0.1:8000/api/stations?river_basin=ciliwung` berhasil mengembalikan hanya 1 rekaman yang sesuai.
- **200 detail (Rincian Stasiun):** Berhasil (`HTTP/1.1 200 OK`)
  - URL: `GET http://127.0.0.1:8000/api/stations/STA-KATULAMPA`
  - Body membungkus satu objek stasiun tunggal pada atribut `data`.
  - Atribut `thresholds_cm` didecode secara benar sebagai JSON object, bukan string.
- **404 not found (Stasiun Tidak Ditemukan):** Berhasil (`HTTP/1.1 404 Not Found`)
  - URL: `GET http://127.0.0.1:8000/api/stations/STA-UNKNOWN`
  - Mengembalikan standar error envelope dengan `status: "error"`, pesan deskriptif, dan array `errors` yang memuat field dan issue.

---

## Contract Comparison
Perbandingan antara **API Contract Minggu 3** (`docs/api-contract.md`) dengan **Actual Response Laravel Minggu 4**:

| Elemen Kontrak | Spesifikasi Contract Minggu 3 | Actual Response Laravel Minggu 4 | Kesesuaian |
|---|---|---|---|
| **HTTP Method & Path** | `GET /api/v1/stations`<br>`GET /api/v1/stations/{id}` | `GET /api/stations` & `GET /api/v1/stations`<br>`GET /api/stations/{id}` & `GET /api/v1/stations/{id}` | **Cocok 100%** (Mendukung path standar `/api/stations` dan alias versi `/api/v1/stations`) |
| **Status Code List** | `200 OK` | `200 OK` | **Cocok 100%** |
| **Status Code Detail** | `200 OK` | `200 OK` | **Cocok 100%** |
| **Status Code Not Found** | `404 Not Found` | `404 Not Found` | **Cocok 100%** |
| **Response Envelope (Success)** | `{"status": "success", "message": "...", "data": [...], "meta": {...}}` | `{"status": "success", "message": "...", "data": [...], "meta": {...}}` | **Cocok 100%** |
| **Response Envelope (Error)** | `{"status": "error", "message": "...", "errors": [...]}` | `{"status": "error", "message": "...", "errors": [...]}` | **Cocok 100%** |
| **Field Tipe Data** | `id` (string), `name` (string), `current_level_cm` (float), `flow_rate_m3s` (float), `alert_status` (string), `thresholds_cm` (object/array) | `id` (string), `name` (string), `current_level_cm` (float), `flow_rate_m3s` (float), `alert_status` (string), `thresholds_cm` (object/array) | **Cocok 100%** |
| **Format Timestamp** | ISO-8601 (`toISOString()`) | ISO-8601 (contoh: `2026-10-12T05:17:31.000000Z`) | **Cocok 100%** |

### Analisis Kesesuaian
1. **Wrapper Data dan Transformasi:** Controller tidak langsung mengembalikan `Station::all()`, melainkan melalui fungsi transformasi `$this->stationData($station)`. Hal ini menjamin tipe data angka (`current_level_cm`, `flow_rate_m3s`) tetap bertipe float numerik dan atribut `thresholds_cm` selalu berupa associative array JSON murni.
2. **Kesesuaian Kode Status:** Skenario resource tidak ditemukan mengembalikan status `404 Not Found` eksplisit dengan body JSON terstruktur, bukan halaman HTML default Laravel.
3. **Filtering & Pagination:** Query parameter `river_basin` dan `status` ditangani secara dinamis dengan Eloquent query builder dan `meta.total_records` dihitung secara akurat.

---

## Evidence

### 1. Bukti Request Postman & Automated Newman
Pengujian dilakukan terhadap server lokal Laravel (`http://127.0.0.1:8000`) menggunakan Postman Collection `docs/postman/week-03-contract.postman_collection.json` dan suite unit/feature test:

1. **Request 1: GET All Stations with Query Params (200 OK)**
   - **Method:** `GET`
   - **URL:** `http://127.0.0.1:8000/api/v1/stations?river_basin=ciliwung&limit=10`
   - **Headers:** `Accept: application/json`
   - **Hasil:** Status `200 OK`, `status: "success"`, total 1 record (DAS Ciliwung - Katulampa).
   - **Assertions:**
     - `pm.response.to.have.status(200)`: **PASS**
     - `Response has success envelope`: **PASS**
     - `Filtering by river_basin is applied`: **PASS**

2. **Request 2: GET Station by ID - Success (200 OK)**
   - **Method:** `GET`
   - **URL:** `http://127.0.0.1:8000/api/v1/stations/STA-KATULAMPA`
   - **Headers:** `Accept: application/json`
   - **Hasil:** Status `200 OK`, `id: "STA-KATULAMPA"`, nama "Bendung Katulampa", `alert_status: "siaga_3"`.
   - **Assertions:**
     - `pm.response.to.have.status(200)`: **PASS**
     - `Station ID matches request`: **PASS**

3. **Request 3: GET Station by ID - Not Found (404 Not Found)**
   - **Method:** `GET`
   - **URL:** `http://127.0.0.1:8000/api/v1/stations/STA-UNKNOWN`
   - **Headers:** `Accept: application/json`
   - **Hasil:** Status `404 Not Found`, `status: "error"`, `errors: [{"field": "id", "issue": "Resource station tidak ada dalam basis data."}]`.
   - **Assertions:**
     - `pm.response.to.have.status(404)`: **PASS**
     - `Error envelope contains details`: **PASS**

### 2. Bukti Automated Test Laravel (Feature Test)
Suite pengujian otomatis ditulis pada `tests/Feature/StationApiTest.php` dan dieksekusi dengan `php artisan test`:
- `test_can_list_stations_with_200_ok`: **PASSED**
- `test_can_filter_stations_by_river_basin`: **PASSED**
- `test_can_get_single_station_detail_with_200_ok`: **PASSED**
- `test_returns_404_when_station_not_found`: **PASSED**
- `test_v1_prefixed_routes_work_identically`: **PASSED**
- **Ringkasan:** 5 passed, 38 assertions, 0 failed.

---

## Refleksi Praktikum

1. **Bagian Laravel mana yang menentukan URL endpoint?**  
   File `routes/api.php` mendefinisikan URI rute beserta HTTP Method yang didukung. Laravel secara otomatis menambahkan prefix `/api` ke seluruh rute yang dideklarasikan di berkas ini.
2. **Bagian mana yang menentukan struktur database?**  
   File migrasi basis data pada direktori `database/migrations/` (misalnya `..._create_stations_table.php`). Di dalam method `up()`, `Schema::create` mendefinisikan nama tabel, tipe kolom, indeks, default value, dan batasan integritas basis data.
3. **Mengapa actual response harus dibandingkan dengan contract?**  
   Agar implementasi backend menjamin kompatibilitas penuh (*contract compliance*) terhadap kebutuhan klien tanpa menciptakan *breaking changes*. Kontrak API adalah komitmen teknis yang disepakati antartim (frontend web, mobile app, perangkat IoT), sehingga respons aktual harus mematuhi skema payload, nama field, tipe data, dan status code yang dijanjikan.
4. **Mengapa collection kosong tidak dianggap 404?**  
   Karena endpoint collection (misalnya `GET /api/stations`) merepresentasikan sebuah kontainer data. Ketika query filter tidak menemukan rekaman atau tabel masih kosong, kontainer tersebut tetap ada secara valid dan mengembalikan representasi array kosong `[]` dengan status `200 OK`. Kode status `404 Not Found` hanya digunakan jika alamat URI tidak dikenali atau entitas tunggal spesifik pada `GET /api/stations/{id}` memang tidak ada di basis data.
5. **Apa yang akan lebih baik ditangani oleh API Resource pada Minggu 5?**  
   Eloquent API Resource (`JsonResource` dan `ResourceCollection`) memisahkan lapisan transformasi data dari controller ke kelas tersendiri (*Separation of Concerns*). Hal ini mempermudah pembungkusan (*envelope wrapping*), penyusunan metadata pagination secara seragam, penanganan field kondisional (*whenLoaded*), serta memastikan format keluaran API tetap konsisten di seluruh aplikasi tanpa redundansi kode pada method controller.
