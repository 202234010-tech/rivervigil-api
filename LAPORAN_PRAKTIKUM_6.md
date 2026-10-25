# Laporan Praktikum 6 — Endpoint Collection yang Dapat Dicari

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Modul:** Praktikum 6 — Endpoint Collection yang Dapat Dicari (Search, Sorting, dan Pagination)  
**Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
**Resource Domain:** `Book` (`books`)  
**Versi API:** `v1.3.0`  
**Base URL:** `http://127.0.0.1:8000/api`  
**Repositori GitHub:** [https://github.com/202234010-tech/rivervigil-api](https://github.com/202234010-tech/rivervigil-api)  
**Tautan Commit Terkait:** [Commit `80301c9`](https://github.com/202234010-tech/rivervigil-api/commit/80301c9)  
**Waktu Praktikum:** 120 menit  

---

## 1. Ringkasan & Tujuan Praktikum

Pada Praktikum 6 ini, mahasiswa melanjutkan Book CRUD dari Minggu 5 menjadi collection endpoint yang mendukung:
1. **Paginasi Dinamis (*Pagination*):** Mendukung parameter `page` dan `per_page` (default: 5 item, maksimal: 50 item) untuk membatasi ukuran transfer data dan mengoptimalkan beban server serta client.
2. **Pencarian Parsial (*Search*):** Mendukung pencarian teks pada field `title` dan `isbn` menggunakan klausa Eloquent `where(function ($builder) { ... })` dengan operator SQL `LIKE %...%`.
3. **Pengurutan Terarah dengan Allowlist (*Sorting with Allowlist*):** Membatasi field sorting hanya pada kolom yang diizinkan (`id`, `title`, `isbn`, `created_at`) dengan direction `asc` atau `desc` (default: `id asc`), guna mencegah SQL Injection, error 500 basis data, dan *information disclosure*.
4. **Validasi Query Parameter:** Menjalankan validasi ketat pada seluruh parameter kueri (`$request->validate(...)`) sebelum query basis data dibangun, mengembalikan respon terstruktur `422 Unprocessable Content` jika parameter tidak valid.
5. **Format Respons Standar Terpaginasi:** Mengembalikan payload seragam menggunakan `BookResource::collection($books)` yang menyertakan envelope `data`, tautan hipermedia navigasi `links` (*HATEOAS*), dan metadata paginasi `meta`.
6. **Preservasi Query String:** Menerapkan method chaining `->withQueryString()` pada paginator Eloquent agar seluruh filter pencarian dan sorting tetap dipertahankan pada setiap tautan navigasi halaman.

---

## 2. Struktur Komponen yang Dihasilkan / Diperbarui

| Komponen | Path File | Status | Peran & Deskripsi |
|---|---|---|---|
| **Controller** | `app/Http/Controllers/BookController.php` | Diperbarui | Menambahkan import `Request` dan `Rule`, serta merefaktor method `index()` dengan alur: `validate` -> `filter` -> `sort` -> `paginate` -> `withQueryString` -> `BookResource::collection`. |
| **Laporan Modul** | `docs/minggu-6-praktikum.md` | Baru | Dokumen laporan resmi sesuai format modul praktikum (Query Contract, Verification, Response Structure, Decision, dan AI Usage). |
| **Laporan Praktikum** | `LAPORAN_PRAKTIKUM_6.md` | Baru | Dokumen laporan praktikum komprehensif tingkat proyek semester. |
| **Postman Collection** | `docs/postman/week-06-pagination-filtering-sorting.postman_collection.json` | Baru | Koleksi Postman dengan 10 request pengujian otomatis, assertions script, dan parameter query. |

---

## 3. Implementasi Kode Utama

File: [`app/Http/Controllers/BookController.php`](file:///d:/SMT9/MNJ%20WEB%20SERV/P6/app/Http/Controllers/BookController.php)

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in(['id', 'title', 'isbn', 'created_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Book::query();

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? 5;

        $books = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }

    // Method store, show, update, destroy, and notFound tetap dipertahankan dari Minggu 5
```

---

## 4. Query Contract & Spesifikasi Parameter

| Parameter | Tipe Data | Aturan Validasi | Default | Keterangan |
|---|---|---|---|---|
| `search` | String | `sometimes`, `nullable`, `string`, `max:100` | `null` | Mencari substring pada kolom `title` atau `isbn` (case-insensitive). |
| `sort` | String | `sometimes`, `in:id,title,isbn,created_at` | `id` | Memilih kolom pengurutan (dibatasi oleh allowlist). |
| `direction` | String | `sometimes`, `in:asc,desc` | `asc` | Menentukan arah pengurutan (`asc` = menaik, `desc` = menurun). |
| `page` | Integer | `sometimes`, `integer`, `min:1` | `1` | Nomor halaman yang diminta. |
| `per_page` | Integer | `sometimes`, `integer`, `min:1`, `max:50` | `5` | Jumlah item yang ditampilkan per halaman. |

---

## 5. Hasil Verifikasi & Testing Empiris

Seluruh pengujian dijalankan langsung terhadap server pengembangan aktif (`http://127.0.0.1:8000`) dengan data riil pada basis data SQLite (memiliki 4 buku unik).

| Skenario Pengujian | Request URI & Header | Status Code | Respon Aktual / Indikator Keberhasilan |
|---|---|:---:|---|
| **Pagination Page 1** | `GET /api/books?per_page=2&page=1`<br>`Accept: application/json` | `200 OK` | Mengembalikan 2 buku pertama (`id: 1` dan `id: 2`), `meta.current_page: 1`, `meta.last_page: 2`, `links.next` tersedia, `links.prev: null`. |
| **Pagination Page 2** | `GET /api/books?per_page=2&page=2`<br>`Accept: application/json` | `200 OK` | Mengembalikan 2 buku berikutnya (`id: 4` dan `id: 5`), `meta.current_page: 2`, `links.prev` mengarah ke page 1, `links.next: null`. |
| **Search Ditemukan (Title)** | `GET /api/books?search=Clean`<br>`Accept: application/json` | `200 OK` | Mengembalikan 1 buku yang cocok (*Clean Code...*, `id: 1`), `meta.total: 1`. |
| **Search Ditemukan (ISBN)** | `GET /api/books?search=9780321`<br>`Accept: application/json` | `200 OK` | Mengembalikan 1 buku yang cocok (*Domain-Driven Design*, `id: 4`), `meta.total: 1`. |
| **Search Tanpa Hasil** | `GET /api/books?search=tidak-ada-987654321`<br>`Accept: application/json` | `200 OK` | Mengembalikan `data: []`, `links` navigasi tetap ada, `meta.total: 0` (bukan error 404). |
| **Sorting Descending** | `GET /api/books?sort=title&direction=desc&per_page=10`<br>`Accept: application/json` | `200 OK` | Data terurut dari Z ke A: (1) *Working...* -> (2) *The Pragmatic...* -> (3) *Domain...* -> (4) *Clean...*. |
| **Sorting Ascending** | `GET /api/books?sort=title&direction=asc&per_page=10`<br>`Accept: application/json` | `200 OK` | Data terurut dari A ke Z: (1) *Clean...* -> (2) *Domain...* -> (3) *The Pragmatic...* -> (4) *Working...*. |
| **Invalid Sort (422)** | `GET /api/books?sort=password`<br>`Accept: application/json` | `422 Unprocessable` | Validasi gagal dengan JSON: `{"message":"The selected sort is invalid.","errors":{"sort":["The selected sort is invalid."]}}`. |
| **Invalid Per Page (422)** | `GET /api/books?per_page=100`<br>`Accept: application/json` | `422 Unprocessable` | Validasi gagal dengan JSON: `{"message":"The per page field must not be greater than 50.","errors":{"per_page":["The per page field must not be greater than 50."]}}`. |
| **Kombinasi Parameter** | `GET /api/books?search=Code&sort=title&direction=asc&per_page=2&page=1`<br>`Accept: application/json` | `200 OK` | Hasil terfilter, terurut, dan terpaginasi. Query string dipertahankan pada `links.first`, `links.last`, dsb. |

---

## 6. Struktur Response JSON (Data Envelope, Links, dan Meta)

Format respons paginasi tersusun atas tiga bagian utama:
1. **`data`**: Array objek data buku yang telah ditransformasikan melalui `BookResource`, menyajikan field publik (`id`, `title`, `isbn`, `available`, `created_at`) secara konsisten.
2. **`links`**: Objek navigasi *HATEOAS* dasar (`first`, `last`, `prev`, `next`) yang mempertahankan parameter filter dan pencarian melalui chaining method `->withQueryString()`.
3. **`meta`**: Objek metadata paginasi lengkap (`current_page`, `from`, `to`, `last_page`, `per_page`, `total`, `path`, dan elemen array navigasi `links` dengan status `active`).

---

## 7. Analisis Keputusan Desain (Sort Allowlist)

Penerapan allowlist pada parameter sorting didasari oleh empat alasan rekayasa perangkat lunak:
1. **Pencegahan SQL Injection & Parameter Tampering:** Menghindari manipulasi klausa `ORDER BY` dari masukan eksternal yang tidak disanitasi.
2. **Proteksi Informasi Sensitif (*Information Disclosure*):** Menjamin nama kolom internal atau kolom privat tidak dapat diurutkan atau ditebak oleh client luar (*blind sorting attack*).
3. **Mencegah Unhandled Database Exception (HTTP 500):** Menolak nama kolom yang tidak ada pada basis data di lapisan validasi HTTP dengan status `422 Unprocessable Content`, alih-alih memicu crash `QueryException` berstatus 500.
4. **Optimasi Performa Query:** Membatasi operasi pengurutan hanya pada kolom-kolom yang telah dirancang untuk pengindeksan (*database index*).

---

## 8. Referensi & Deklarasi AI

- **Deklarasi Penggunaan AI:** Praktikum ini dikembangkan dengan asistensi Antigravity AI Assistant (Google DeepMind) untuk penyesuaian controller, refactoring method `index()`, pembuatan koleksi pengujian Postman, serta penyusunan laporan verifikasi aktual. Seluruh implementasi telah diuji secara menyeluruh melalui live HTTP client, linter PHP, dan automated test PHPUnit.
- **Referensi:**
  1. Laravel Official Documentation: [Pagination](https://laravel.com/docs/12.x/pagination), [Eloquent API Resources](https://laravel.com/docs/12.x/eloquent-resources), [Validation](https://laravel.com/docs/12.x/validation), [Queries](https://laravel.com/docs/12.x/queries).
  2. Modul Pembelajaran Manajemen Layanan Web 2026: *Praktikum 6 — Endpoint Collection yang Dapat Dicari*.
