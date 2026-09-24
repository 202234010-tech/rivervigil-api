# Dokumentasi Minggu 2 — Eksplorasi HTTP, REST, dan API Contract

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Topik:** Penerapan HTTP, REST, JSON, Status Codes, dan Postman pada Proyek Semester  
**Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
**Artefak Terkait:**
* Dokumen API Contract: [docs/api-contract.md](api-contract.md)
* Postman Collection Proyek: [docs/postman/rivervigil-api.postman_collection.json](postman/rivervigil-api.postman_collection.json)
* Router Prototipe Mandiri: [server.php](../server.php)

---

## 1. Tujuan

Tujuan dari pengerjaan Tugas 2 ini adalah:
1. Menerapkan pemahaman fundamental protokol **HTTP**, prinsip desain arsitektur **RESTful**, pertukaran payload **JSON**, dan semantik **HTTP Status Codes** yang dieksplorasi pada modul Minggu 2 ke dalam perancangan nyata proyek semester.
2. Mengembangkan **RiverVigil API**, sebuah layanan RESTful terpadu untuk sistem pemantauan telemetri ketinggian muka air sungai (*river water level*) dan peringatan dini bahaya banjir kiriman (*flood early warning*).
3. Menyusun spesifikasi **API Contract** yang baku dan konsisten sebagai acuan kesepahaman antara implementasi server dan konsumsi client (aplikasi mobile warga, dasbor pemantau BPBD, serta perangkat IoT sensor air).
4. Menyediakan **Postman Collection** berisikan rangkaian uji otomatis (*post-response tests*) untuk memverifikasi minimal satu *success response* (`200 OK`, `201 Created`) dan satu *error response* (`400 Bad Request`, `404 Not Found`, `422 Unprocessable Content`).
5. Mengumpulkan bukti hasil uji empiris yang dapat direproduksi dan diverifikasi oleh dosen secara langsung tanpa menyertakan kredensial sensitif.

---

## 2. Perubahan (Changes & Implementation)

Perubahan dan penambahan artefak pada repositori proyek mencakup:

1. **Penyusunan API Contract ([docs/api-contract.md](api-contract.md)):**  
   Mendefinisikan konvensi envelope standar JSON (`status`, `message`, `data`, `errors`, `meta`), aturan header HTTP, serta spesifikasi detail 4 endpoint inti layanan telemetri sungai.
2. **Pembuatan Router Prototipe REST ([server.php](../server.php)):**  
   Menyediakan server REST mandiri berbasis PHP native tanpa dependensi pihak ketiga. Server menangani *routing*, validasi header `Accept` & `Content-Type`, verifikasi integritas sintaks JSON, kalkulasi ambang batas status siaga (`normal`, `siaga_3`, `siaga_2`, `siaga_1`), serta pengembalian status code semantik secara akurat.
3. **Penyusunan Postman Collection ([docs/postman/rivervigil-api.postman_collection.json](postman/rivervigil-api.postman_collection.json)):**  
   Menyusun collection v2.1 yang terbagi dalam 3 folder fungsional:
   * `01 Stations`: Pengujian pembacaan data pos pantau (`GET /stations` dengan query params, `GET /stations/{id}` sukses, dan `GET /stations/{id}` not found).
   * `02 Telemetry`: Pengujian ingesti sensor air (`POST /telemetry` sukses 201, validasi domain gagal 422, dan JSON rusak 400).
   * `03 Alerts`: Pengujian pemanggilan peringatan dini aktif (`GET /alerts/active`).
4. **Pembuatan File Dokumentasi Formal ([docs/minggu-2.md](minggu-2.md)):**  
   Mencatat seluruh tujuan, perubahan, rincian kontrak, bukti uji empiris, analisis error cases, referensi, dan deklarasi etika AI.
5. **Konfigurasi Git & Version Control:**  
   Menginisialisasi repositori Git lokal, memastikan filter `.gitignore` melindungi berkas environment dan rahasia, serta mendokumentasikan commit terstruktur.

---

## 3. Endpoint atau Contract

Layanan RiverVigil API mengadopsi standar arsitektur REST (Representational State Transfer) dengan konvensi pertukaran format data JSON murni.

### 3.1 Konvensi Standar Request & Response

* **Base URL:** `http://127.0.0.1:8000/api/v1`
* **Request Headers:**
  * `Accept: application/json`
  * `Content-Type: application/json` (diwajibkan pada payload `POST`)
