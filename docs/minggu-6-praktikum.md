# Praktikum Minggu 6 — Search, Sorting, dan Pagination

## Query Contract
- search: title atau isbn; maksimal 100 karakter
- sort: id, title, isbn, atau created_at
- direction: asc atau desc
- page: integer minimal 1
- per_page: integer 1–50; default 5

## Verification
- pagination 200:
  - Request: `GET http://127.0.0.1:8000/api/books?per_page=2&page=1`
  - Header: `Accept: application/json`
  - Status: `200 OK`
  - Actual Response:
    ```json
    {
      "data": [
        {
          "id": 1,
          "title": "Clean Code: A Handbook of Agile Software Craftsmanship",
          "isbn": "9780132350884",
          "available": true,
          "created_at": "2026-10-12T05:44:50.000000Z"
        },
        {
          "id": 2,
          "title": "The Pragmatic Programmer: Your Journey to Mastery",
          "isbn": "9780201616224",
          "available": true,
          "created_at": "2026-10-12T05:44:50.000000Z"
        }
      ],
      "links": {
        "first": "http://127.0.0.1:8000/api/books?per_page=2&page=1",
        "last": "http://127.0.0.1:8000/api/books?per_page=2&page=2",
        "prev": null,
        "next": "http://127.0.0.1:8000/api/books?per_page=2&page=2"
      },
      "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 2,
        "links": [
          {
            "url": null,
            "label": "&laquo; Previous",
            "page": null,
            "active": false
          },
          {
            "url": "http://127.0.0.1:8000/api/books?per_page=2&page=1",
            "label": "1",
            "page": 1,
            "active": true
          },
          {
            "url": "http://127.0.0.1:8000/api/books?per_page=2&page=2",
            "label": "2",
            "page": 2,
            "active": false
          },
          {
            "url": "http://127.0.0.1:8000/api/books?per_page=2&page=2",
            "label": "Next &raquo;",
            "page": 2,
            "active": false
          }
        ],
        "path": "http://127.0.0.1:8000/api/books",
        "per_page": 2,
        "to": 2,
        "total": 4
      }
    }
    ```
  - Request: `GET http://127.0.0.1:8000/api/books?per_page=2&page=2`
  - Header: `Accept: application/json`
  - Status: `200 OK`
  - Actual Response: Mengembalikan item halaman 2 dengan `id: 4` ("Domain-Driven Design") dan `id: 5` ("Working Effectively"), `meta.current_page: 2`, `links.prev` terisi URL halaman 1, dan `links.next: null`.

- search ditemukan 200:
  - Request: `GET http://127.0.0.1:8000/api/books?search=Clean`
  - Header: `Accept: application/json`
  - Status: `200 OK`
  - Actual Response:
    ```json
    {
      "data": [
        {
          "id": 1,
          "title": "Clean Code: A Handbook of Agile Software Craftsmanship",
          "isbn": "9780132350884",
          "available": true,
          "created_at": "2026-10-12T05:44:50.000000Z"
        }
      ],
      "links": {
        "first": "http://127.0.0.1:8000/api/books?search=Clean&page=1",
        "last": "http://127.0.0.1:8000/api/books?search=Clean&page=1",
        "prev": null,
        "next": null
      },
      "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "links": [
          {
            "url": null,
            "label": "&laquo; Previous",
            "page": null,
            "active": false
          },
          {
            "url": "http://127.0.0.1:8000/api/books?search=Clean&page=1",
            "label": "1",
            "page": 1,
            "active": true
          },
          {
            "url": null,
            "label": "Next &raquo;",
            "page": null,
            "active": false
          }
        ],
        "path": "http://127.0.0.1:8000/api/books",
        "per_page": 5,
        "to": 1,
        "total": 1
      }
    }
    ```
  - Catatan Pencarian ISBN: Request `GET http://127.0.0.1:8000/api/books?search=9780321` juga berhasil mengembalikan buku `id: 4` ("Domain-Driven Design", `isbn: 9780321125217`).

