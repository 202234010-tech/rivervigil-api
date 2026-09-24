# Laporan Praktikum 2 — Eksplorasi HTTP dengan Postman

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Modul:** Praktikum 2 — Eksplorasi HTTP dengan Postman  
**Format File Export:** [week-02-http.postman_collection.json](docs/postman/week-02-http.postman_collection.json)  

---

## 1. Ringkasan & Tujuan Praktikum

Setelah menyelesaikan praktikum ini, mahasiswa mampu:
1. Membuat dan menyusun **Postman Collection** terstruktur untuk eksperimen HTTP.
2. Mengirim request dengan **Query Parameter** dan **Custom Header**.
3. Membaca komponen response: **HTTP Status Code**, **Response Headers**, **Response Time**, dan **JSON Body**.
4. Menulis dan mengeksekusi **Post-response Scripts (Unit Testing)** menggunakan library bawaan Postman (`pm.test` dan `pm.expect`).
5. Menganalisis perbedaan flow response sukses dan gagal (status 200, 400, 404, 429).
6. Mengamankan kredensial dan mengekspor artefak collection tanpa menyertakan secret.

---

## 2. Struktur Collection & Variabel

### 2.1 Struktur Folder
Postman Collection diberi nama: **`Minggu 2 — Eksplorasi HTTP`** dengan hierarki:

```text
Minggu 2 — Eksplorasi HTTP
├── 01 Request Anatomy
│   ├── GET Echo Query Parameters
│   └── GET Echo Custom Header
└── 02 Status Codes
    ├── Success
    ├── Bad Request
    ├── Not Found
    └── Too Many Requests
```

### 2.2 Collection Variables (Checkpoint 1)
Variabel didefinisikan pada tingkat Collection sehingga dapat diwariskan ke seluruh request tanpa menyimpan kredensial:

| Variable Name | Initial Value | Current Value | Fungsi |
|---|---|---|---|
| `echo_base_url` | `https://postman-echo.com` | `https://postman-echo.com` | Endpoint echo untuk pengujian parameter & header |
| `status_base_url` | `https://httpbin.org` | `https://httpbin.org` | Endpoint simulasi beragam HTTP status code |

> **Checkpoint 1 Lulus:** Collection memiliki dua folder (`01 Request Anatomy` & `02 Status Codes`) dan dua base URL tanpa credential/secret.

---

## 3. Detail Eksplorasi HTTP Request & Response

### 3.1 Bagian 2 — Query Parameter
* **Folder:** `01 Request Anatomy`
* **Request Name:** `GET Echo Query Parameters`
* **Method:** `GET`
* **URL:** `{{echo_base_url}}/get?search=laravel api&page=2`
* **Params:**
  * `search`: `laravel api`
  * `page`: `2`
* **Analisis:**
  * Server mengembalikan data query params di dalam object `args`.
  * Spasi pada nilai `search` secara otomatis di-encode menjadi format URL percent-encoding (`laravel%20api` atau `laravel+api`).
  * Nilai `page` diterima oleh server sebagai string `"2"`, bukan integer numerik.

**Contoh Response Body:**
```json
{
  "args": {
    "page": "2",
    "search": "laravel api"
  },
  "headers": {
    "host": "postman-echo.com",
    "x-forwarded-proto": "https"
  },
  "url": "https://postman-echo.com/get?search=laravel%20api&page=2"
}
```

---

### 3.2 Bagian 3 — Request Custom Header & Post-Response Tests (Checkpoint 2)
* **Folder:** `01 Request Anatomy`
* **Request Name:** `GET Echo Custom Header`
* **Method:** `GET`
* **URL:** `{{echo_base_url}}/get?search=laravel api&page=2`
* **Headers:**
  * `Accept`: `application/json`
  * `X-Student-Client`: `postman-week-2`
* **Post-response Scripts (Tests):**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response is JSON", function () {
    pm.response.to.be.json;
});

pm.test("Custom header was received", function () {
    const body = pm.response.json();
    pm.expect(body.headers["x-student-client"]).to.eql("postman-week-2");
});
```

* **Hasil Eksekusi Test:**
  * `PASS` Status code is 200
  * `PASS` Response is JSON
  * `PASS` Custom header was received

> **Catatan:** Echo API menormalisasi nama header menjadi huruf kecil (*lowercase*) pada object JSON respons (`headers["x-student-client"]`).

---

### 3.3 Bagian 4 & 5 — Status Code & Observasi (Checkpoint 3)

Endpoint `https://httpbin.org/status/:code` digunakan untuk mensimulasikan respons server terhadap berbagai skenario kode status.

#### Tabel Observasi Utama (Checkpoint 3)

