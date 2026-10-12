# Laporan Tugas 5 — CRUD Resource Utama (RiverVigil API)

## Informasi Tugas

- **Mata Kuliah:** Manajemen Layanan Web / Web Service Management
- **Modul:** Tugas 5 — CRUD Resource Utama pada Project Semester
- **Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System
- **Resource Utama Terpilih:** `Station` (`stations`) — Pos Pantau Muka Air Sungai & Banjir
- **Base URL:** `http://127.0.0.1:8000/api` dan `http://127.0.0.1:8000/api/v1`
- **Repositori GitHub:** [https://github.com/202234010-tech/rivervigil-api](https://github.com/202234010-tech/rivervigil-api)
- **Tautan Commit Evidence:** *(Akan dicatat setelah commit git)*
- **Postman Collection:** `docs/postman/rivervigil-api.postman_collection.json`

---

## 1. Tujuan

1. **Menerapkan Hasil Pembelajaran Minggu 5 pada Project Semester:** Mentransformasikan resource inti pos pantau sungai (`Station`) dari yang sebelumnya hanya bertipe *read-only* (Minggu 4) menjadi siklus operasi **CRUD (Create, Read, Update, Delete)** yang utuh dan operasional.
2. **Menjamin Integritas Data melalui Form Request Validation:** Mengisolasi logika validasi input pada kelas `StoreStationRequest` dan `UpdateStationRequest`, mencegah payload rusak atau inkonsisten sebelum mencapai *controller action*.
3. **Menerapkan Transformasi Data Mandiri via Eloquent API Resource:** Menggantikan helper manual dengan `StationResource` untuk menjamin konsistensi *data envelope*, format timestamp ISO-8601, tipe data numerik float, serta parsing JSON batas ambang siaga (*thresholds*).
4. **Menyeragamkan Standard Error & Success Envelope:** Mengimplementasikan respons seragam yang mematuhi **API Contract Minggu 3** (`docs/api-contract.md`) dengan kode status HTTP semantik (`200 OK`, `201 Created`, `204 No Content`, `404 Not Found`, dan `422 Unprocessable Content`).
5. **Menyediakan Bukti Empiris yang Terverifikasi:** Menyajikan rekaman pengujian aktual dari server lokal aktif, suite pengujian fitur otomatis (PHPUnit), dan Postman Collection yang dapat diuji ulang secara independen.

---

## 2. Perubahan yang Dilakukan

Berikut rangkuman perombakan dan penambahan komponen dari arsitektur Minggu 4 ke arsitektur CRUD Minggu 5:

| Komponen | Status | File Path | Rincian Perubahan |
|---|---|---|---|
| **Model** | Diperbarui | `app/Models/Station.php` | Menambahkan proteksi *Mass Assignment* `$fillable = ['id', 'name', 'river_basin', 'location', 'current_level_cm', 'flow_rate_m3s', 'alert_status', 'thresholds_cm']`. |
| **API Resource** | Baru | `app/Http/Resources/StationResource.php` | Menghadirkan transformasi objek `Station` menjadi representasi JSON yang konsisten dengan casting tipe data float, ISO-8601, dan array batas siaga. |
| **Form Request Store** | Baru | `app/Http/Requests/StoreStationRequest.php` | Validasi pembuatan pos pantau baru: keunikan primary key `id`, validitas status siaga, numerik non-negatif, dan kelengkapan objek threshold. |
| **Form Request Update** | Baru | `app/Http/Requests/UpdateStationRequest.php` | Validasi pembaruan data secara parsial (`sometimes`) menggunakan HTTP method `PATCH` maupun pembaruan total via `PUT`. |
| **Controller** | Dirombak | `app/Http/Controllers/StationController.php` | Mengubah controller read-only menjadi full CRUD (`index`, `store`, `show`, `update`, `destroy`) dengan standard envelope dan response codes 200, 201, 204, 404, 422. |
| **Routing** | Diperbarui | `routes/api.php` | Mendaftarkan resource route penuh menggunakan `Route::apiResource('stations', StationController::class)` baik pada root `/api` maupun alias prefix `/v1`. |
| **Feature Tests** | Diperluas | `tests/Feature/StationApiTest.php` | Menambahkan skenario test case otomatis dari 5 pengujian menjadi 12 pengujian mencakup Create, Update, Validation Failure, Not Found, dan Delete. |
| **Postman Collection**| Diperbarui | `docs/postman/rivervigil-api.postman_collection.json` | Menambahkan request uji `PATCH`, `DELETE`, dan skenario kegagalan validasi `POST 422` lengkap dengan test script assertions. |

---

## 3. Endpoint & Hubungan dengan API Contract

Implementasi ini selaras 100% dengan **Spesifikasi API Contract Minggu 3** (`docs/api-contract.md` - Bagian 3.1 Resource Stations):

| HTTP Method | URI Path | Action Handler | HTTP Status Code | Deskripsi Sesuai Kontrak |
|---|---|---|:---:|---|
| `GET` | `/api/v1/stations` | `StationController@index` | `200 OK` | Mengambil katalog pos pantau sungai dengan filter query (`river_basin`, `status`) dan metadata pagination. |
| `POST` | `/api/v1/stations` | `StationController@store` | `201 Created` | Mendaftarkan stasiun pos pantau baru dengan payload terstruktur. |
| `GET` | `/api/v1/stations/{id}` | `StationController@show` | `200 OK` / `404` | Mengambil detail teknis pos pantau dan ambang batas siaga berdasarkan ID unik. |
| `PATCH` / `PUT` | `/api/v1/stations/{id}` | `StationController@update` | `200 OK` / `422` | Memperbarui data ketinggian muka air, debit arus, atau nama lokasi pos pantau. |
| `DELETE` | `/api/v1/stations/{id}` | `StationController@destroy` | `204 No Content` / `404` | Menghapus stasiun pos pantau dari jaringan pemantauan aktif. |

### Keputusan Desain Utama & API Contract:
1. **Primary Key String Non-Incrementing (`id`):** ID stasiun tetap menggunakan format kode wilayah standar BMKG/PUSAIR (contoh: `STA-KATULAMPA`, `STA-MANGA`), sehingga divalidasi keunikannya pada `StoreStationRequest` dan client berhak mendefinisikan kode ID saat create.
2. **Standard Uniform Envelope:** Seluruh respons sukses dibungkus dalam `{ "status": "success", "message": "...", "data": ... }`, dan respons gagal dibungkus dalam `{ "status": "error", "message": "...", "errors": [ ... ] }`.
3. **Penyelarasan Error Envelope Form Request:** Method `failedValidation()` pada Form Request di-override agar melempar error JSON dalam format array objek `errors: [{"field": "...", "issue": "..."}]`, menjamin konsistensi parsing bagi aplikasi mobile/dashboard RiverVigil.

---

## 4. Bukti Pengujian (Empirical Evidence)

Pengujian dilakukan secara langsung terhadap server lokal aktif (`http://127.0.0.1:8000`) menggunakan client HTTP cURL:

### 4.1 Create Station — `201 Created`
- **Request:**
  ```http
  POST /api/v1/stations HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  Content-Type: application/json

  {
    "id": "STA-MANGA",
    "name": "Pintu Air Manggarai",
    "river_basin": "ciliwung",
    "location": "Jakarta Selatan, DKI Jakarta",
    "current_level_cm": 650.0,
    "flow_rate_m3s": 120.5,
    "alert_status": "normal",
    "thresholds_cm": {
      "normal": 750,
      "siaga_3": 850,
      "siaga_2": 950,
      "siaga_1": 1050
    }
  }
  ```
- **Actual Response:**
  ```http
  HTTP/1.1 201 Created
  Content-Type: application/json

  {
    "status": "success",
    "message": "Pos pantau stasiun sungai baru berhasil didaftarkan.",
    "data": {
      "id": "STA-MANGA",
      "name": "Pintu Air Manggarai",
      "river_basin": "ciliwung",
      "location": "Jakarta Selatan, DKI Jakarta",
      "current_level_cm": 650,
      "flow_rate_m3s": 120.5,
      "alert_status": "normal",
      "thresholds_cm": {
        "normal": 750,
        "siaga_3": 850,
        "siaga_2": 950,
        "siaga_1": 1050
      },
      "updated_at": "2026-10-12T06:35:50.000000Z"
    }
  }
  ```

### 4.2 Detail Station — `200 OK`
- **Request:** `GET /api/v1/stations/STA-MANGA`
- **Actual Response:**
  ```http
  HTTP/1.1 200 OK
  Content-Type: application/json

  {
    "status": "success",
    "message": "Detail stasiun pos pantau ditemukan.",
    "data": {
      "id": "STA-MANGA",
      "name": "Pintu Air Manggarai",
      "river_basin": "ciliwung",
      "location": "Jakarta Selatan, DKI Jakarta",
      "current_level_cm": 650,
      "flow_rate_m3s": 120.5,
      "alert_status": "normal",
      "thresholds_cm": {
        "normal": 750,
        "siaga_3": 850,
        "siaga_2": 950,
        "siaga_1": 1050
      },
      "updated_at": "2026-10-12T06:35:50.000000Z"
    }
  }
  ```

### 4.3 Update Station (Partial via PATCH) — `200 OK`
- **Request:**
  ```http
  PATCH /api/v1/stations/STA-MANGA HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  Content-Type: application/json

  {
    "current_level_cm": 860.0,
    "alert_status": "siaga_3"
  }
  ```
- **Actual Response:**
  ```http
  HTTP/1.1 200 OK
  Content-Type: application/json

  {
    "status": "success",
    "message": "Data stasiun pos pantau berhasil diperbarui.",
    "data": {
      "id": "STA-MANGA",
      "name": "Pintu Air Manggarai",
      "river_basin": "ciliwung",
      "location": "Jakarta Selatan, DKI Jakarta",
      "current_level_cm": 860,
      "flow_rate_m3s": 120.5,
      "alert_status": "siaga_3",
      "thresholds_cm": {
        "normal": 750,
        "siaga_3": 850,
        "siaga_2": 950,
        "siaga_1": 1050
      },
      "updated_at": "2026-10-12T06:35:50.000000Z"
    }
  }
  ```
  *(Terbukti nilai ketinggian air berubah menjadi 860 cm dan status siaga menjadi `siaga_3` tanpa menghilangkan atribut lokasi atau threshold).*

### 4.4 Delete Station — `204 No Content`
- **Request:** `DELETE /api/v1/stations/STA-MANGA`
- **Actual Response:**
  ```http
  HTTP/1.1 204 No Content
  ```
  *(Body respons kosong / 0 bytes).*

---

## 5. Error Cases & Penanganannya

### 5.1 Validation Error — `422 Unprocessable Content`
Kasus ketika client mengirimkan ID yang sudah terdaftar (duplikat) atau field kosong:
- **Request:**
  ```http
  POST /api/v1/stations HTTP/1.1
  Accept: application/json
  Content-Type: application/json

  {
    "id": "STA-MANGA",
    "name": ""
  }
  ```
- **Actual Response:**
  ```http
  HTTP/1.1 422 Unprocessable Content
  Content-Type: application/json

  {
    "status": "error",
    "message": "Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.",
    "errors": [
      {
        "field": "id",
        "issue": "The id has already been taken."
      },
      {
        "field": "name",
        "issue": "The name field is required."
      },
      {
        "field": "river_basin",
        "issue": "The river basin field is required."
      },
      {
        "field": "location",
        "issue": "The location field is required."
      },
      {
        "field": "current_level_cm",
        "issue": "The current level cm field is required."
      },
      {
        "field": "flow_rate_m3s",
        "issue": "The flow rate m3s field is required."
      },
      {
        "field": "thresholds_cm",
        "issue": "The thresholds cm field is required."
      },
      {
        "field": "thresholds_cm.normal",
        "issue": "The thresholds cm.normal field is required."
      },
      {
        "field": "thresholds_cm.siaga_3",
        "issue": "The thresholds cm.siaga 3 field is required."
      },
      {
        "field": "thresholds_cm.siaga_2",
        "issue": "The thresholds cm.siaga 2 field is required."
      },
      {
        "field": "thresholds_cm.siaga_1",
        "issue": "The thresholds cm.siaga 1 field is required."
      }
    ]
  }
  ```

### 5.2 Resource Not Found — `404 Not Found`
Kasus ketika client meminta ID stasiun yang tidak terdaftar atau telah dihapus:
- **Request:** `GET /api/v1/stations/STA-UNKNOWN`
- **Actual Response:**
  ```http
  HTTP/1.1 404 Not Found
  Content-Type: application/json

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

## 6. Hasil Pengujian Otomatis (PHPUnit & Postman)

### 6.1 PHPUnit Feature Test Suite
Suite pengujian otomatis dijalankan melalui command `vendor/bin/phpunit`:
- `test_can_list_stations_with_200_ok`: **PASSED**
- `test_can_filter_stations_by_river_basin`: **PASSED**
- `test_can_get_single_station_detail_with_200_ok`: **PASSED**
- `test_returns_404_when_station_not_found`: **PASSED**
- `test_v1_prefixed_routes_work_identically`: **PASSED**
- `test_can_create_station_with_201_created`: **PASSED**
- `test_validation_fails_on_duplicate_id_and_missing_fields_with_422`: **PASSED**
- `test_can_partially_update_station_with_200_ok`: **PASSED**
- `test_update_fails_with_invalid_alert_status_with_422`: **PASSED**
- `test_update_returns_404_when_station_not_found`: **PASSED**
- `test_can_delete_station_with_204_no_content`: **PASSED**
- `test_delete_returns_404_when_station_not_found`: **PASSED**

Hasil komparatif seluruh test suite aplikasi:
```text
{"tool":"phpunit","result":"passed","tests":24,"passed":24,"assertions":129,"duration_ms":1771}
```
*Total 24 tests (12 Station CRUD tests + 10 Book CRUD tests + 2 framework tests), 129 assertions, 0 failed (100% SUCCESS).*

### 6.2 Postman Collection
Tersimpan pada `docs/postman/rivervigil-api.postman_collection.json`. Berisi folder `01 Stations` yang telah dilengkapi seluruh request `GET`, `POST`, `PATCH`, `DELETE`, `422 Validation Error`, dan `404 Not Found` dengan test assertion scripts (`pm.test`).

---

## 7. Kesimpulan

Penerapan konsep Praktikum 5 (CRUD, Form Request, API Resource, dan Standardized Response) pada resource `Station` dalam proyek semester RiverVigil API berhasil diselesaikan dengan keunggulan teknis sebagai berikut:
1. **Pemisahan Tanggung Jawab (*Separation of Concerns*):** Controller tidak lagi mengelola validasi manual atau formatting data kotor. Validasi berada di `StoreStationRequest` dan `UpdateStationRequest`, transformasi berada di `StationResource`, dan penyimpanan diorkestrasi oleh Eloquent.
2. **Kesesuaian Kontrak (*Contract Compliance*):** Seluruh respons mematuhi skema kontrak API RiverVigil v1.1.0 dengan envelope `status`, `message`, `data`, dan `errors`.
3. **Robustness & Keamanan:** Penanganan ID duplikat, atribut numerik negatif, validasi format ambang siaga, serta perlindungan mass assignment berhasil diuji dan diverifikasi secara otomatis tanpa cacat.

---

## 8. Referensi & Deklarasi Penggunaan AI

### Referensi
1. **Laravel Documentation (v11/v12/v13):**
   - *Eloquent: API Resources:* [https://laravel.com/docs/eloquent-resources](https://laravel.com/docs/eloquent-resources)
   - *Validation: Form Request Validation:* [https://laravel.com/docs/validation#form-request-validation](https://laravel.com/docs/validation#form-request-validation)
   - *Controllers & Resource Routing:* [https://laravel.com/docs/controllers#resource-controllers](https://laravel.com/docs/controllers#resource-controllers)
2. **Internet Engineering Task Force (IETF):**
   - *RFC 9110 — HTTP Semantics:* Status codes `200 OK`, `201 Created`, `204 No Content`, `404 Not Found`, `422 Unprocessable Content`.
   - *RFC 8259 — The JavaScript Object Notation (JSON) Data Interchange Format*.
3. **Dokumen Internal RiverVigil:**
   - *API Contract v1.1.0:* `docs/api-contract.md`.
   - *Panduan Tugas 5 — CRUD Resource Utama:* Modul Pembelajaran Manajemen API 2026.

### Deklarasi Penggunaan AI
- **Alat / Model AI:** Antigravity AI Coding Assistant (Gemini 3.8 Flash model).
- **Tujuan Penggunaan:** Membantu scaffolding kelas Form Request, Eloquent Resource, penulisan skenario feature test, perancangan item Postman collection, dan penyusunan draf dokumentasi.
- **Metode Verifikasi:** Seluruh kode dieksekusi secara empiris pada lingkungan lokal menggunakan PHP 8.3 CLI, live web server port 8000, serta suite pengujian otomatis PHPUnit (24 tests, 129 assertions, 100% pass).
- **Integritas Keamanan:** Memastikan tidak ada token, secret, kredensial, atau berkas `.env` yang tersimpan dalam riwayat commit.
