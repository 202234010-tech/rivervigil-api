# Spesifikasi API Contract — RiverVigil API
## Disaster Early Warning & River Telemetry Management Service

**Versi API:** `v1.1.0`  
**Status Dokumen:** Approved / Active (Minggu 3 — Merancang API Contract)  
**Format Pertukaran Data:** JSON (`application/json`)  
**Base URL:** `http://127.0.0.1:8000/api/v1`  
**Proyek:** RiverVigil API — Manajemen Layanan Web  

---

## 1. Konvensi Arsitektur RESTful & Header

RiverVigil API mengadopsi prinsip arsitektur Representational State Transfer (REST) dengan pertukaran representasi data berbasis format JSON standar.

### 1.1 Standar Request Headers
Setiap request yang dikirimkan oleh client wajib/disarankan menyertakan header standar berikut:

| Header | Nilai | Wajib/Opsional | Keterangan |
|---|---|---|---|
| `Accept` | `application/json` | Wajib | Memastikan server selalu mengembalikan respons berformat JSON |
| `Content-Type` | `application/json` | Wajib (`POST`, `PUT`, `PATCH`) | Menyatakan format payload request yang dikirim ke server |
| `X-Client-App` | String (misal: `RiverVigil-Web/1.0`, `RiverVigil-IoT/2.1`) | Opsional | Identifikasi aplikasi klien atau tipe perangkat |
| `X-Request-ID` | UUID string | Opsional | ID unik pelacakan tracing permintaan end-to-end |

### 1.2 Format Struktur Respons (Standard Envelope)
Seluruh respons dibungkus dalam format seragam (*uniform envelope*) untuk konsistensi parsing di seluruh platform klien (Web BPBD, Mobile Warga, Perangkat IoT).

#### Format Respons Sukses (`2xx`)
```json
{
  "status": "success",
  "message": "Deskripsi singkat hasil operasi yang berhasil dilakukan",
  "data": { ... }, // Dapat berupa Object entitas tunggal atau Array kumpulan entitas
  "meta": { ... }  // Opsional: informasi pagination, filter aktif, atau metadata komputasi
}
```

#### Format Respons Gagal (`4xx`, `5xx`)
```json
{
  "status": "error",
  "message": "Ringkasan pesan kesalahan umum yang terjadi",
  "errors": [
    {
      "field": "nama_atribut",
      "issue": "Keterangan rinci letak kesalahan validasi atau sintaks"
    }
  ]
}
```

### 1.3 Matriks Status Code HTTP Semantik

| Kode HTTP | Status Text | Penggunaan pada RiverVigil API |
|---|---|---|
| `200` | `OK` | Pengambilan data berhasil (`GET`), pembaruan resource sukses (`PUT`/`PATCH`), penghapusan sukses (`DELETE`). |
| `201` | `Created` | Pembuatan resource baru berhasil (`POST` telemetry, station, alert, report). Mengembalikan data resource yang baru dibuat. |
| `204` | `No Content` | Operasi sukses tanpa body respons tambahan (opsional pada beberapa endpoint penghapusan). |
| `400` | `Bad Request` | Payload request rusak secara sintaks, JSON malformed, atau body request kosong. |
| `401` | `Unauthorized` | Klien belum terotentikasi (untuk endpoint administratif terproteksi). |
| `403` | `Forbidden` | Klien terotentikasi namun tidak memiliki hak akses (role tidak memadai). |
| `404` | `Not Found` | Resource ID yang diminta tidak ditemukan dalam database atau rute URI tidak terdaftar. |
| `405` | `Method Not Allowed` | HTTP method tidak diizinkan pada resource yang dituju. |
| `422` | `Unprocessable Content` | Format JSON valid, namun gagal validasi semantik logika bisnis (misal nilai negatif, foreign key pos tidak ada). |
| `500` | `Internal Server Error` | Terjadi kegagalan komputasi tidak terduga pada server. |

---

## 2. Pemetaan Kebutuhan Domain Menjadi REST Resource

Dalam perancangan API Contract Minggu 3, kebutuhan fungsional sistem peringatan dini banjir dan telemetri sungai dipetakan ke dalam 5 resource inti:

| ID | Kebutuhan Fungsional Sistem | Entitas Domain | REST Resource URI | HTTP Methods | Sub-resource / Relasi |
|---|---|---|---|---|---|
| **REQ-01** | Mengelola katalog pos pantau ketinggian air, koordinat lokasi, dan batas ambang siaga (threshold). | Stasiun Pos Pantau | `/stations` | `GET`, `POST`, `PUT`, `DELETE` | `/stations/{id}/telemetry` |
| **REQ-02** | Mengingesti dan mengarsipkan log pembacaan sensor berkala (ketinggian muka air, debit arus, curah hujan) dari sensor IoT lapangan. | Log Telemetri | `/telemetry` | `POST` | Terhubung ke `Station` via `station_id` |
| **REQ-03** | Mempublikasikan dan menyebarkan status siaga darurat banjir kiriman beserta estimasi waktu rambat air ke hilir. | Peringatan Dini Banjir | `/alerts` | `GET`, `POST`, `PATCH` | `/alerts/active`, `/alerts/{id}/resolve` |
| **REQ-04** | Mengelola direktori posko evakuasi pengungsi, daya tampung warga, logistik medis, dan ketersediaan fasilitas darurat. | Posko Pengungsian | `/shelters` | `GET`, `POST` | `/shelters/{id}` |
| **REQ-05** | Menyediakan kanal pelaporan partisipatif dari relawan dan warga sekitar aliran sungai mengenai genangan riil di pemukiman. | Laporan Partisipasi Warga | `/reports` | `GET`, `POST` | `/reports/{id}` |

---

## 3. Spesifikasi Rinci Endpoint & Kontrak Payload

---

### 3.1 Resource: Stations (`/stations`)

#### A. `GET /api/v1/stations`
Mengambil daftar stasiun pos pantau dengan dukungan penyaringan (*query filtering*) dan pagination.

* **Method:** `GET`
* **Path:** `/api/v1/stations`
* **Query Parameters:**
  * `river_basin` *(string, opsional)*: Filter berdasarkan nama DAS (misal: `ciliwung`, `cisadane`, `bekasi`).
  * `status` *(string, opsional)*: Filter status siaga (`normal`, `siaga_3`, `siaga_2`, `siaga_1`).
  * `page` *(integer, default: 1)*: Nomor halaman.
  * `limit` *(integer, default: 10)*: Batas jumlah rekaman per halaman.
* **Response `200 OK`:**
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
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total_records": 1
  }
}
```

---

#### B. `POST /api/v1/stations`
Mendaftarkan stasiun pos pantau pemantauan sungai baru ke dalam jaringan RiverVigil.

* **Method:** `POST`
* **Path:** `/api/v1/stations`
* **Request Headers:**
  * `Accept: application/json`
  * `Content-Type: application/json`
* **Request Body Example:**
```json
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
* **Response `201 Created`:**
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

---

#### C. `GET /api/v1/stations/{id}`
Mengambil data detail stasiun pos pantau tertentu beserta batas ambang siaga.

* **Method:** `GET`
* **Path:** `/api/v1/stations/{id}`
* **URL Parameters:**
  * `id` *(string, required)*: Kode pengenal stasiun (misal: `STA-KATULAMPA`).