| Request | Endpoint | Expected Status | Status Aktual | Body Response | Makna untuk Client | Tindakan Client |
|---|---|---|---|---|---|---|
| **Success** | `GET {{status_base_url}}/status/200` | `200` | `200 OK` | *Kosong (0 bytes)* | Request berhasil diproses secara valid oleh server. | Lanjut membaca hasil / memproses data selanjutnya. |
| **Bad Request** | `GET {{status_base_url}}/status/400` | `400` | `400 Bad Request` | *Kosong (0 bytes)* | Sintaks atau format request tidak valid / parameter wajib salah. | Periksa format request, payload, tipe data sebelum mengirim ulang. |
| **Not Found** | `GET {{status_base_url}}/status/404` | `404` | `404 Not Found` | *Kosong (0 bytes)* | Endpoint / resource yang dituju tidak ditemukan di server. | Tampilkan pesan resource tidak ditemukan; periksa URL/path. |
| **Too Many Requests** | `GET {{status_base_url}}/status/429` | `429` | `429 Too Many Requests` | *Kosong (0 bytes)* | Client telah melampaui kuota frekuensi request (rate limit). | Tunda request dan coba kembali sesuai jeda policy / header `Retry-After`. |

#### Post-Response Tests untuk Setiap Status Code:
* **Success:**
  ```javascript
  pm.test("Status code is 200", function () {
      pm.response.to.have.status(200);
  });
  ```
* **Bad Request:**
  ```javascript
  pm.test("Status code is 400", function () {
      pm.response.to.have.status(400);
  });
  ```
* **Not Found:**
  ```javascript
  pm.test("Status code is 404", function () {
      pm.response.to.have.status(404);
  });
  ```
* **Too Many Requests:**
  ```javascript
  pm.test("Status code is 429", function () {
      pm.response.to.have.status(429);
  });
  ```

---

## 4. Analisis Komponen Respons HTTP (Bagian 5)

Berikut adalah rekapitulasi pembacaan komponen teknis response dari masing-masing request:

| No | Request Name | Status Code | Content-Type | Ketersediaan Body | Makna & Tindakan Client |
|---|---|---|---|---|---|
| 1 | **GET Echo Query Parameters** | `200 OK` | `application/json` | Tersedia (`args`, `headers`, `url`) | Request parameter dibaca sukses. Client mengolah object `args`. |
| 2 | **GET Echo Custom Header** | `200 OK` | `application/json` | Tersedia (memuat `x-student-client`) | Header divalidasi sukses oleh test suite. Client melanjutkan session. |
| 3 | **Success** | `200 OK` | `text/html; charset=utf-8` | Tidak ada (0 B) | Server mengonfirmasi sukses tanpa body tambahan. |
| 4 | **Bad Request** | `400 Bad Request` | `text/html; charset=utf-8` | Tidak ada (0 B) | Request ditolak. Jangan kirim ulang sebelum memperbaiki parameter. |
| 5 | **Not Found** | `404 Not Found` | `text/html; charset=utf-8` | Tidak ada (0 B) | URI target tidak ada. Tampilkan error 404 pada antarmuka pengguna. |
| 6 | **Too Many Requests** | `429 Too Many Requests` | `text/html; charset=utf-8` | Tidak ada (0 B) | Rate limit tercapai. Hentikan request bertubi-tubi dan gunakan backoff delay. |

> **Penting:** Tidak semua response tanpa body mengindikasikan bug aplikasi. Dalam standar HTTP, respons seperti `204 No Content`, `304 Not Modified`, atau endpoint status test memang sengaja tidak mengembalikan payload body.

---

## 5. Menjalankan Collection Runner (Bagian 6)

Langkah menjalankan automated test seluruh collection:
1. Buka collection **`Minggu 2 — Eksplorasi HTTP`** di Postman Desktop.
2. Klik tombol **Run collection** (ikon play di panel collection).
3. Pastikan ke-6 request tercentang:
   * `GET Echo Query Parameters`
   * `GET Echo Custom Header` (3 tests)
   * `Success` (1 test)
   * `Bad Request` (1 test)
   * `Not Found` (1 test)
   * `Too Many Requests` (1 test)
4. Konfigurasi:
   * **Iterations:** `1`
   * **Delay:** `0 ms`
5. Klik **Run Minggu 2 — Eksplorasi HTTP**.
6. **Hasil:** Seluruh 7 unit test berstatus **PASSED (100% Pass Rate)** tanpa kegagalan.

---

## 6. Jawaban Pertanyaan Refleksi

### 1. Mengapa query parameter `page=2` diterima sebagai string?
**Jawaban:**  
Protokol HTTP mengirimkan URL dan query string sebagai aliran karakter teks (ASCII/UTF-8 string). URL tidak memiliki sistem tipe data (*type system*) seperti bahasa pemrograman (misalnya integer, boolean, float). Ketika web server atau API gateway mem-parsing URI request, seluruh pasangan *key-value* di belakang tanda tanya (`?`) dibaca secara literal sebagai string (`"2"`). Konversi menjadi tipe numerik (*type casting/coercion*) merupakan tanggung jawab kode aplikasi backend (seperti validasi skema atau `intval($_GET['page'])`).

