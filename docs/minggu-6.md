# Laporan Tugas 6 — Endpoint Collection yang Dapat Dicari (RiverVigil API)

## Informasi Tugas

- **Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
- **Modul:** Tugas 6 — Endpoint Collection yang Dapat Dicari (Search, Sorting, dan Pagination)  
- **Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
- **Resource Utama Terpilih:** `Station` (`stations`) — Pos Pantau Muka Air Sungai & Banjir  
- **Versi API:** `v1.3.0`  
- **Base URL:** `http://127.0.0.1:8000/api` dan `http://127.0.0.1:8000/api/v1`  
- **Repositori GitHub:** [https://github.com/202234010-tech/rivervigil-api](https://github.com/202234010-tech/rivervigil-api)  
- **Tautan Commit Terkait:** [Commit `de567a0`](https://github.com/202234010-tech/rivervigil-api/commit/de567a0)  
- **Postman Collection:** `docs/postman/week-06-pagination-filtering-sorting.postman_collection.json`  

---

## 1. Tujuan

Tujuan dari pelaksanaan Tugas 6 ini adalah menerapkan hasil pembelajaran Praktikum 6 pada resource utama proyek semester RiverVigil API (`Station`):
1. **Mengimplementasikan Paginasi Terstandar (*Standard Pagination*):** Membatasi volume data yang ditransfer pada endpoint collection pos pantau sungai dengan parameter `page` dan `per_page` (maksimum 50 rekaman per request), guna mengoptimalkan latensi jaringan dan konsumsi memori client.
2. **Menyediakan Pencarian Multi-Kolom (*Multi-field Search*):** Memungkinkan pencarian fleksibel pada minimal dua atribut relevan sekaligus (`name` dan `location`, serta kode `id`) menggunakan klausa pengelompokan `where` dengan operator `LIKE %...%`.
3. **Menerapkan Pengurutan Terkontrol (*Allowlist Sorting*):** Membatasi atribut yang dapat diurutkan hanya pada field publik yang diizinkan (`id`, `name`, `river_basin`, `location`, `current_level_cm`, `flow_rate_m3s`, `alert_status`, `created_at`) dengan arah `asc` atau `desc`, mencegah SQL Injection dan kebocoran struktur data.
4. **Validasi Query Parameter yang Ketat:** Memvalidasi seluruh masukan kueri sebelum query database dieksekusi, menolak parameter invalid dengan status semantik `422 Unprocessable Content` dan envelope error yang terstruktur.
5. **Mempertahankan Query String pada Navigasi Hipermedia:** Menghadirkan tautan navigasi HATEOAS (`links.first`, `links.last`, `links.prev`, `links.next`) yang mempertahankan seluruh parameter pencarian dan pengurutan melalui method chaining `->withQueryString()`.
6. **Menjaga Kompatibilitas dengan API Contract:** Memastikan metadata paginasi dan pembungkus data (`data`, `links`, `meta`) selaras 100% dengan **Spesifikasi API Contract Minggu 3** (`docs/api-contract.md`).

---

## 2. Endpoint dan Query Contract

Endpoint collection stasiun pos pantau tersedia pada path:
- `GET /api/stations`
- `GET /api/v1/stations` (versi resmi RiverVigil API)

### 2.1 Spesifikasi Query Parameters

| Query Parameter | Tipe Data | Wajib / Opsional | Default | Batasan & Aturan Validasi |
|---|---|---|---|---|
| `search` | String | Opsional | `null` | Maksimal 100 karakter. Mencocokkan teks pada `name`, `location`, atau `id` (`LIKE %...%`). |
| `sort` | String | Opsional | `id` | Hanya menerima field allowlist: `id`, `name`, `river_basin`, `location`, `current_level_cm`, `flow_rate_m3s`, `alert_status`, `created_at`. |
| `direction` | String | Opsional | `asc` | Hanya menerima `asc` (menaik) atau `desc` (menurun). |
| `page` | Integer | Opsional | `1` | Minimal bernilai 1 (`min:1`). |
| `per_page` | Integer | Opsional | `10` | Rentang nilai 1 hingga 50 (`min:1`, `max:50`). |
| `limit` | Integer | Opsional | `10` | Alias backward-compatible untuk `per_page` (rentang 1–50). |
| `river_basin` | String | Opsional | `null` | Filter exact match DAS (misal: `ciliwung`, `bekasi`, `cisadane`, `pesanggrahan`, `angke`). |
| `status` | String | Opsional | `null` | Filter exact match status siaga: `normal`, `siaga_3`, `siaga_2`, `siaga_1`. |

---

## 3. Daftar Field Search dan Sort Allowlist

### 3.1 Field Pencarian (*Search Fields*)
Klausa pencarian pada `StationController` mencocokkan input pengguna ke **3 field relevan** pos pantau sungai secara bersamaan:
1. **`name`** (Nama Stasiun/Pos Pantau): Pencarian berdasarkan nama bangunan air, misalnya `Bendung Katulampa`, `Pos Pantau Kali Bekasi`, `Pintu Air 10 Cisadane`.
2. **`location`** (Lokasi Geografis): Pencarian berdasarkan wilayah pos, misalnya `Jakarta Selatan`, `Bogor`, `Kota Bekasi`, `Tangerang`.
3. **`id`** (Kode Registrasi Unik): Pencarian berdasarkan kode pos pantau BMKG/PUSAIR, misalnya `STA-KATULAMPA`, `STA-BEKASI`.

Implementasi logika pencarian pada controller:
```php
if (! empty($validated['search'])) {
    $search = $validated['search'];
    $query->where(function ($builder) use ($search) {
        $builder
            ->where('name', 'like', "%{$search}%")
            ->orWhere('location', 'like', "%{$search}%")
            ->orWhere('id', 'like', "%{$search}%");
    });
}
```

### 3.2 Field Allowlist Pengurutan (*Sort Allowlist*)
Pengurutan dibatasi secara ketat melalui aturan validasi `Rule::in(...)`:
- `id`: Kode identitas unik stasiun pos pantau.
- `name`: Nama stasiun pos pantau.
- `river_basin`: Nama Wilayah Sungai / DAS.
- `location`: Lokasi fisik pos pantau.
- `current_level_cm`: Ketinggian muka air sungai terkini (dalam centimeter).
- `flow_rate_m3s`: Debit aliran air sungai terkini (dalam meter kubik per detik).
- `alert_status`: Status siaga bencana (`normal`, `siaga_3`, `siaga_2`, `siaga_1`).
- `created_at`: Waktu pendaftaran stasiun pos pantau ke sistem.

---

## 4. Validation Rules

Validasi parameter kueri dijalankan menggunakan `Validator::make($request->query(), ...)` sebelum query Eloquent dirancang:

```php
$validator = Validator::make($request->query(), [
    'search' => ['sometimes', 'nullable', 'string', 'max:100'],
    'sort' => ['sometimes', Rule::in([
        'id', 'name', 'river_basin', 'location', 
        'current_level_cm', 'flow_rate_m3s', 'alert_status', 'created_at'
    ])],
    'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
    'page' => ['sometimes', 'integer', 'min:1'],
    'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
    'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
    'river_basin' => ['sometimes', 'nullable', 'string', 'max:100'],
    'status' => ['sometimes', Rule::in(['normal', 'siaga_3', 'siaga_2', 'siaga_1'])],
]);
```

Jika salah satu parameter melanggar aturan di atas, sistem segera menghentikan proses query dan mengembalikan respons `422 Unprocessable Content` dengan struktur error standar RiverVigil API:
```json
{
  "status": "error",
  "message": "Validasi query parameter gagal. Periksa kembali parameter yang dikirimkan.",
  "errors": [
    {
      "field": "nama_parameter",
      "issue": "Pesan deskripsi kegagalan validasi."
    }
  ]
}
```

---

## 5. Bukti Pengujian (Empirical Evidence)

Pengujian berikut dieksekusi langsung terhadap server lokal aktif (`http://127.0.0.1:8000`) dengan data aktual sebanyak 5 stasiun pos pantau unik:

### 5.1 Bukti Pagination dan Metadata

#### A. Default Pagination (`GET /api/v1/stations`)
- **Request:**
  ```http
  GET /api/v1/stations HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
- **HTTP Status:** `200 OK`
- **Actual Response Body:**
  ```json
  {
    "status": "success",
    "message": "Daftar stasiun pos pantau berhasil diambil.",
    "data": [
      {
        "id": "STA-ANGKE",
        "name": "Pos Pantau Cengkareng Drain",
        "river_basin": "angke",
        "location": "Jakarta Barat, DKI Jakarta",
        "current_level_cm": 65,
        "flow_rate_m3s": 18.4,
        "alert_status": "normal",
        "thresholds_cm": {
          "normal": 70,
          "siaga_3": 110,
          "siaga_2": 150,
          "siaga_1": 190
        },
        "updated_at": "2026-10-27T20:20:32.000000Z"
      },
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
        "updated_at": "2026-10-27T20:20:32.000000Z"
      },
      {
        "id": "STA-CISADANE",
        "name": "Pintu Air 10 Cisadane",
        "river_basin": "cisadane",
        "location": "Tangerang, Banten",
        "current_level_cm": 175,
        "flow_rate_m3s": 88,
        "alert_status": "siaga_2",
        "thresholds_cm": {
          "normal": 120,
          "siaga_3": 160,
          "siaga_2": 200,
          "siaga_1": 250
        },
        "updated_at": "2026-10-27T20:20:32.000000Z"
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
        "updated_at": "2026-10-27T20:20:32.000000Z"
      },
      {
        "id": "STA-PESANG",
        "name": "Pos Pantau Pesanggrahan",
        "river_basin": "pesanggrahan",
        "location": "Jakarta Selatan, DKI Jakarta",
        "current_level_cm": 110,
        "flow_rate_m3s": 45,
        "alert_status": "siaga_3",
        "thresholds_cm": {
          "normal": 90,
          "siaga_3": 130,
          "siaga_2": 170,
          "siaga_1": 220
        },
        "updated_at": "2026-10-27T20:20:32.000000Z"
      }
    ],
    "links": {
      "first": "http://127.0.0.1:8000/api/v1/stations?page=1",
      "last": "http://127.0.0.1:8000/api/v1/stations?page=1",
      "prev": null,
      "next": null
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 10,
      "to": 5,
      "total": 5,
      "page": 1,
      "limit": 10,
      "total_records": 5
    }
  }
  ```

#### B. Custom Pagination Page 1 (`GET /api/v1/stations?per_page=2&page=1`)
- **HTTP Status:** `200 OK`
- **Data Ditampilkan:** 2 stasiun pertama (`STA-ANGKE` dan `STA-BEKASI`).
- **Metadata:** `"current_page": 1`, `"per_page": 2`, `"last_page": 3`, `"total": 5`.
- **Navigasi Links:** `"next": "http://127.0.0.1:8000/api/v1/stations?per_page=2&page=2"`, `"prev": null`.

#### C. Custom Pagination Page 2 (`GET /api/v1/stations?per_page=2&page=2`)
- **HTTP Status:** `200 OK`
- **Data Ditampilkan:** 2 stasiun berikutnya (`STA-CISADANE` dan `STA-KATULAMPA`).
- **Metadata:** `"current_page": 2`, `"from": 3`, `"to": 4`, `"total": 5`.
- **Navigasi Links:** `"prev": "http://127.0.0.1:8000/api/v1/stations?per_page=2&page=1"`, `"next": "http://127.0.0.1:8000/api/v1/stations?per_page=2&page=3"`.

---

### 5.2 Bukti Search pada Dua Field Berbeda

#### A. Pencarian pada Field Pertama (`name`) — Kata Kunci: "Bendung"
- **Request:**
  ```http
  GET /api/v1/stations?search=Bendung HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
- **HTTP Status:** `200 OK`
- **Actual Response:**
  ```json
  {
    "status": "success",
    "message": "Daftar stasiun pos pantau berhasil diambil.",
    "data": [
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
        "updated_at": "2026-10-27T20:20:32.000000Z"
      }
    ],
    "links": {
      "first": "http://127.0.0.1:8000/api/v1/stations?search=Bendung&page=1",
      "last": "http://127.0.0.1:8000/api/v1/stations?search=Bendung&page=1",
      "prev": null,
      "next": null
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 10,
      "to": 1,
      "total": 1,
      "page": 1,
      "limit": 10,
      "total_records": 1
    }
  }
  ```

#### B. Pencarian pada Field Kedua (`location`) — Kata Kunci: "Jakarta"
- **Request:**
  ```http
  GET /api/v1/stations?search=Jakarta HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
- **HTTP Status:** `200 OK`
- **Hasil:** Berhasil mengembalikan 2 stasiun yang berlokasi di DKI Jakarta (`STA-ANGKE` di Jakarta Barat dan `STA-PESANG` di Jakarta Selatan), dengan `"total_records": 2`.

#### C. Pencarian Tanpa Hasil Cocok (`search=tidak-ada-stasiun`)
- **Request:** `GET /api/v1/stations?search=tidak-ada-stasiun`
- **HTTP Status:** `200 OK` (Bukan 404)
- **Hasil:** `"data": []`, `"total": 0`, `"total_records": 0`.

---

### 5.3 Bukti Sorting (Ascending dan Descending)

#### A. Sorting Ascending (`sort=name&direction=asc&per_page=10`)
- **Request:** `GET /api/v1/stations?sort=name&direction=asc&per_page=10`
- **HTTP Status:** `200 OK`
- **Urutan Data Aktual (A ke Z):**
  1. `STA-KATULAMPA` — *"Bendung Katulampa"* (B)
  2. `STA-CISADANE` — *"Pintu Air 10 Cisadane"* (Pi)
  3. `STA-ANGKE` — *"Pos Pantau Cengkareng Drain"* (Po... C)
  4. `STA-BEKASI` — *"Pos Pantau Kali Bekasi"* (Po... K)
  5. `STA-PESANG` — *"Pos Pantau Pesanggrahan"* (Po... P)

#### B. Sorting Descending (`sort=name&direction=desc&per_page=10`)
- **Request:** `GET /api/v1/stations?sort=name&direction=desc&per_page=10`
- **HTTP Status:** `200 OK`
- **Urutan Data Aktual (Z ke A):**
  1. `STA-PESANG` — *"Pos Pantau Pesanggrahan"* (Po... P)
  2. `STA-BEKASI` — *"Pos Pantau Kali Bekasi"* (Po... K)
  3. `STA-ANGKE` — *"Pos Pantau Cengkareng Drain"* (Po... C)
  4. `STA-CISADANE` — *"Pintu Air 10 Cisadane"* (Pi)
  5. `STA-KATULAMPA` — *"Bendung Katulampa"* (B)

#### C. Kombinasi Pagination + Search + Sorting
- **Request:**
  ```http
  GET /api/v1/stations?search=Pos&sort=name&direction=asc&per_page=2&page=1 HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
- **HTTP Status:** `200 OK`
- **Hasil:** Mengembalikan 2 stasiun pertama dari total 3 stasiun yang memuat kata *"Pos"*, diurutkan ascending:
  1. `STA-ANGKE` (*Pos Pantau Cengkareng Drain*)
  2. `STA-BEKASI` (*Pos Pantau Kali Bekasi*)
- **Preservasi Query String pada Links:**
  ```json
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/stations?search=Pos&sort=name&direction=asc&per_page=2&page=1",
    "last": "http://127.0.0.1:8000/api/v1/stations?search=Pos&sort=name&direction=asc&per_page=2&page=2",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/v1/stations?search=Pos&sort=name&direction=asc&per_page=2&page=2"
  }
  ```

---

### 5.4 Bukti Error 422 (Validasi Query Parameter Gagal)

#### A. Invalid Sort Allowlist (`sort=password`)
- **Request:**
  ```http
  GET /api/v1/stations?sort=password HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
- **HTTP Status:** `422 Unprocessable Content`
- **Actual Response Body:**
  ```json
  {
    "status": "error",
    "message": "Validasi query parameter gagal. Periksa kembali parameter yang dikirimkan.",
    "errors": [
      {
        "field": "sort",
        "issue": "The selected sort is invalid."
      }
    ]
  }
  ```

#### B. Invalid Per Page Exceeds Limit (`per_page=100`)
- **Request:**
  ```http
  GET /api/v1/stations?per_page=100 HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
- **HTTP Status:** `422 Unprocessable Content`
- **Actual Response Body:**
  ```json
  {
    "status": "error",
    "message": "Validasi query parameter gagal. Periksa kembali parameter yang dikirimkan.",
    "errors": [
      {
        "field": "per_page",
        "issue": "The per page field must not be greater than 50."
      }
    ]
  }
  ```

---

## 6. Perbandingan Actual Response dengan API Contract

Berikut adalah analisis perbandingan kepatuhan implementasi aktual terhadap dokumen **API Contract Minggu 3** (`docs/api-contract.md` Bagian 3.1):

| Aspek Kontrak | Spesifikasi API Contract (Minggu 3) | Implementasi Aktual Minggu 6 | Evaluasi & Status Kepatuhan |
|---|---|---|---|
| **Base Envelope** | `{ status, message, data, meta }` | `{ status, message, data, links, meta }` | **100% Sesuai & Diperkaya.** Mempertahankan standard envelope `status` dan `message`, serta menambahkan navigasi `links` HATEOAS. |
| **Data Array** | Array of object `StationResource` | Array of object `StationResource` | **100% Sesuai.** Seluruh atribut (`id`, `name`, `river_basin`, `location`, `current_level_cm`, `flow_rate_m3s`, `alert_status`, `thresholds_cm`, `updated_at`) identik. |
| **Filter Eksisting** | `river_basin`, `status` | `river_basin`, `status` | **100% Sesuai.** Filter wilayah sungai dan status siaga tetap berfungsi sempurna dan dapat dikombinasikan. |
| **Parameter Paginasi** | `page`, `limit` (default: 10) | `page`, `per_page`, dan alias `limit` | **100% Sesuai & Kompatibel.** Mendukung parameter standar Laravel `per_page` sekaligus mendukung parameter kontrak `limit`. |
| **Fitur Pencarian** | Belum didefinisikan secara rinci pada Minggu 3 | Parameter `search` (mencocokkan `name`, `location`, `id`) | **Peningkatan Fungsional.** Menambahkan kapabilitas pencarian parsial multi-field tanpa merusak kontrak sebelumnya. |
| **Fitur Pengurutan** | Belum didefinisikan secara rinci pada Minggu 3 | Parameter `sort` (allowlist) dan `direction` (`asc`/`desc`) | **Peningkatan Fungsional.** Mendukung pengurutan terkontrol pada 8 kolom publik. |
| **Metadata Paginasi** | `{ page, limit, total_records }` | `{ current_page, from, last_page, per_page, to, total, page, limit, total_records }` | **100% Sesuai & Kompatibel.** Menyajikan key kontrak lama (`page`, `limit`, `total_records`) dan key standar Laravel (`current_page`, `last_page`, dll). |
| **Format Validasi 422**| `{ status: "error", message: "...", errors: [{field, issue}] }` | `{ status: "error", message: "...", errors: [{field, issue}] }` | **100% Sesuai.** Menggunakan struktur error array-of-objects sesuai standar penanganan error RiverVigil API. |

---

## 7. Kesimpulan

1. Endpoint collection resource utama `Station` pada RiverVigil API telah berhasil ditingkatkan dari yang sebelumnya bersifat non-paginated menjadi collection endpoint yang tangguh, terpaginasi penuh, dan mendukung pencarian serta pengurutan dinamis.
2. Keamanan aplikasi diperkuat melalui penerapan **allowlist sorting** dan validasi parameter masukan yang ketat, mencegah serangan SQL Injection, crash database (500), maupun kebocoran nama kolom internal.
3. Arsitektur respons tetap konsisten dengan prinsip **Standard Uniform Envelope** RiverVigil API, mempertahankan kompatibilitas mundur (*backward compatibility*) dengan klien dan test suite yang ada.
4. Seluruh fungsionalitas telah divalidasi secara komprehensif melalui 18 unit/feature automated tests (140 assertions lulus 100%) dan koleksi pengujian Postman yang dapat dijalankan ulang secara mandiri.

---

## 8. Referensi

1. [Laravel 12.x Documentation — Database Pagination](https://laravel.com/docs/12.x/pagination)
2. [Laravel 12.x Documentation — Eloquent API Resources](https://laravel.com/docs/12.x/eloquent-resources)
3. [Laravel 12.x Documentation — Validation (Form Requests & Rules)](https://laravel.com/docs/12.x/validation)
4. [Laravel 12.x Documentation — Database Query Builder](https://laravel.com/docs/12.x/queries)
5. [IETF RFC 9110 — HTTP Semantics (Status Codes 200, 422)](https://www.rfc-editor.org/rfc/rfc9110)
6. Modul Pembelajaran Manajemen Layanan Web 2026: *Tugas 6 — Endpoint Collection yang Dapat Dicari*.

---

## 9. Deklarasi Penggunaan AI

- **Alat Bantu AI:** Antigravity AI Assistant (Google DeepMind).
- **Tujuan Penggunaan:** Membantu perancangan arsitektur query builder, refactoring controller `StationController@index`, penambahan automated feature test pada `StationApiTest`, penyusunan Postman collection `week-06-pagination-filtering-sorting.postman_collection.json`, dan pencatatan bukti empiris pada dokumentasi `docs/minggu-6.md`.
- **Ringkasan Prompt:** Menginstruksikan implementasi pagination dinamis dengan batas maksimal 50, pencarian pada minimal 2 field relevan (`name` dan `location`), pengurutan allowlist dengan arah `asc`/`desc`, validasi query parameter 422, dan pengujian empiris pada server aktif.
- **Bagian yang Dipakai / Diubah:**
  - `app/Http/Controllers/StationController.php`: Logika method `index()` diganti dengan pipeline validasi `Validator::make`, pencarian multi-kolom `where` group, filter DAS dan status, pengurutan allowlist `orderBy`, dan paginasi `paginate()->withQueryString()`.
  - `database/seeders/StationSeeder.php`: Penambahan data stasiun pos pantau realistis dari 2 menjadi 5 pos pantau dengan DAS berbeda (`cisadane`, `pesanggrahan`, `angke`) untuk memungkinkan pengujian paginasi multi-halaman dan pencarian multi-lokasi.
  - `tests/Feature/StationApiTest.php`: Penambahan 6 metode feature test baru untuk menguji seluruh skenario paginasi, pencarian, sorting, dan validasi 422.
  - `docs/postman/week-06-pagination-filtering-sorting.postman_collection.json`: Pembuatan koleksi Postman lengkap dengan 11 request dan test scripts assertion.
- **Cara Memeriksa Hasil:**
  - Sintaks linting dengan `php -l`.
  - Eksekusi automated test suite melalui `php artisan test --filter=StationApiTest` (18 tests passed, 140 assertions) dan `php artisan test` (30 tests passed, 188 assertions).
  - Eksekusi request live cURL ke server lokal aktif (`http://127.0.0.1:8000/api/v1/stations`) untuk memverifikasi HTTP status codes (200 OK, 422 Unprocessable Content), struktur payload, dan query parameter links.
