# RiverVigil API — Manajemen Layanan Web (Minggu 2)

Proyek Semester mata kuliah **Manajemen Layanan Web / Web Service Management**:  
**RiverVigil API** — Disaster Early Warning & River Telemetry Management Service.

---

## 📂 Struktur Repositori

```text
.
├── .gitignore
├── LAPORAN_PRAKTIKUM_2.md                   # Laporan Modul Praktikum 2 (Eksplorasi HTTP Dasar)
├── README.md                                # Petunjuk navigasi & pengujian repositori
├── server.php                               # Standalone REST API Prototype Server (PHP)
├── docs/
│   ├── api-contract.md                      # Spesifikasi lengkap REST API Contract RiverVigil
│   ├── minggu-2.md                          # Laporan Tugas 2 (Dokumentasi Eksplorasi HTTP)
│   └── postman/
│       ├── week-02-http.postman_collection.json        # Collection Praktikum 2 (Public Test API)
│       └── rivervigil-api.postman_collection.json      # Collection Proyek Semester RiverVigil
└── postman/                                 # Format Postman Desktop Workspace (v10)
    └── collections/
        ├── Minggu 2 — Eksplorasi HTTP/      # Praktikum 2 Collection
        └── RiverVigil API/                  # Proyek Semester Collection
```

---

## 🚀 Panduan Menjalankan & Verifikasi Pengujian

### 1. Menjalankan Server Lokal RiverVigil API
Gunakan PHP CLI bawaan sistem atau XAMPP:
```powershell
# Jalankan web server mandiri pada port 8000
C:\xampp\php\php.exe -S 127.0.0.1:8000 server.php
# atau jika php sudah ada di PATH:
php -S 127.0.0.1:8000 server.php
```
Server akan aktif melayani request di: `http://127.0.0.1:8000`.

### 2. Mengimpor & Menjalankan Uji di Postman Desktop
1. Buka aplikasi **Postman Desktop**.
2. Klik tombol **Import** (atau tekan `Ctrl + O`).
3. Pilih file `docs/postman/rivervigil-api.postman_collection.json`.
4. Buka collection **RiverVigil API — Minggu 2 Eksplorasi HTTP & Contract**.
5. Klik menu **Run collection** -> **Run RiverVigil API**.
6. Seluruh 17 unit test assertions akan berstatus **PASSED (100%)**.

### 3. Menguji Otomatis via Terminal (Newman / Node.js)
```powershell
npx newman run docs/postman/rivervigil-api.postman_collection.json
```

---

## 📋 Checklist Penilaian Tugas 2

- [x] **Postman collection eksplorasi API tersedia**: `docs/postman/rivervigil-api.postman_collection.json`.
- [x] **API contract dan implementasi konsisten**: Didefinisikan di `docs/api-contract.md` dan diimplementasikan di `server.php`.
- [x] **Success dan error response telah diuji**:
  - Success: `200 OK` (`GET /stations`, `GET /alerts/active`), `201 Created` (`POST /telemetry`).
  - Error: `400 Bad Request` (Malformed JSON), `404 Not Found` (Station Unknown), `422 Unprocessable Content` (Validation Error).
- [x] **Dokumentasi serta Postman collection diperbarui**: `docs/minggu-2.md` mencakup seluruh 8 format wajib.
- [x] **Tidak ada credential atau secret**: Tidak ada API key, token rahasia, cookie, atau password pada seluruh kode dan riwayat git.