- search kosong 200:
  - Request: `GET http://127.0.0.1:8000/api/books?search=tidak-ada-987654321`
  - Header: `Accept: application/json`
  - Status: `200 OK`
  - Actual Response:
    ```json
    {
      "data": [],
      "links": {
        "first": "http://127.0.0.1:8000/api/books?search=tidak-ada-987654321&page=1",
        "last": "http://127.0.0.1:8000/api/books?search=tidak-ada-987654321&page=1",
        "prev": null,
        "next": null
      },
      "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "links": [
          {
            "url": null,
            "label": "&laquo; Previous",
            "page": null,
            "active": false
          },
          {
            "url": "http://127.0.0.1:8000/api/books?search=tidak-ada-987654321&page=1",
            "label": "1",
            "page": 1,
            "active": true
          },
          {
            "url": null,
            "label": "Next &raquo;",
            "page": null,
            "active": false
          }
        ],
        "path": "http://127.0.0.1:8000/api/books",
        "per_page": 5,
        "to": null,
        "total": 0
      }
    }
    ```

- sorting asc/desc 200:
  - Request Descending: `GET http://127.0.0.1:8000/api/books?sort=title&direction=desc&per_page=10`
  - Header: `Accept: application/json`
  - Status: `200 OK`
  - Actual Result (Z to A):
    1. Working Effectively (`id: 5`)
    2. The Pragmatic Programmer: Your Journey to Mastery (`id: 2`)
    3. Domain-Driven Design (`id: 4`)
    4. Clean Code: A Handbook of Agile Software Craftsmanship (`id: 1`)
  - Request Ascending: `GET http://127.0.0.1:8000/api/books?sort=title&direction=asc&per_page=10`
  - Header: `Accept: application/json`
  - Status: `200 OK`
  - Actual Result (A to Z):
    1. Clean Code: A Handbook of Agile Software Craftsmanship (`id: 1`)
    2. Domain-Driven Design (`id: 4`)
    3. The Pragmatic Programmer: Your Journey to Mastery (`id: 2`)
    4. Working Effectively (`id: 5`)

- invalid sort 422:
  - Request: `GET http://127.0.0.1:8000/api/books?sort=password`
  - Header: `Accept: application/json`
  - Status: `422 Unprocessable Content`
  - Actual Response:
    ```json
    {
      "message": "The selected sort is invalid.",
      "errors": {
        "sort": [
          "The selected sort is invalid."
        ]
      }
    }
    ```

- invalid per_page 422:
  - Request: `GET http://127.0.0.1:8000/api/books?per_page=100`
  - Header: `Accept: application/json`
  - Status: `422 Unprocessable Content`
  - Actual Response:
    ```json
    {
      "message": "The per page field must not be greater than 50.",
      "errors": {
        "per_page": [
          "The per page field must not be greater than 50."
        ]
      }
    }
    ```

## Response Structure
Jelaskan fungsi data, links, dan meta:
- **data**: Berupa array of objects yang membungkus kumpulan record entitas buku yang berhasil diambil dan ditransformasikan oleh Eloquent API Resource (`BookResource`). Menyediakan format *uniform envelope* yang menyembunyikan representasi internal basis data dan hanya mengekspos field publik (`id`, `title`, `isbn`, `available`, `created_at`). Apabila query tidak menghasilkan data, `data` bernilai array kosong `[]`.
- **links**: Objek navigasi hipermedia (*HATEOAS*) yang menyediakan URL langsung untuk navigasi halaman:
  - `first`: Tautan menuju halaman pertama.
  - `last`: Tautan menuju halaman terakhir.
  - `prev`: Tautan menuju halaman sebelumnya (`null` jika berada pada halaman 1).
  - `next`: Tautan menuju halaman berikutnya (`null` jika berada pada halaman terakhir).
  Seluruh query parameter (`search`, `sort`, `direction`, `per_page`) otomatis dipertahankan pada setiap URL tautan melalui method chaining `->withQueryString()`.
- **meta**: Objek informasi metadata paginasi yang menjelaskan konteks data pada response saat ini:
  - `current_page`: Indeks nomor halaman aktif (berbasis 1).
  - `from` dan `to`: Indeks nomor rekaman awal dan akhir yang sedang ditampilkan (bernilai `null` bila data kosong).
  - `last_page`: Total jumlah halaman yang tersedia.
  - `per_page`: Jumlah kuota item per halaman yang sedang diterapkan (default 5, maksimal 50).
  - `total`: Total keseluruhan rekaman yang cocok dengan kriteria filter di seluruh basis data.
  - `path`: URL dasar rute endpoint API.
  - `links`: Kumpulan array elemen tombol paginasi lengkap dengan status `active` dan label untuk mempermudah render UI pagination controls pada aplikasi front-end.

