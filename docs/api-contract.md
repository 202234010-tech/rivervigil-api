# Spesifikasi API Contract — RiverVigil API
## Disaster Early Warning & River Telemetry Management Service

**Versi API:** `v1`  
**Status Dokumen:** Approved / Active  
**Format Pertukaran Data:** JSON (`application/json`)  
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 1. Konvensi Arsitektur RESTful & Header

### 1.1 Standar Request Headers
Setiap request yang dikirimkan oleh client wajib menyertakan header standar berikut:

| Header | Nilai | Keterangan |
|---|---|---|
| `Accept` | `application/json` | Memastikan server mengembalikan respons berformat JSON |
| `Content-Type` | `application/json` | Diwajibkan pada method dengan payload body (`POST`, `PUT`, `PATCH`) |
| `X-Client-App` | `RiverVigil-Web/1.0` | Header opsional untuk identifikasi identitas klien pengirim |

### 1.2 Format Struktur Respons (Envelope Standard)

Seluruh respons dari RiverVigil API dibungkus dalam format seragam (*envelope*) untuk mempermudah parsing di sisi client:

#### Format Respons Sukses (`2xx`)
```json
{
  "status": "success",
  "message": "Deskripsi singkat hasil operasi",
  "data": { ... },
  "meta": { ... } // Opsional: pagination atau metadata
}
```

#### Format Respons Gagal (`4xx`, `5xx`)
```json
{
  "status": "error",
  "message": "Pesan ringkasan kesalahan",
  "errors": [
    {
      "field": "nama_field",
      "issue": "Deskripsi spesifik letak kesalahan validasi/sintaks"
    }
  ]
}
```

---

## 2. Daftar Endpoint & Spesifikasi Kontrak

### 2.1 GET `/stations` — Mendapatkan Daftar Pos Pantau Sungai
Mendukung penyaringan (*filtering*) dan pagination melalui Query Parameters.

* **Method:** `GET`
* **Path:** `/api/v1/stations`
* **Query Parameters:**
  * `river_basin` *(string, opsional)*: Filter berdasarkan Daerah Aliran Sungai, misal: `ciliwung`, `pesanggrahan`.
  * `status` *(string, opsional)*: Filter status siaga, nilai: `siaga_1`, `siaga_2`, `siaga_3`, `normal`.
  * `page` *(integer, default: 1)*: Halaman data.
  * `limit` *(integer, default: 10)*: Jumlah data per halaman.
* **Status Code:** `200 OK`
* **Contoh Respons Sukses (200 OK):**
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
      "alert_status": "siaga_3",
      "updated_at": "2026-09-21T22:30:00+07:00"
    },
    {
      "id": "STA-DEPOK",
      "name": "Pos Pantau Depok",
      "river_basin": "ciliwung",
      "location": "Depok, Jawa Barat",
      "current_level_cm": 185,
      "alert_status": "siaga_3",
      "updated_at": "2026-09-21T22:25:00+07:00"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total_records": 2
  }
}
```

---

### 2.2 GET `/stations/{id}` — Detail Pos Pantau Spesifik
Mengambil data detail stasiun pos pantau beserta batas ambang siaga (*alert thresholds*).

* **Method:** `GET`
* **Path:** `/api/v1/stations/{id}`
* **URL Parameter:**
  * `id` *(string, required)*: Kode unik stasiun (misal: `STA-KATULAMPA`).
* **Status Codes:**
  * `200 OK`: Stasiun ditemukan.
  * `404 Not Found`: ID stasiun tidak terdaftar pada sistem.

#### Respons 200 OK
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
    "updated_at": "2026-09-21T22:30:00+07:00"
  }
}
```

#### Respons 404 Not Found
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

### 2.3 POST `/telemetry` — Ingesti Log Telemetri Sensor Ketinggian Air
Menerima pembacaan telemetri terbaru dari IoT gateway sensor air di pos pantau sungai.

