# RiverVigil API — Manajemen Layanan Web (Minggu 3)

Proyek Semester mata kuliah **Manajemen Layanan Web / Web Service Management**:  
**RiverVigil API** — Disaster Early Warning & River Telemetry Management Service.  
**Modul:** Praktikum 3 — Merancang API Contract.

---

## 📂 Struktur Repositori

```text
.
├── .gitignore
├── LAPORAN_PRAKTIKUM_3.md                   # Laporan Utama Praktikum 3 (Merancang API Contract)
├── README.md                                # Petunjuk navigasi & pengujian repositori
├── server.php                               # Standalone REST API Server (PHP CLI)
├── docs/
│   ├── api-contract.md                      # Spesifikasi lengkap REST API Contract v1.1.0
│   ├── minggu-3-praktikum.md                # Dokumentasi & Analisis Praktikum Minggu 3
│   └── postman/
│       ├── week-03-contract.postman_collection.json    # Collection Khusus Praktikum 3
│       └── rivervigil-api.postman_collection.json      # Collection Proyek Semester RiverVigil
└── postman/                                 # Format Postman Desktop Workspace (v10)
    └── collections/
        ├── Praktikum 3 — Merancang API Contract/
        └── RiverVigil API/
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
Server akan aktif melayani request di: `http://127.0.0.1:8000/api/v1`.

### 2. Mengimpor & Menjalankan Uji di Postman Desktop
1. Buka aplikasi **Postman Desktop**.
2. Klik tombol **Import** (atau tekan `Ctrl + O`).
3. Pilih file `docs/postman/week-03-contract.postman_collection.json`.
4. Buka collection **Praktikum 3 — Merancang API Contract (RiverVigil API)**.
5. Klik menu **Run collection** -> **Run RiverVigil API**.
6. Seluruh unit test assertions akan berstatus **PASSED (100%)**.

### 3. Menguji Otomatis via Terminal (PowerShell / Curl)
```powershell
# Eksekusi script pengujian verifikasi menyeluruh
powershell -ExecutionPolicy Bypass -File scratch/run-tests.ps1
```

---

## 📋 Checklist Pemenuhan Praktikum Minggu 3

- [x] **Target praktikum selesai:** Pemetaan kebutuhan domain ke dalam 5 resource REST (`stations`, `telemetry`, `alerts`, `shelters`, `reports`).
- [x] **Success dan error response telah diuji:**
  - Success: `200 OK` (`GET /stations`, `GET /alerts/active`, `GET /shelters`, `PATCH /alerts/{id}/resolve`), `201 Created` (`POST /stations`, `POST /telemetry`, `POST /reports`).
  - Error: `400 Bad Request` (Malformed JSON), `404 Not Found` (Station Unknown), `422 Unprocessable Content` (Validation Error).
- [x] **Postman collection diperbarui:** Tersedia di `docs/postman/week-03-contract.postman_collection.json` dan `docs/postman/rivervigil-api.postman_collection.json`.
- [x] **Dokumentasi minggu 3 tersedia:** Dicatat pada `docs/minggu-3-praktikum.md` dan spesifikasi teknis di `docs/api-contract.md`.
- [x] **Repository tidak mengandung credential atau secret:** Kebijakan zero-secret ditaati pada seluruh file dan riwayat git.