## Decision
Jelaskan mengapa sort menggunakan allowlist:
1. **Pencegahan SQL Injection & Parameter Tampering:** Klausa `ORDER BY` pada SQL berisiko tinggi terhadap eksploitasi jika menerima masukan pengguna secara langsung tanpa sanitasi. Validasi allowlist melalui `Rule::in(['id', 'title', 'isbn', 'created_at'])` memastikan hanya kolom yang terdaftar secara sah yang dapat diteruskan ke Eloquent Query Builder.
2. **Pencegahan Kebocoran Struktur Kolom Sensitif (*Information Disclosure*):** Tanpa allowlist, penyerang dapat mencoba mengurutkan data berdasarkan nama kolom internal yang tidak seharusnya diketahui publik (seperti hash token, encryption keys, atau status internal), yang dapat dimanfaatkan untuk inferensi data (*blind sorting/timing attack*). Allowlist menjamin hanya atribut publik yang dapat diurutkan.
3. **Mencegah Unhandled Database Exception (HTTP 500):** Jika client mengirimkan nama kolom yang tidak ada pada tabel basis data (misalnya `sort=password`), database driver akan melemparkan `QueryException` (*Column not found*), menyebabkan server mengembalikan error 500 Internal Server Error yang tidak bersahabat. Dengan allowlist, input yang tidak sah langsung diintersepsi pada lapisan validasi HTTP dan dikembalikan sebagai HTTP 422 Unprocessable Content yang jelas dan informatif.
4. **Menjaga Kestabilan Performa Basis Data:** Allowlist membatasi pengurutan hanya pada kolom-kolom yang telah diantisipasi atau memiliki indeks basis data, mencegah kueri berat yang memicu *unindexed filesort* pada basis data besar.

## AI Usage
Catat alat, prompt, hasil yang digunakan, dan cara memeriksanya:
- **Alat:** Antigravity AI Assistant (Google DeepMind).
- **Prompt:** Menjalankan instruksi modul Praktikum Minggu 6 — Endpoint Collection yang Dapat Dicari untuk:
  1. Menambahkan import `Request` dan `Rule` pada `BookController`.
  2. Memperbarui method `index()` dengan urutan: `validate` -> `filter (where search)` -> `sort (orderBy allowlist)` -> `paginate` -> `withQueryString` -> `BookResource::collection`.
  3. Menguji fungsionalitas live HTTP endpoint dan menyusun dokumentasi berbasis hasil aktual.
  4. Menyiapkan staged commit git yang bersih tanpa file credential/secret.
- **Hasil yang Digunakan:**
  - Penulisan import `use Illuminate\Http\Request;` dan `use Illuminate\Validation\Rule;` pada `app/Http/Controllers/BookController.php`.
  - Kode logika method `index()` yang tervalidasi dan mendukung pencarian pada `title` dan `isbn`, pengurutan allowlist dengan arah `asc`/`desc`, serta paginasi dinamis maksimal 50 item per halaman.
  - Penyusunan berkas Postman Collection `docs/postman/week-06-pagination-filtering-sorting.postman_collection.json` untuk seluruh 10 skenario pengujian.
  - Penyusunan dokumentasi verifikasi aktual pada `docs/minggu-6-praktikum.md`.
- **Cara Memeriksanya:**
  - **PHP Linter:** Menjalankan `php -l app/Http/Controllers/BookController.php` untuk memastikan tidak ada kesalahan sintaks (`No syntax errors detected`).
  - **Route Checker:** Memeriksa ketersediaan dan integritas route via `php artisan route:list --path=api/books`.
  - **Automated Tests:** Menjalankan `php artisan test` dengan hasil 24 tests passed dan 129 assertions lulus.
  - **Live HTTP Request Testing:** Mengirimkan HTTP request empiris menggunakan `curl` dan `Invoke-RestMethod` dengan header `Accept: application/json` ke server pengembangan lokal (`http://127.0.0.1:8000`) untuk memvalidasi:
    - Status code 200 OK dengan wrapper `data`, `links`, dan `meta` pada pagination `page=1` dan `page=2`.
    - Filter pencarian pada `title` dan `isbn` menghasilkan data yang cocok.
    - Pencarian tanpa hasil mengembalikan status 200 OK dengan `data: []` dan `meta.total: 0`.
    - Pengurutan `title desc` dan `title asc` membalik urutan data secara akurat.
    - Status code 422 Unprocessable Content pada sort tidak valid (`sort=password`) dan per_page melebihi batas (`per_page=100`).
    - Kombinasi parameter query tetap dipertahankan pada seluruh tautan navigasi paginasi.