* **Method:** `POST`
* **Path:** `/api/v1/telemetry`
* **Request Body (JSON):**
```json
{
  "station_id": "STA-KATULAMPA",
  "water_level_cm": 125,
  "flow_rate_m3s": 58.4,
  "rainfall_intensity_mmh": 22.5,
  "recorded_at": "2026-09-21T22:35:00+07:00"
}
```
* **Aturan Validasi Input:**
  * `station_id`: String, required, harus ID stasiun yang valid.
  * `water_level_cm`: Numeric/Integer, required, $\ge 0$.
  * `flow_rate_m3s`: Numeric/Float, required, $\ge 0$.
  * `rainfall_intensity_mmh`: Numeric/Float, opsional, $\ge 0$.
  * `recorded_at`: String ISO 8601, required.
* **Status Codes:**
  * `201 Created`: Data telemetri berhasil divalidasi dan dicatat.
  * `422 Unprocessable Content`: Validasi logika gagal (misal nilai negatif atau field kosong).
  * `400 Bad Request`: Format JSON rusak (*malformed payload*) atau Content-Type bukan JSON.

#### Respons 201 Created
```json
{
  "status": "success",
  "message": "Data telemetri sensor berhasil dicatat ke sistem.",
  "data": {
    "telemetry_id": "TLM-20260921-0091",
    "station_id": "STA-KATULAMPA",
    "water_level_cm": 125,
    "flow_rate_m3s": 58.4,
    "calculated_alert": "siaga_3",
    "recorded_at": "2026-09-21T22:35:00+07:00",
    "created_at": "2026-09-21T22:35:02+07:00"
  }
}
```

#### Respons 422 Unprocessable Content
```json
{
  "status": "error",
  "message": "Validasi entitas gagal. Periksa kembali atribut payload yang dikirimkan.",
  "errors": [
    {
      "field": "water_level_cm",
      "issue": "Nilai water_level_cm wajib diisi dan harus bernilai numerik lebih besar atau sama dengan 0."
    },
    {
      "field": "station_id",
      "issue": "Nilai station_id tidak boleh kosong."
    }
  ]
}
```

#### Respons 400 Bad Request
```json
{
  "status": "error",
  "message": "Sintaks JSON tidak valid atau format request rusak.",
  "errors": [
    {
      "field": "payload",
      "issue": "Payload yang dikirim bukan format JSON yang valid (malformed JSON)."
    }
  ]
}
```

---

### 2.4 GET `/alerts/active` — Daftar Peringatan Dini Banjir Aktif
Mengembalikan peringatan aktif bagi stasiun dengan status `siaga_1`, `siaga_2`, atau `siaga_3` beserta estimasi waktu rambat air (*travel time*) ke hilir.

* **Method:** `GET`
* **Path:** `/api/v1/alerts/active`
* **Status Code:** `200 OK`
* **Contoh Respons:**
```json
{
  "status": "success",
  "message": "Daftar status peringatan dini aktif berhasil dimuat.",
  "data": [
    {
      "alert_id": "ALT-20260921-01",
      "station_id": "STA-KATULAMPA",
      "station_name": "Bendung Katulampa",
      "river_basin": "ciliwung",
      "severity": "siaga_3",
      "description": "Ketinggian air mencapai 125 cm akibat hujan deras di kawasan Puncak.",
      "estimated_arrival_depok": "2026-09-22T02:30:00+07:00",
      "estimated_arrival_manggarai": "2026-09-22T06:30:00+07:00"
    }
  ]
}
```

---

## 3. Matriks Kode Status HTTP (Semantics)

| HTTP Status | Arti Standar | Skenario Penggunaan di RiverVigil API |
|---|---|---|
| `200 OK` | Operasi berhasil | Pembacaan resource (`GET /stations`, `GET /alerts/active`). |
| `201 Created` | Resource baru berhasil dibuat | Pencatatan data telemetri baru (`POST /telemetry`). |
| `400 Bad Request` | Sintaks request tidak valid | JSON rusak (*malformed syntax*), header `Content-Type` salah. |
| `404 Not Found` | Resource tidak ditemukan | Stasiun ID atau URL path tidak terdaftar di sistem. |
| `422 Unprocessable Content` | Validasi domain gagal | Nilai minus sensor, field wajib tidak diisi, ID stasiun asing. |
| `429 Too Many Requests` | Rate limit terlampaui | Sensor atau client mengirim request di luar ambang batas per menit. |