---

### 2. Mengapa client perlu membaca status code sebelum body?
**Jawaban:**  
1. **Validasi Kesuksesan & Integritas Payload:** HTTP Status Code merepresentasikan hasil operasi secara definitif (2xx = Sukses, 4xx = Kesalahan Klien, 5xx = Kesalahan Server). Membaca status code mencegah client langsung mencoba mem-parsing body yang mungkin tidak berbentuk format yang diharapkan (misal: mem-parse halaman HTML error 500 sebagai JSON yang akan menyebabkan *crash/JSON parse exception*).
2. **Efisiensi Kontrol Alur (Control Flow):** Jika status code menunjukkan error atau pengalihan (redirect), client dapat langsung mengeksekusi penanganan kesalahan (*error handler*), logika otentikasi ulang (401), atau jeda rate limit (429) tanpa membuang siklus komputasi untuk membaca keseluruhan body.

---

### 3. Apa perbedaan error 400 dan 404?
**Jawaban:**  
* **400 Bad Request:** Server mengenali endpoint yang dituju, namun **menolak memproses request** karena format atau sintaks yang dikirim oleh client tidak valid (misal: JSON rusak/malformed, parameter wajib tidak dikirim, atau tipe data payload tidak sesuai skema).
* **404 Not Found:** Server tidak menemukan **alamat URL / resource** yang diminta (misal: salah penulisan rute URI, ID entitas tidak ada dalam database, atau rute belum didefinisikan pada routing aplikasi).

---

### 4. Apa tindakan client setelah menerima status 429?
**Jawaban:**  
1. **Hentikan Request Segera:** Jangan mengulang request yang sama secara terus-menerus karena dapat memicu pemblokiran IP permanen oleh firewall/API gateway.
2. **Periksa Header `Retry-After`:** Baca header HTTP `Retry-After` dari respons (jika disediakan oleh server) untuk mengetahui durasi tunggu pasti (dalam detik atau stempel waktu).
3. **Terapkan Algoritma Exponential Backoff:** Jika header tidak tersedia, terapkan jeda waktu tunggu yang bertambah secara eksponensial dengan *jitter* acak sebelum mencoba kembali.
4. **Beri Notifikasi Pengguna:** Jika request dipicu oleh aksi pengguna di antarmuka (UI), tampilkan pesan informatif bahwa server sedang membatasi permintaan dan minta pengguna menunggu sejenak.

---

### 5. Apa manfaat menyimpan request dalam Collection?
**Jawaban:**  
1. **Organisasi & Struktur:** Endpoint API terdokumentasi dan terkelompok secara rapi per modul, fitur, atau versi.
2. **Reusabilitas:** Menghilangkan keharusan mengetik ulang URL, query params, headers, dan payload body setiap kali melakukan pengujian.
3. **Variabel Dinamis:** Mempermudah pergantian environment (Local, Staging, Production) melalui *Collection/Environment Variables*.
4. **Otomatisasi Testing (Collection Runner / Newman):** Memungkinkan pengujian otomatis (*regression test suite*) seluruh API dalam satu klik atau terintegrasi ke CI/CD pipeline.
5. **Dokumentasi & Kolaborasi Tim:** Collection dapat diekspor sebagai file JSON standar (v2.1) untuk di-*commit* ke Git repository atau dibagikan ke anggota tim developer lainnya.

---

## 7. Prosedur Git & Version Control (Bagian 7)

File collection telah diekspor dan disimpan di path:  
`docs/postman/week-02-http.postman_collection.json`

Untuk melakukan version control menggunakan Git melalui terminal Antigravity IDE:

```bash
# 1. Pastikan repositori git telah diinisialisasi (jika baru pertama kali)
git init

# 2. Periksa status perubahan berkas
git status

# 3. Tambahkan file collection ke staging area
git add docs/postman/week-02-http.postman_collection.json

# 4. Lakukan commit dengan pesan terstandar
git commit -m "docs: add week 2 HTTP collection"
```

---

## 8. Verifikasi Checklist

- [x] Collection **Minggu 2 — Eksplorasi HTTP** dan folder `01 Request Anatomy` serta `02 Status Codes` dibuat.
- [x] Collection variables (`echo_base_url` & `status_base_url`) disimpan.
- [x] Query parameter (`search` & `page`) diuji pada endpoint Echo.
- [x] Custom header (`Accept` & `X-Student-Client`) diuji.
- [x] Post-response tests (`pm.test`) pada Echo dan Status Codes lulus.
- [x] Status `200`, `400`, `404`, dan `429` dibandingkan dan didokumentasikan dalam tabel observasi.
- [x] Collection Runner berhasil dijalankan dengan kelulusan 100%.
- [x] Collection diekspor ke `docs/postman/week-02-http.postman_collection.json` (format Collection v2.1).
- [x] Tidak ada credential, token, atau secret pribadi pada file atau screenshot.