* **Format Envelope Respons Sukses:**
  ```json
  {
    "status": "success",
    "message": "Pesan deskriptif hasil operasi",
    "data": { ... },
    "meta": { "page": 1, "limit": 10, "total_records": 3 }
  }
  ```
* **Format Envelope Respons Gagal:**
  ```json
  {
    "status": "error",
    "message": "Deskripsi kesalahan umum",
    "errors": [
      {
        "field": "nama_atribut",
        "issue": "Keterangan rinci letak kesalahan"
      }
    ]
  }
  ```

### 3.2 Ringkasan Matriks Endpoint Proyek

| HTTP Method | Endpoint URI | Parameter / Payload | Expected Status | Fungsi Bisnis |
|---|---|---|---|---|
| `GET` | `/api/v1/stations` | Query: `river_basin`, `status`, `page`, `limit` | `200 OK` | Mengambil katalog seluruh pos pantau sungai dengan filter |
| `GET` | `/api/v1/stations/{id}` | Path: `id` (e.g. `STA-KATULAMPA`) | `200 OK` / `404 Not Found` | Mengambil detail spesifik satu stasiun dan ambang siaganya |
| `POST` | `/api/v1/telemetry` | Body JSON: `station_id`, `water_level_cm`, `flow_rate_m3s`, `recorded_at` | `201 Created` / `422 Unprocessable Content` / `400 Bad Request` | Menerima pengiriman log data telemetri dari sensor pos pantau |
| `GET` | `/api/v1/alerts/active` | - | `200 OK` | Mengambil status peringatan dini aktif dan estimasi rambat air |

---

## 4. Bukti Pengujian (Testing & Evidence)

Pengujian dilakukan terhadap server lokal `server.php` yang berjalan pada port `8000`. Pengujian mengevaluasi anatomi request (Method, URL, Headers, Body), kode status HTTP, waktu respons, serta struktur JSON body menggunakan assertion script Postman (`pm.test`).

### 4.1 Success Response 1: Ingesti Data Telemetri Sensor (`POST /api/v1/telemetry`)

* **Tujuan Uji:** Memverifikasi kemampuan API dalam menerima payload sensor valid dan mengembalikan kode status semantik `201 Created`.
* **HTTP Request:**
  * **Method:** `POST`
  * **URL:** `http://127.0.0.1:8000/api/v1/telemetry`
  * **Headers:**
    * `Accept: application/json`
    * `Content-Type: application/json`
  * **Payload Body (JSON):**
    ```json
    {
      "station_id": "STA-KATULAMPA",
      "water_level_cm": 125,
      "flow_rate_m3s": 58.4,
      "rainfall_intensity_mmh": 22.5,
      "recorded_at": "2026-09-21T22:35:00+07:00"
    }
    ```

* **HTTP Response:**
  * **Status Code:** `201 Created`
  * **Response Headers:**
    * `Content-Type: application/json; charset=utf-8`
    * `Access-Control-Allow-Origin: *`
  * **Response Time:** `~18 ms`
  * **Response Body (JSON):**
    ```json
    {
      "status": "success",
      "message": "Data telemetri sensor berhasil dicatat ke sistem.",
      "data": {
        "telemetry_id": "TLM-20260921-0281",
        "station_id": "STA-KATULAMPA",
        "water_level_cm": 125,
        "flow_rate_m3s": 58.4,
        "rainfall_intensity_mmh": 22.5,
        "calculated_alert": "siaga_3",
        "recorded_at": "2026-09-21T22:35:00+07:00",
        "created_at": "2026-09-21T22:38:44+07:00"
      }
    }
    ```

* **Postman Test Script & Assertion:**
  ```javascript
  pm.test("Status code is 201 Created", function () {
      pm.response.to.have.status(201);
  });

  pm.test("Telemetry successfully registered with ID and calculated alert", function () {
      const jsonData = pm.response.json();
      pm.expect(jsonData.status).to.eql("success");
      pm.expect(jsonData.data).to.have.property("telemetry_id");
      pm.expect(jsonData.data.station_id).to.eql("STA-KATULAMPA");
      pm.expect(jsonData.data.water_level_cm).to.eql(125);
      pm.expect(jsonData.data.calculated_alert).to.eql("siaga_3");
  });
  ```
