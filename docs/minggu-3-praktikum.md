# Laporan Praktikum 3 — Merancang API Contract

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Modul:** Praktikum 3 — Merancang API Contract  
**Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
**Versi API Contract:** `v1.1.0`  
**Base URL:** `http://127.0.0.1:8000/api/v1`  
**Waktu Praktikum:** 90–120 menit  
**Artefak Terkait:**
* Dokumen API Contract: [docs/api-contract.md](api-contract.md)
* Collection Postman Praktikum 3: [docs/postman/week-03-contract.postman_collection.json](postman/week-03-contract.postman_collection.json)
* Collection Postman Proyek RiverVigil: [docs/postman/rivervigil-api.postman_collection.json](postman/rivervigil-api.postman_collection.json)
* Prototipe Router REST API Server: [server.php](../server.php)

---

## 1. Ringkasan & Tujuan Praktikum

Setelah menyelesaikan seluruh tahapan pada Praktikum 3 ini, mahasiswa mampu:
1. **Memetakan Kebutuhan Menjadi Resource:** Menganalisis kebutuhan fungsional sistem peringatan dini banjir (*flood early warning*) dan telemetri pemantauan sungai menjadi entitas-entitas RESTful resource yang terdefinisi dengan tepat.
2. **Menuliskan Endpoint dan Method:** Merancang struktur URL yang konsisten, hierarkis, dan deklaratif sesuai standar penamaan kata benda jamak (*plural nouns*), serta memilih HTTP Method yang semantik (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`).
3. **Membuat Contoh Request dan Response JSON:** Menyusun skema *uniform envelope* yang baku untuk respon sukses (`200 OK`, `201 Created`) dan respon gagal (`400 Bad Request`, `404 Not Found`, `422 Unprocessable Content`).
4. **Menunjukkan Bukti Pengujian Terverifikasi:** Menguji kontrak API secara empiris terhadap prototipe server lokal yang dapat dijalankan ulang (*reproducible*), mencatat header HTTP, kode status, waktu respons, serta menyimpan rangkaian unit test ke dalam Postman Collection.
5. **Menerapkan Version Control yang Aman:** Mengelola perubahan repositori menggunakan Git dengan konvensi commit terstandar tanpa mengekspos kredensial, API key, atau berkas rahasia.

---

## 2. Bagian 1 — Persiapan

### 2.1 Pemeriksaan Environment Pengembangan
Sebelum memulai perancangan kontrak dan pengujian, seluruh environment pendukung diverifikasi melalui terminal:

```powershell
# 1. Verifikasi instalasi Git
git --version
# Output: git version 2.55.0.windows.5

# 2. Verifikasi instalasi PHP CLI (melalui direktori XAMPP lokal)
C:\xampp\php\php.exe -v
# Output: PHP 8.0.30 (cli) (built: Sep 1 2023 14:15:38) ( ZTS Visual C++ 2019 x64 )

# 3. Verifikasi utilitas HTTP Client Curl
curl.exe --version
# Output: curl 8.21.0 (Windows) libcurl/8.21.0 Schannel zlib/1.3.2 WinIDN WinLDAP

# 4. Verifikasi Node.js (untuk eksekusi script dan Newman)
node -v
# Output: v20.17.0
```

> **Catatan Konfigurasi Lingkungan:**  
> Jika perintah `php` belum terdaftar pada variabel `PATH` global sistem Windows, pemanggilan CLI dilakukan secara eksplisit menggunakan direktori executable: `C:\xampp\php\php.exe`.

### 2.2 Sinkronisasi Repositori & Checkpoint Git
Repositori proyek disinkronkan dengan remote repository bersama, dan branch kerja disiapkan untuk checkpoint pengerjaan modul Minggu 3:

```powershell
# Memeriksa status repositori dan branch kerja
git status
# On branch master
# Your branch is up to date with 'origin/master'.

# Membuat branch checkpoint praktikum minggu 3
git checkout -b feature/week-03-api-contract
```

### 2.3 Peninjauan API Contract Awal & Kebutuhan Pengembangan
Pada Praktikum 2 sebelumnya, eksplorasi HTTP difokuskan pada 3 resource dasar (`stations`, `telemetry`, dan `alerts/active`). Untuk Praktikum 3, dilakukan evaluasi kesenjangan (*gap analysis*) terhadap arsitektur sistem peringatan dini bencana sungai secara holistik. Ditemukan kebutuhan untuk:
* Melengkapi operasi CRUD pada resource pos pantau (`POST /stations`, `PUT /stations/{id}`, `DELETE /stations/{id}`).
* Menyediakan sub-resource relasional riwayat telemetri per pos pantau (`GET /stations/{id}/telemetry`).
* Menambahkan resource posko pengungsian tanggap darurat (`/shelters`).
* Menambahkan resource kanal pelaporan partisipatif warga/relawan lapangan (`/reports`).
* Menstandarkan penanganan error struktural sintaks (`400 Bad Request`) dan validasi logika domain (`422 Unprocessable Content`).

### 2.4 Keamanan Database Development & Zero-Leakage Policy
* Prototipe server pada tahap perancangan kontrak menggunakan *in-memory mock datastore* terisolasi tanpa kredensial basis data hardcoded.
* Berkas `.gitignore` telah dikonfigurasi untuk mencegah file `.env`, file konfigurasi lokal, log runtime, dan file temporary tersimpan ke dalam repositori.

---

## 3. Bagian 2 — Implementasi & Perancangan API Contract

### 3.1 Pemetaan Kebutuhan Menjadi Resource (Requirements to Resources Mapping)

Sistem **RiverVigil API** dirancang untuk menghubungkan tiga pemangku kepentingan utama:
1. **Perangkat Sensor IoT Pos Pantau Sungai:** Mengirimkan data telemetri otomatis secara berkala.
2. **Pusat Pengendali BPBD (Badan Penanggulangan Bencana Daerah):** Memantau status seluruh stasiun, menetapkan status siaga bencana, mengelola posko pengungsian, dan memvalidasi laporan.
3. **Masyarakat Umum & Relawan Kebencanaan:** Menerima notifikasi peringatan dini, memantau titik posko evakuasi terdekat, serta melaporkan kondisi banjir di lingkungan sekitar.

Berdasarkan analisis kebutuhan tersebut, dipetakan 5 resource RESTful utama:

| No | Kebutuhan Fungsional Bisnis | Entitas Domain | REST Resource URI | Format Tipe | Relasi / Keterkaitan |
|---|---|---|---|---|---|
| 1 | Pengelolaan katalog dan batas ambang siaga pos pantau aliran sungai | Stasiun Pos Pantau | `/stations` | Collection Resource | Memiliki relasi one-to-many ke data `telemetry` |
| 2 | Ingesti otomatis data pembacaan sensor ketinggian air, debit arus, dan curah hujan | Log Telemetri Sensor | `/telemetry` | Independent Ingestion & Sub-resource `/stations/{id}/telemetry` | Merujuk ke entitas induk `Station` melalui foreign key `station_id` |
| 3 | Publikasi peringatan dini banjir kiriman, kalkulasi rambat air ke hilir, dan pencabutan status | Peringatan Dini Banjir | `/alerts` | Controller & State Resource (`/alerts/active`, `/alerts/{id}/resolve`) | Terhubung ke stasiun sumber kenaikan air `station_id` |
| 4 | Manajemen titik kumpul posko pengungsian bencana, kapasitas warga, dan ketersediaan fasilitas darurat | Posko Evakuasi | `/shelters` | Collection Resource | Entitas mandiri pendukung tanggap darurat bencana |
| 5 | Kanal pelaporan kondisi genangan riil berbasis partisipasi warga dan relawan lapangan | Laporan Partisipasi Warga | `/reports` | Submission & Verification Queue Resource | Diverifikasi oleh petugas BPBD sebelum diintegrasikan ke peta bencana |

---

### 3.2 Penulisan Endpoint dan HTTP Method

Sesuai prinsip RESTful API Design:
* **URI Naming:** Selalu menggunakan kata benda jamak (*plural nouns*) huruf kecil, menghindari kata kerja (*verbs*) di dalam path, dan menggunakan pemisah tanda hubung (*kebab-case*) jika diperlukan.
* **HTTP Methods Semantics:**
  * `GET`: Operasi aman (*safe*) dan idempoten (*idempotent*) untuk membaca data representasi resource tanpa mengubah status server.
  * `POST`: Operasi non-idempoten untuk membuat (*create*) resource baru atau mengirimkan aksi kalkulasi.
  * `PUT`: Operasi idempoten untuk memperbarui seluruh atribut resource (*complete replacement*).
  * `PATCH`: Operasi untuk memperbarui sebagian status atau atribut resource (*partial update/state transition*).
  * `DELETE`: Operasi idempoten untuk menghapus resource dari sistem.

#### Matriks Lengkap Endpoint RiverVigil API (v1.1.0)

| HTTP Method | Endpoint Path | Query / Body Parameters | Status Sukses | Potensi Error | Deskripsi Operasi |
|---|---|---|---|---|---|
| `GET` | `/api/v1` | - | `200 OK` | - | Pemeriksaan kesehatan layanan (*health check*) dan katalog rute aktif. |
| `GET` | `/api/v1/stations` | Query: `river_basin`, `status`, `page`, `limit` | `200 OK` | `400` | Mengambil katalog seluruh pos pantau dengan filter DAS dan paginasi. |
| `POST` | `/api/v1/stations` | Body: `id`, `name`, `river_basin`, `location`, `thresholds_cm` | `201 Created` | `400`, `422` | Mendaftarkan stasiun pos pantau pemantau sungai baru ke sistem. |
| `GET` | `/api/v1/stations/{id}` | Path: `id` (e.g. `STA-KATULAMPA`) | `200 OK` | `404` | Mengambil data profil dan ambang batas siaga satu pos pantau. |
| `PUT` | `/api/v1/stations/{id}` | Path: `id`<br>Body: `name`, `river_basin`, `location`, `thresholds_cm` | `200 OK` | `400`, `404`, `422` | Memperbarui informasi data pos pantau secara komprehensif. |
| `DELETE` | `/api/v1/stations/{id}` | Path: `id` | `200 OK` | `404` | Menghapus stasiun pos pantau dari sistem monitoring. |
| `GET` | `/api/v1/stations/{id}/telemetry` | Path: `id` | `200 OK` | `404` | Mengambil riwayat log data telemetri khusus pos pantau yang dituju (*sub-resource*). |
| `POST` | `/api/v1/telemetry` | Body: `station_id`, `water_level_cm`, `flow_rate_m3s`, `recorded_at` | `201 Created` | `400`, `422` | Menerima pengiriman log telemetri sensor air IoT lapangan. |
| `GET` | `/api/v1/alerts/active` | - | `200 OK` | - | Mengambil daftar peringatan dini aktif dan estimasi waktu tiba banjir ke hilir. |
| `POST` | `/api/v1/alerts` | Body: `station_id`, `severity`, `description`, `estimated_arrival_*` | `201 Created` | `400`, `422` | Menerbitkan notifikasi peringatan dini bahaya banjir baru oleh BPBD. |
| `PATCH` | `/api/v1/alerts/{id}/resolve` | Path: `id` (e.g. `ALT-20260925-01`) | `200 OK` | `404` | Mencabut status siaga peringatan dini setelah muka air kembali normal. |
| `GET` | `/api/v1/shelters` | - | `200 OK` | - | Mengambil direktori posko evakuasi pengungsi beserta kapasitasnya. |
| `GET` | `/api/v1/shelters/{id}` | Path: `id` | `200 OK` | `404` | Mengambil detail satu posko evakuasi, daya tampung, dan fasilitas. |
| `GET` | `/api/v1/reports` | - | `200 OK` | - | Mengambil seluruh feed laporan banjir partisipasi warga masyarakat. |
| `POST` | `/api/v1/reports` | Body: `reporter_name`, `location_name`, `inundation_height_cm` | `201 Created` | `400`, `422` | Mengirimkan laporan genangan banjir baru oleh warga sekitar. |

---

### 3.3 Pembuatan Contoh Request dan Response JSON

#### A. Format Envelope Standar
Untuk menjamin konsistensi di seluruh antarmuka klien, seluruh respons dibungkus dalam format *envelope*:

* **Envelope Sukses:** Memuat kunci `status` bernilai `"success"`, `message` deskriptif, `data` (objek/array representasi data), serta `meta` opsional untuk metadata.
* **Envelope Gagal:** Memuat kunci `status` bernilai `"error"`, `message` ringkasan kesalahan, dan array `errors` berisikan pasangan `field` dan `issue`.

#### B. Contoh Request & Response: `GET /api/v1/stations` (Success 200 OK)
* **Request:**
  ```http
  GET /api/v1/stations?river_basin=ciliwung&limit=2 HTTP/1.1
  Host: 127.0.0.1:8000
  Accept: application/json
  ```
* **Response Body (200 OK):**
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
        "updated_at": "2026-09-25T10:30:00+07:00"
      },
      {
        "id": "STA-DEPOK",
        "name": "Pos Pantau Depok",
        "river_basin": "ciliwung",
        "location": "Depok, Jawa Barat",
        "current_level_cm": 185,
        "flow_rate_m3s": 78.1,
        "alert_status": "siaga_3",
        "thresholds_cm": {
          "normal": 100,
          "siaga_3": 175,
          "siaga_2": 270,
          "siaga_1": 350
        },
        "updated_at": "2026-09-25T10:25:00+07:00"
      }
    ],
    "meta": {
      "page": 1,
      "limit": 2,
      "total_records": 3
    }
  }
  ```

#### C. Contoh Request & Response: `POST /api/v1/stations` (Success 201 Created)
* **Request:**
  ```http
  POST /api/v1/stations HTTP/1.1
  Host: 127.0.0.1:8000
  Content-Type: application/json
  Accept: application/json

  {
    "id": "STA-BEKASI",
    "name": "Pos Pantau Kali Bekasi",
    "river_basin": "bekasi",
    "location": "Kota Bekasi, Jawa Barat",
    "current_level_cm": 90.0,
    "flow_rate_m3s": 32.5,
    "thresholds_cm": {
      "normal": 100,
      "siaga_3": 200,
      "siaga_2": 300,
      "siaga_1": 400
    }
  }
  ```
* **Response Body (201 Created):**
  ```json
  {
    "status": "success",
    "message": "Pos pantau stasiun sungai baru berhasil didaftarkan.",
    "data": {
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
      "updated_at": "2026-09-25T11:21:11+07:00"
    }
  }
  ```

#### D. Contoh Request & Response: `POST /api/v1/telemetry` (Success 201 Created)
* **Request:**
  ```http
  POST /api/v1/telemetry HTTP/1.1
  Host: 127.0.0.1:8000
  Content-Type: application/json
  Accept: application/json

  {
    "station_id": "STA-KATULAMPA",
    "water_level_cm": 145.5,
    "flow_rate_m3s": 68.4,
    "rainfall_intensity_mmh": 32.0,
    "recorded_at": "2026-09-25T11:00:00+07:00"
  }
  ```
* **Response Body (201 Created):**
  ```json
  {
    "status": "success",
    "message": "Data telemetri sensor berhasil dicatat ke sistem.",
    "data": {
      "telemetry_id": "TLM-20260925-0691",
      "station_id": "STA-KATULAMPA",
      "water_level_cm": 145.5,
      "flow_rate_m3s": 68.4,
      "rainfall_intensity_mmh": 32,
      "calculated_alert": "siaga_3",
      "recorded_at": "2026-09-25T11:00:00+07:00",
      "created_at": "2026-09-25T11:21:53+07:00"
    }
  }
  ```

#### E. Contoh Error Response: `400 Bad Request` (Malformed JSON Syntax)
* **Request:** Payload JSON memiliki kurung kurawal terbuka atau karakter koma salah.
* **Response Body (400 Bad Request):**
  ```json
  {
    "status": "error",
    "message": "Sintaks JSON tidak valid atau format request rusak.",
    "errors": [
      {
        "field": "payload",
        "issue": "Payload yang dikirim bukan format JSON yang valid (malformed JSON: Syntax error)."
      }
    ]
  }
  ```

#### F. Contoh Error Response: `404 Not Found` (Resource Tidak Ditemukan)
* **Request:** `GET /api/v1/stations/STA-UNKNOWN`
* **Response Body (404 Not Found):**
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

#### G. Contoh Error Response: `422 Unprocessable Content` (Validation Error)
* **Request:** `POST /api/v1/telemetry` dengan atribut angka bernilai string teks atau field mandatory kosong.
* **Response Body (422 Unprocessable Content):**
  ```json
  {
    "status": "error",
    "message": "Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.",
    "errors": [
      {
        "field": "water_level_cm",
        "issue": "Nilai water_level_cm wajib diisi numerik dan harus >= 0."
      },
      {
        "field": "flow_rate_m3s",
        "issue": "Nilai flow_rate_m3s wajib diisi numerik dan harus >= 0."
      },
      {
        "field": "recorded_at",
        "issue": "Field recorded_at (timestamp ISO 8601) wajib disertakan."
      }
    ]
  }
  ```

---

## 4. Bagian 3 — Verifikasi & Bukti Pengujian Empiris

Pengujian verifikasi dilakukan secara langsung terhadap router prototipe mandiri `server.php` yang dijalankan pada server lokal port `8000`.

### 4.1 Menjalankan Server Lokal & Script Pengujian
```powershell
# Menjalankan server lokal di background
C:\xampp\php\php.exe -S 127.0.0.1:8000 server.php

# Menjalankan rangkaian uji otomatis melalui PowerShell / curl
powershell -ExecutionPolicy Bypass -File scratch/run-tests.ps1
```

### 4.2 Bukti Pengujian 1: Success Response Ingesti Telemetri (`POST /api/v1/telemetry`)
* **Endpoint & Method:** `POST http://127.0.0.1:8000/api/v1/telemetry`
* **Request Headers:**
  * `Accept: application/json`
  * `Content-Type: application/json`
* **Request Payload:**
  ```json
  {
    "station_id": "STA-KATULAMPA",
    "water_level_cm": 145.5,
    "flow_rate_m3s": 68.4,
    "rainfall_intensity_mmh": 32.0,
    "recorded_at": "2026-09-25T11:00:00+07:00"
  }
  ```
* **Hasil Verifikasi Empiris:**
  * **HTTP Status:** `201 Created` *(Status Match: True)*
  * **Response Time:** `16 ms`
  * **Response Content-Type:** `application/json; charset=utf-8`
  * **Response Body:**
    ```json
    {
      "status": "success",
      "message": "Data telemetri sensor berhasil dicatat ke sistem.",
      "data": {
        "telemetry_id": "TLM-20260925-0691",
        "station_id": "STA-KATULAMPA",
        "water_level_cm": 145.5,
        "flow_rate_m3s": 68.4,
        "rainfall_intensity_mmh": 32,
        "calculated_alert": "siaga_3",
        "recorded_at": "2026-09-25T11:00:00+07:00",
        "created_at": "2026-09-25T06:21:53+02:00"
      }
    }
    ```
* **Analisis & Keputusan:**
  Server tidak hanya mencatat data, namun secara dinamis mengevaluasi ambang batas (*threshold*) pos pantau Katulampa (di mana 145.5 cm berada di atas batas siaga 3 yaitu 120 cm). Kunci `calculated_alert: "siaga_3"` dikembalikan secara langsung sehingga perangkat IoT tidak perlu melakukan panggilan jaringan tambahan untuk mengetahui tingkat bahaya.

---

### 4.3 Bukti Pengujian 2: Error Response Validasi Data Gagal (`POST /api/v1/telemetry` -> `422 Unprocessable Content`)
* **Endpoint & Method:** `POST http://127.0.0.1:8000/api/v1/telemetry`
* **Request Payload Cacat Logika:**
  ```json
  {
    "station_id": "STA-KATULAMPA",
    "water_level_cm": "bukan_angka"
  }
  ```
* **Hasil Verifikasi Empiris:**
  * **HTTP Status:** `422 Unprocessable Content` *(Status Match: True)*
  * **Response Time:** `2 ms`
  * **Response Content-Type:** `application/json; charset=utf-8`
  * **Response Body:**
    ```json
    {
      "status": "error",
      "message": "Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.",
      "errors": [
        {
          "field": "water_level_cm",
          "issue": "Nilai water_level_cm wajib diisi numerik dan harus >= 0."
        },
        {
          "field": "flow_rate_m3s",
          "issue": "Nilai flow_rate_m3s wajib diisi numerik dan harus >= 0."
        },
        {
          "field": "recorded_at",
          "issue": "Field recorded_at (timestamp ISO 8601) wajib disertakan."
        }
      ]
    }
    ```
* **Analisis & Keputusan:**
  Penggunaan kode status `422` sangat tepat karena format pengiriman berwujud JSON valid, namun melanggar semantik aturan domain (nilai non-numerik dan ketiadaan atribut wajib). Setiap field yang melanggar aturan didaftarkan secara rinci pada array `errors` sehingga klien frontend dapat langsung menyorot field formulir yang salah.

---

### 4.4 Bukti Pengujian 3: Error Response Sintaks Rusak (`400 Bad Request`)
* **Endpoint & Method:** `POST http://127.0.0.1:8000/api/v1/telemetry`
* **Request Payload Malformed:** `{"station_id": "STA-KATULAMPA", INVALID_BODY`
* **Hasil Verifikasi Empiris:**
  * **HTTP Status:** `400 Bad Request` *(Status Match: True)*
  * **Response Time:** `3 ms`
  * **Response Body:**
    ```json
    {
      "status": "error",
      "message": "Sintaks JSON tidak valid atau format request rusak.",
      "errors": [
        {
          "field": "payload",
          "issue": "Payload yang dikirim bukan format JSON yang valid (malformed JSON: Syntax error)."
        }
      ]
    }
    ```

---

### 4.5 Bukti Pengujian 4: Error Response Resource Tidak Ditemukan (`404 Not Found`)
* **Endpoint & Method:** `GET http://127.0.0.1:8000/api/v1/stations/STA-UNKNOWN`
* **Hasil Verifikasi Empiris:**
  * **HTTP Status:** `404 Not Found` *(Status Match: True)*
  * **Response Time:** `2 ms`
  * **Response Body:**
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

### 4.6 Postman Collection Test Scripts

Rangkaian pengujian disimpan dalam artefak Postman Collection:  
`docs/postman/week-03-contract.postman_collection.json`

Setiap item request dilengkapi dengan *Post-response Script* (`pm.test`):
```javascript
// Contoh unit test Postman pada request pembuatan stasiun baru
pm.test("Status code is 201 Created", function () {
    pm.response.to.have.status(201);
});

pm.test("Response adheres to standard envelope", function () {
    const json = pm.response.json();
    pm.expect(json.status).to.eql("success");
    pm.expect(json.data).to.be.an("object");
});

pm.test("Resource ID properly generated", function () {
    const json = pm.response.json();
    pm.expect(json.data.id).to.eql("STA-BEKASI-TEST");
    pm.expect(json.data.thresholds_cm).to.be.an("object");
});
```

---

## 5. Bagian 4 — Version Control & Commit Checkpoint

Perubahan dokumen spesifikasi API Contract, kode router mock server, koleksi pengujian Postman, dan laporan praktikum ditambahkan ke dalam sistem pelacakan Git secara bertahap:

```powershell
# 1. Memeriksa daftar berkas yang diubah (staged & untracked)
git status

# 2. Menambahkan seluruh perubahan berkas ke staging area
git add .

# 3. Memverifikasi tidak ada file kredensial (.env, token, secret) yang ter-stage
git status --short

# 4. Melakukan commit sesuai instruksi resmi modul
git commit -m "docs: complete week 3 API work"
```

> **Verifikasi Kredensial:**  
> Seluruh histori git, variabel collection Postman (`{{base_url}}`), serta payload JSON tidak mengandung credential, secret key, atau password.

---

## 6. Evaluasi dan Penutup

### 6.1 Troubleshooting Guide

| Masalah yang Sering Terjadi | Penyebab Utama | Solusi & Tindakan Perbaikan |
|---|---|---|
| **Command `php` tidak dikenali** | Path instalasi PHP belum terdaftar pada variabel `PATH` Windows. | Jalankan perintah dengan full binary path: `C:\xampp\php\php.exe -S 127.0.0.1:8000 server.php`. |
| **Response JSON tidak sesuai skema envelope** | Format pengembalian fungsi di backend belum seragam atau error langsung di-*echo* tanpa fungsi helper. | Gunakan fungsi pembungkus `sendResponse($statusCode, $status, $message, $data, $errors, $meta)` di seluruh percabangan rute. |
| **Status code selalu `200` meskipun validasi gagal** | Developer mengembalikan response pesan error tetapi lupa menetapkan `http_response_code(422)`. | Pastikan pemanggilan `http_response_code()` dilakukan sebelum fungsi `json_encode()` dan `exit`. |
| **Test Postman Gagal pada Assertion JSON** | Header `Content-Type` tidak dikirim atau body bukan JSON murni. | Pastikan request menyertakan header `Content-Type: application/json` dan `Accept: application/json`. |

---

### 6.2 Checklist Pemenuhan Target Praktikum

- [x] **Target praktikum selesai:** Pemetaan kebutuhan domain ke dalam 5 resource REST (`stations`, `telemetry`, `alerts`, `shelters`, `reports`) telah diselesaikan.
- [x] **Success dan error response telah diuji:** Pengujian empiris pada status `200 OK`, `201 Created`, `400 Bad Request`, `404 Not Found`, dan `422 Unprocessable Content` terbukti lulus (`Match: True`).
- [x] **Postman collection diperbarui:** Artefak `docs/postman/week-03-contract.postman_collection.json` dan `docs/postman/rivervigil-api.postman_collection.json` telah dimutakhirkan dengan skrip `pm.test`.
- [x] **Dokumentasi minggu 3 tersedia:** Laporan lengkap tercatat pada `docs/minggu-3-praktikum.md` dan spesifikasi teknis pada `docs/api-contract.md`.
- [x] **Repository tidak mengandung credential atau secret:** Kebijakan zero-secret ditaati pada seluruh file dan riwayat git.

---

### 6.3 Pertanyaan Refleksi & Jawaban Komprehensif

#### 1. Bukti apa yang menunjukkan hasil sudah benar?
**Jawaban:**  
Kebenaran hasil rancangan dan implementasi API Contract dibuktikan secara objektif dan berlapis melalui empat indikator empiris:
1. **Kesesuaian Semantik HTTP Status Code:** Endpoint mengembalikan kode status yang tepat sesuai standar RFC 9110 (`200` untuk pengambilan data berhasil, `201` untuk penciptaan entitas baru, `400` untuk sintaks JSON rusak, `404` untuk entitas tidak terdaftar, dan `422` untuk pelanggaran validasi logika domain).
2. **Kesesuaian Struktur Envelope JSON:** Seluruh respon memiliki field standar (`status`, `message`, `data`, `meta`, atau `errors`) yang konsisten sehingga aplikasi klien dapat menggunakan generic response parser yang stabil.
3. **Hasil Assertion Script Otomatis Lulus (100% Passed):** Unit testing menggunakan `pm.test` pada Postman dan script pengujian PowerShell berhasil memvalidasi nilai payload dan skema tanpa kegagalan.
4. **Reproducibility (Dapat Dijalankan Ulang):** Bukti pengujian berasal dari interaksi jaringan langsung (*real network round-trip*) terhadap server lokal port `8000`, bukan sekadar mock statis atau tangkapan layar yang tidak dapat diuji ulang.

#### 2. Error case apa yang paling penting?
**Jawaban:**  
Pada sistem RiverVigil API, error case yang paling krusial adalah **`422 Unprocessable Content` (Semantic Validation Error)** pada endpoint ingesti data telemetri sensor (`POST /api/v1/telemetry`) dan pembuatan stasiun (`POST /api/v1/stations`).  
**Alasan:**
* **Integritas Keselamatan Publik (Public Safety Integrity):** Data ketinggian muka air sungai digunakan untuk menentukan status siaga bahaya banjir (`siaga_3`, `siaga_2`, `siaga_1`) dan memicu evakuasi warga. Jika data nilai negatif (misal: `-50 cm`) atau stasiun fiktif diterima oleh server, sistem dapat memicu alarm palsu (*false alarm*) atau sebaliknya, gagal mendeteksi banjir bandang yang sebenarnya.
* **Kejelasan Tindakan Pemulihan (Actionable Error Recovery):** Respons `422` mengembalikan daftar spesifik atribut mana yang salah beserta alasannya (misal: `"Nilai water_level_cm wajib diisi numerik dan harus >= 0"`), sehingga developer perangkat IoT atau aplikasi dapat segera mendeteksi kegagalan hardware sensor atau bug format kiriman.

#### 3. Bagian mana yang perlu masuk API contract?
**Jawaban:**  
Dokumen API Contract harus mencakup spesifikasi komprehensif yang menjadi batas kesepakatan (*contractual agreement*) antara penyedia API (backend) dan pengguna API (frontend/mobile/IoT), yang meliputi:
1. **Metadata & Konvensi Global:** Base URL, versi API (`v1`), format data default (`application/json`), dan konvensi *uniform envelope*.
2. **Spesifikasi Header Wajib:** Header `Accept`, `Content-Type`, token otentikasi (jika ada), serta custom tracking headers seperti `X-Request-ID`.
3. **Matriks Endpoint & HTTP Method:** URL path yang deklaratif, metode HTTP semantik yang diizinkan, serta deskripsi fungsional setiap endpoint.
4. **Definisi Parameter:** Parameter URL/Path, Query Parameters (tipe data, nilai default, batas paginasi), dan Payload Body (skema JSON tipe data, batasan validasi *required/optional*).
5. **Katalog Respons Lengkap:** Contoh JSON representasi sukses (`200`, `201`, `204`) dan representasi error lengkap dengan matriks HTTP Status Code (`400`, `401`, `403`, `404`, `422`, `500`).
6. **Data Dictionary:** Arti setiap atribut field agar tidak terjadi salah interpretasi definisi bisnis antar tim pengembang.

---

## 7. Referensi

1. **Internet Engineering Task Force (IETF):**  
   * RFC 9110 — *HTTP Semantics* (Juni 2022): [https://www.rfc-editor.org/rfc/rfc9110.html](https://www.rfc-editor.org/rfc/rfc9110.html)  
   * RFC 4918 / RFC 9457 — *Problem Details for HTTP APIs*: [https://www.rfc-editor.org/rfc/rfc9457.html](https://www.rfc-editor.org/rfc/rfc9457.html)
2. **Laravel Framework Documentation:**  
   * *Routing & Controllers*: [https://laravel.com/docs/routing](https://laravel.com/docs/routing)  
   * *Request Validation*: [https://laravel.com/docs/validation](https://laravel.com/docs/validation)  
   * *Eloquent API Resources & JSON Responses*: [https://laravel.com/docs/eloquent-resources](https://laravel.com/docs/eloquent-resources)
3. **Postman Learning Center:**  
   * *Writing Automated Post-response Tests*: [https://learning.postman.com/docs/writing-scripts/test-scripts/](https://learning.postman.com/docs/writing-scripts/test-scripts/)  
   * *Working with Collections & Variables*: [https://learning.postman.com/docs/sending-requests/variables/](https://learning.postman.com/docs/sending-requests/variables/)
4. **Pedoman Operasional Penanggulangan Bencana:**  
   * Balai Besar Wilayah Sungai Ciliwung Cisadane (BBWSCC) & Badan Penanggulangan Bencana Daerah (BPBD) DKI Jakarta — *Kriteria Batas Ambang Siaga Pintu Air & Pos Pantau Aliran Sungai*.

---

## 8. Deklarasi Penggunaan AI

Sesuai dengan kode etik integritas akademik:
1. **Penggunaan AI:** Kecerdasan buatan (*Antigravity AI Coding Assistant*) digunakan sebagai instrumen asistensi dalam merapikan struktur dokumentasi markdown, menyusun skema Postman collection standar v2.1, serta merancang script verifikasi pengujian.
2. **Validasi Mandiri Mahasiswa:** Seluruh logika pemetaan domain kebencanaan sungai, penentuan ambang batas siaga pos pantau, penulisan rute router REST `server.php`, analisis status code semantik, serta pengujian empiris diverifikasi dan dijalankan secara sadar dan mandiri oleh mahasiswa.
3. **Proteksi Informasi:** Tidak ada kredensial pribadi, API key, password, atau rahasia yang diekspos dalam repositori maupun prompt interaksi.