* **Response `200 OK` (Sukses):**
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
    "updated_at": "2026-09-25T10:30:00+07:00"
  }
}
```
* **Response `404 Not Found` (Error):**
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

#### D. `GET /api/v1/stations/{id}/telemetry` (Sub-resource)
Mengambil riwayat log data telemetri yang secara spesifik tercatat untuk satu stasiun pos pantau.

* **Method:** `GET`
* **Path:** `/api/v1/stations/{id}/telemetry`
* **Response `200 OK`:**
```json
{
  "status": "success",
  "message": "Riwayat data telemetri untuk stasiun 'STA-KATULAMPA' berhasil diambil.",
  "data": [
    {
      "telemetry_id": "TLM-20260925-0101",
      "station_id": "STA-KATULAMPA",
      "water_level_cm": 120,
      "flow_rate_m3s": 54.2,
      "rainfall_intensity_mmh": 25,
      "calculated_alert": "siaga_3",
      "recorded_at": "2026-09-25T10:30:00+07:00"
    }
  ],
  "meta": {
    "station_id": "STA-KATULAMPA",
    "total_logs": 1
  }
}
```

---

### 3.2 Resource: Telemetry (`/telemetry`)

#### A. `POST /api/v1/telemetry`
Menerima dan memvalidasi log pembacaan sensor air otomatis dari perangkat IoT pos pantau.

* **Method:** `POST`
* **Path:** `/api/v1/telemetry`
* **Request Headers:**
  * `Accept: application/json`
  * `Content-Type: application/json`
* **Request Body (Valid Payload):**
```json
{
  "station_id": "STA-KATULAMPA",
  "water_level_cm": 145.5,
  "flow_rate_m3s": 68.4,
  "rainfall_intensity_mmh": 32.0,
  "recorded_at": "2026-09-25T11:00:00+07:00"
}
```
* **Response `201 Created`:**
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
* **Response `400 Bad Request` (Malformed JSON Syntax):**
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
* **Response `422 Unprocessable Content` (Validation Error):**
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

### 3.3 Resource: Alerts (`/alerts`)

#### A. `GET /api/v1/alerts/active`
Mengambil seluruh status peringatan dini aktif dan estimasi waktu perambatan air menuju titik hilir kritis.

* **Method:** `GET`
* **Path:** `/api/v1/alerts/active`
* **Response `200 OK`:**
```json
{
  "status": "success",
  "message": "Daftar status peringatan dini aktif berhasil dimuat.",
  "data": [
    {
      "alert_id": "ALT-20260925-01",
      "station_id": "STA-KATULAMPA",
      "station_name": "Bendung Katulampa",
      "river_basin": "ciliwung",
      "severity": "siaga_3",
      "status": "active",
      "description": "Ketinggian air mencapai 120 cm (Siaga 3) akibat hujan deras di kawasan Puncak Bogor.",
      "estimated_arrival_depok": "2026-09-25T14:30:00+07:00",
      "estimated_arrival_manggarai": "2026-09-25T18:30:00+07:00",
      "published_at": "2026-09-25T10:35:00+07:00",
      "resolved_at": null
    }
  ],
  "meta": {
    "total_active": 1
  }
}
```

#### B. `PATCH /api/v1/alerts/{id}/resolve`
Mencabut status peringatan dini setelah tinggi muka air sungai kembali ke batas normal.

* **Method:** `PATCH`
* **Path:** `/api/v1/alerts/{id}/resolve`
* **Response `200 OK`:**
```json
{
  "status": "success",
  "message": "Peringatan dini 'ALT-20260925-01' berhasil diselesaikan (status dicabut).",
  "data": {
    "alert_id": "ALT-20260925-01",
    "station_id": "STA-KATULAMPA",
    "station_name": "Bendung Katulampa",
    "river_basin": "ciliwung",
    "severity": "siaga_3",
    "status": "resolved",
    "description": "Ketinggian air mencapai 120 cm (Siaga 3) akibat hujan deras di kawasan Puncak Bogor.",
    "estimated_arrival_depok": "2026-09-25T14:30:00+07:00",
    "estimated_arrival_manggarai": "2026-09-25T18:30:00+07:00",
    "published_at": "2026-09-25T10:35:00+07:00",
    "resolved_at": "2026-09-25T06:21:53+02:00"
  }
}
```

---

### 3.4 Resource: Shelters (`/shelters`)

#### A. `GET /api/v1/shelters`
Mengambil data direktori posko evakuasi pengungsi, kapasitas daya tampung, kontak penanggung jawab, serta fasilitas darurat yang tersedia.

* **Method:** `GET`
* **Path:** `/api/v1/shelters`
* **Response `200 OK`:**
```json
{
  "status": "success",
  "message": "Daftar posko pengungsian tanggap darurat berhasil diambil.",
  "data": [
    {
      "shelter_id": "SHL-001",
      "name": "GOR Otista Jatinegara",
      "address": "Jl. Otto Iskandardinata No. 121, Jakarta Timur",
      "latitude": -6.230154,
      "longitude": 106.868214,
      "capacity_people": 500,
      "current_occupants": 45,
      "status": "ready",
      "contact_person": "Bambang Sudibyo (0812-3456-7890)",
      "facilities": [
        "Dapur Umum",
        "Tenda Medis",
        "MCK Portabel",
        "Logistik Bayi"
      ]
    }
  ],
  "meta": {
    "total_shelters": 1
  }
}
```

---

### 3.5 Resource: Reports (`/reports`)

#### A. `POST /api/v1/reports`
Menerima laporan genangan banjir partisipatif langsung dari warga atau relawan pos pantau mandiri.

* **Method:** `POST`
* **Path:** `/api/v1/reports`
* **Request Body:**
```json
{
  "reporter_name": "Rian Hidayat",
  "reporter_phone": "081399887766",
  "location_name": "Kampung Melayu RW 02",
  "inundation_height_cm": 65.0,
  "notes": "Genangan air naik pesat di bantaran sungai"
}
```
* **Response `201 Created`:**
```json
{
  "status": "success",
  "message": "Laporan genangan banjir berhasil dikirim dan menunggu verifikasi petugas.",
  "data": {
    "report_id": "REP-20260925-41",
    "reporter_name": "Rian Hidayat",
    "reporter_phone": "081399887766",
    "location_name": "Kampung Melayu RW 02",
    "inundation_height_cm": 65,
    "notes": "Genangan air naik pesat di bantaran sungai",
    "status": "pending_verification",
    "created_at": "2026-09-25T06:21:54+02:00"
  }
}
```

---

## 4. Keamanan & Kebijakan Credential Zero-Leakage

1. Seluruh endpoint dalam spesifikasi API Contract ini dirancang untuk tidak mengekspos token privat, password database, ataupun secret keys.
2. Respons kesalahan (`4xx` dan `5xx`) **tidak boleh** menyertakan trace tumpukan database (*stack trace* internal) atau query SQL mentah demi mencegah kebocoran informasi (*information leakage*).
3. Untuk lingkungan pengujian Postman dan CI/CD, nilai host dan port diisolasi ke dalam variabel dinamis `{{base_url}}`.