* **Hasil Uji:** `PASS` (Status code is 201 Created & Telemetry registered).

---

### 4.2 Success Response 2: Mengambil Daftar Pos Pantau dengan Query Params (`GET /api/v1/stations`)

* **Tujuan Uji:** Memverifikasi pembacaan query parameters untuk filtering Daerah Aliran Sungai (DAS) dan pagination.
* **HTTP Request:**
  * **Method:** `GET`
  * **URL:** `http://127.0.0.1:8000/api/v1/stations?river_basin=ciliwung&page=1`
  * **Headers:** `Accept: application/json`
* **HTTP Response:**
  * **Status Code:** `200 OK`
  * **Response Body (JSON):**
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
          "updated_at": "2026-09-21T22:30:00+07:00"
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
          "updated_at": "2026-09-21T22:25:00+07:00"
        },
        {
          "id": "STA-MANGGARAI",
          "name": "Pintu Air Manggarai",
          "river_basin": "ciliwung",
          "location": "Jakarta Selatan, DKI Jakarta",
          "current_level_cm": 720,
          "flow_rate_m3s": 110.5,
          "alert_status": "normal",
          "thresholds_cm": {
            "normal": 750,
            "siaga_3": 850,
            "siaga_2": 950,
            "siaga_1": 1050
          },
          "updated_at": "2026-09-21T22:28:00+07:00"
        }
      ],
      "meta": {
        "page": 1,
        "limit": 10,
        "total_records": 3
      }
    }
    ```
* **Hasil Uji:** `PASS` (Status code is 200 OK, envelope is valid array, pagination meta present).

---

### 4.3 Ringkasan Eksekusi Otomatis Postman Runner (Collection Runner Summary)

Seluruh request dan skrip post-response tests pada collection `RiverVigil API — Minggu 2 Eksplorasi HTTP & Contract` dieksekusi secara otomatis dengan hasil **100% Passed (17 assertions passed, 0 failed)**:

```text
RiverVigil API — Minggu 2 Eksplorasi HTTP & Contract

□ 01 Stations
└ GET All Stations with Query Params
  GET http://127.0.0.1:8000/api/v1/stations?river_basin=ciliwung&page=1 [200 OK, 2.09kB, 25ms]
  √  Status code is 200 OK
  √  Response is valid JSON
  √  Response envelope status is success
  √  Query parameter filtering is respected

└ GET Station by ID - Success (200 OK)
  GET http://127.0.0.1:8000/api/v1/stations/STA-KATULAMPA [200 OK, 876B, 5ms]
  √  Status code is 200 OK
  √  Station ID matches requested parameter
  √  Threshold structure is present

└ GET Station by ID - Not Found (404 Not Found)
  GET http://127.0.0.1:8000/api/v1/stations/STA-UNKNOWN [404 Not Found, 581B, 5ms]
  √  Status code is 404 Not Found
  √  Error envelope structure is returned

□ 02 Telemetry
└ POST Record Telemetry - Success (201 Created)
  POST http://127.0.0.1:8000/api/v1/telemetry [201 Created, 781B, 5ms]
  √  Status code is 201 Created
  √  Telemetry successfully registered

└ POST Record Telemetry - Validation Error (422 Unprocessable Content)
  POST http://127.0.0.1:8000/api/v1/telemetry [422 Unprocessable Content, 766B, 4ms]
  √  Status code is 422 Unprocessable Content
  √  Validation error list matches contract

└ POST Record Telemetry - Malformed JSON (400 Bad Request)
  POST http://127.0.0.1:8000/api/v1/telemetry [400 Bad Request, 617B, 5ms]
  √  Status code is 400 Bad Request
  √  Malformed JSON error returned

□ 03 Alerts
└ GET Active Early Warning Alerts (200 OK)
  GET http://127.0.0.1:8000/api/v1/alerts/active [200 OK, 1.33kB, 4ms]
  √  Status code is 200 OK
  √  Active alerts data returned as array

┌─────────────────────────┬─────────────────┬─────────────────┐
│                         │        executed │          failed │
├─────────────────────────┼─────────────────┼─────────────────┤
│              iterations │               1 │               0 │
├─────────────────────────┼─────────────────┼─────────────────┤
│                requests │               7 │               0 │
├─────────────────────────┼─────────────────┼─────────────────┤
│            test-scripts │               7 │               0 │
├─────────────────────────┼─────────────────┼─────────────────┤
│      prerequest-scripts │               0 │               0 │
├─────────────────────────┼─────────────────┼─────────────────┤
│              assertions │              17 │               0 │
├─────────────────────────┴─────────────────┴─────────────────┤
│ total run duration: 605ms                                   │
├─────────────────────────────────────────────────────────────┤
│ total data received: 4.67kB (approx)                        │
├─────────────────────────────────────────────────────────────┤
│ average response time: 7ms [min: 4ms, max: 25ms, s.d.: 7ms] │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Error Case (Analisis Kasus Gagal)

Dalam arsitektur API yang matang, penanganan kesalahan (*error handling*) harus bersifat deterministik, informatif, dan mematuhi spesifikasi HTTP. Berikut adalah bukti pengujian kasus error pada RiverVigil API:

### 5.1 Error Case 1: Validasi Domain Gagal (`422 Unprocessable Content`)

* **Skenario:** Client (atau sensor yang rusak) mengirimkan payload dengan angka ketinggian air dan debit bernilai negatif (`water_level_cm: -15`), yang secara fisik mustahil terjadi.
* **HTTP Request:**
  * **Method:** `POST`
  * **URL:** `http://127.0.0.1:8000/api/v1/telemetry`
  * **Payload:**
    ```json
    {
      "station_id": "STA-KATULAMPA",
      "water_level_cm": -15,
      "flow_rate_m3s": -5,
      "recorded_at": "2026-09-21T22:35:00+07:00"
    }
    ```
* **HTTP Response:**
  * **Status Code:** `422 Unprocessable Content`
  * **Response Body (JSON):**
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
        }
      ]
    }
    ```
* **Justifikasi Status Code:**
  Sintaks JSON yang dikirimkan sebenarnya valid secara gramatikal (bukan malformed), sehingga kode `400 Bad Request` kurang presisi. Sesuai RFC 9110 / RFC 4918 dan konvensi standar framework Laravel, kode `422 Unprocessable Content` adalah status paling semantik untuk menandakan bahwa server memahami tipe konten request, namun tidak dapat memproses instruksi karena pelanggaran aturan validasi semantik/domain.

---

### 5.2 Error Case 2: Sintaks JSON Rusak (`400 Bad Request`)

* **Skenario:** Hardware sensor mengalami *power glitch* saat transmisi data jaringan sehingga paket JSON yang terkirim terpotong (*truncated/malformed syntax*).
* **HTTP Request:**
  * **Method:** `POST`
  * **URL:** `http://127.0.0.1:8000/api/v1/telemetry`
  * **Payload:** `{ broken_json_payload : 123 ` *(tanpa kurung kurawal penutup dan tanda petik tidak valid)*
* **HTTP Response:**
  * **Status Code:** `400 Bad Request`
  * **Response Body (JSON):**
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
* **Justifikasi Status Code:**
  Kode `400 Bad Request` digunakan secara tepat karena web server bahkan tidak mampu menguraikan (*parsing*) pohon sintaks request JSON tersebut. Client dilarang mengirim ulang data dengan format yang sama sebelum memperbaiki sintaks request.

---

### 5.3 Error Case 3: Resource Pos Pantau Tidak Ditemukan (`404 Not Found`)

* **Skenario:** Client mencoba mengakses detail stasiun dengan kode pengenal yang tidak ada pada basis data sistem.
* **HTTP Request:**
  * **Method:** `GET`
  * **URL:** `http://127.0.0.1:8000/api/v1/stations/STA-UNKNOWN`
* **HTTP Response:**
  * **Status Code:** `404 Not Found`
  * **Response Body (JSON):**
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
* **Justifikasi Status Code:**
  Server mengenali URI format yang diminta, namun sumber daya entitas spesifik pada parameter path tersebut tidak eksis di server. Client dapat langsung menampilkan pesan bahwa pos pantau tidak ditemukan tanpa memicu kepanikan sistem (*no server crash*).

---

## 6. Kesimpulan & Keputusan Utama Arsitektur

Berdasarkan eksplorasi HTTP pada proyek semester RiverVigil API, keputusan-keputusan teknis utama yang diambil meliputi:

1. **Konsistensi Envelope Response Mempermudah Deserialisasi Client:**  
   Penyediaan kunci seragam (`status`, `message`, `data`, `errors`) pada seluruh endpoint memastikan aplikasi frontend dan IoT mobile dapat membuat *generic response interceptor*. Client cukup memeriksa status code dan `response.status === "success"` tanpa perlu menulis parser yang berbeda-beda untuk tiap endpoint.
2. **Kepatuhan Semantik Status Code HTTP Mencegah Ambiguitas:**  
   Memisahkan antara error sintaks struktural (`400`), resource tidak ditemukan (`404`), dan kegagalan validasi logika bisnis (`422`) memberikan kejelasan tindakan lanjutan bagi client (*actionable client error recovery*).
3. **Pemberian ID Telemetri Unik dan Kalkulasi Siaga Otomatis:**  
   Pada method `POST /telemetry`, respon `201 Created` menyertakan `telemetry_id` serta hasil kalkulasi status siaga (`calculated_alert`). Hal ini menghemat panggilan network tambahan (*round-trip latency*) dari pos sensor ke server.
4. **Automated Postman Test Suites Menjamin Regression Safety:**  
   Penerapan unit test script di Postman menjamin bahwa perubahan kode atau refaktorisasi sistem ke backend Laravel di masa mendatang tidak akan melanggar kesepakatan kontrak API yang telah disetujui.

---

## 7. Referensi

1. **Internet Engineering Task Force (IETF):**  
   * RFC 9110 — *HTTP Semantics* (Juni 2022): [https://www.rfc-editor.org/rfc/rfc9110.html](https://www.rfc-editor.org/rfc/rfc9110.html)  
   * RFC 4918 — *HTTP Status Code 422 (Unprocessable Content)*: [https://www.rfc-editor.org/rfc/rfc4918.html](https://www.rfc-editor.org/rfc/rfc4918.html)
2. **Laravel Documentation:**  
   * *Routing & Controllers*: [https://laravel.com/docs/11.x/routing](https://laravel.com/docs/11.x/routing)  
   * *HTTP Requests & Validation*: [https://laravel.com/docs/11.x/validation](https://laravel.com/docs/11.x/validation)  
   * *Eloquent API Resources & JSON Responses*: [https://laravel.com/docs/11.x/eloquent-resources](https://laravel.com/docs/11.x/eloquent-resources)
3. **Postman Learning Center:**  
   * *Writing Post-response Test Scripts*: [https://learning.postman.com/docs/writing-scripts/test-scripts/](https://learning.postman.com/docs/writing-scripts/test-scripts/)  
   * *Managing Variables and Collections*: [https://learning.postman.com/docs/sending-requests/variables/](https://learning.postman.com/docs/sending-requests/variables/)
4. **Pedoman Operasional Peringatan Dini:**  
   * Balai Besar Wilayah Sungai Ciliwung Cisadane (BBWSCC) & Badan Penanggulangan Bencana Daerah (BPBD) DKI Jakarta — *Kriteria Ambang Batas Siaga Pintu Air*.

---

## 8. Deklarasi Penggunaan AI

Sesuai dengan pedoman integritas dan transparansi akademik di lingkungan perguruan tinggi:

1. **Pemanfaatan AI:**  
   Kecerdasan Buatan (Antigravity AI Assistant dengan model Gemini 3.8 Flash) digunakan sebagai alat bantu (*pair programming assistant*) untuk:
   * Membantu memformulasikan struktur dokumen *API Contract* dan skenario pengujian Postman.
   * Menuliskan skrip *mock router server* mandiri berbasis PHP untuk memverifikasi kesesuaian respon HTTP status code.
   * Memformat dokumentasi markdown agar terstruktur rapi sesuai kaidah penulisan teknis dan rubrik penilaian.
2. **Verifikasi & Validasi Mahasiswa:**  
   Seluruh rancangan logika bisnis bencana (status siaga banjir, Daerah Aliran Sungai Ciliwung, pos pantau Katulampa/Depok/Manggarai), struktur JSON payload, verifikasi eksekusi server lokal, pengujian status code 200, 201, 400, 404, dan 422, serta validasi ketiadaan kredensial/rahasia telah ditinjau, diuji, dan divalidasi secara sadar dan mandiri oleh mahasiswa.
3. **Keamanan & Kredensial:**  
   Tidak ada kredensial pribadi, API key, kata sandi, token otentikasi rahasia, atau cookie yang diekspos ke dalam prompt AI maupun berkas repositori proyek.
