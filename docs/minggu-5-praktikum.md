# Praktikum Minggu 5 — CRUD Laravel API

## Informasi Praktikum

- **Mata Kuliah:** Manajemen Layanan Web / Web Service Management
- **Modul:** Praktikum 5 — Membangun CRUD yang Konsisten
- **Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System
- **Resource Domain:** `Book` (`books`)
- **Base URL:** `http://127.0.0.1:8000/api`
- **Repositori GitHub:** [https://github.com/202234010-tech/rivervigil-api](https://github.com/202234010-tech/rivervigil-api)
- **Postman Collection:** `docs/postman/week-05-crud.postman_collection.json`

---

## Endpoint

Sesuai konvensi RESTful Laravel (`Route::apiResource('books', BookController::class)`), terdapat 5 operasi CRUD utama yang didaftarkan:

| No | HTTP Method | URI Path | Controller Action | Deskripsi Fungsi | Status Code Sukses |
|---|---|---|---|---|---|
| 1 | `GET` | `/api/books` | `BookController@index` | Mengambil seluruh daftar koleksi buku terurut berdasarkan `id` | `200 OK` |
| 2 | `POST` | `/api/books` | `BookController@store` | Menyimpan rekaman buku baru dengan validasi data masukan | `201 Created` |
| 3 | `GET` | `/api/books/{book}` | `BookController@show` | Mengambil rincian data buku tunggal berdasarkan primary key `{id}` | `200 OK` |
| 4 | `PUT` / `PATCH` | `/api/books/{book}` | `BookController@update` | Memperbarui atribut rekaman buku (sebagian via PATCH atau keseluruhan via PUT) | `200 OK` |
| 5 | `DELETE` | `/api/books/{book}` | `BookController@destroy` | Menghapus data buku dari basis data | `204 No Content` |

Hasil pengecekan rute melalui CLI `php artisan route:list --path=api/books`:
```text
GET|HEAD   api/books ................. books.index › BookController@index
POST       api/books ................. books.store › BookController@store
GET|HEAD   api/books/{book} .......... books.show › BookController@show
PUT|PATCH  api/books/{book} .......... books.update › BookController@update
DELETE     api/books/{book} .......... books.destroy › BookController@destroy
```

---

## Validation

Validasi masukan diimplementasikan menggunakan lapisan **Form Request** mandiri (`Illuminate\Foundation\Http\FormRequest`), sehingga controller tetap ramping (*skinny controller*) dan fokus pada orkestrasi alur logika.

### 1. Rules Create (`StoreBookRequest`)
File: `app/Http/Requests/StoreBookRequest.php`

```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [
        'title' => ['required', 'string', 'min:3', 'max:200'],
        'isbn' => ['required', 'string', 'size:13', 'unique:books,isbn'],
        'available' => ['sometimes', 'boolean'],
    ];
}
```

**Penjelasan Aturan Create:**
- `title`: Wajib diisi (`required`), bertipe teks (`string`), dengan panjang minimal 3 karakter dan maksimal 200 karakter.
- `isbn`: Wajib diisi (`required`), bertipe teks, harus tepat 13 karakter (`size:13`), dan tidak boleh duplikat di tabel `books` kolom `isbn` (`unique:books,isbn`).
- `available`: Bersifat opsional (`sometimes`), jika dikirimkan harus bernilai boolean (`true` / `false`). Jika tidak dikirimkan, nilai default basis data adalah `true`.

### 2. Rules Update (`UpdateBookRequest`)
File: `app/Http/Requests/UpdateBookRequest.php`

```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [
        'title' => ['sometimes', 'required', 'string', 'min:3', 'max:200'],
        'isbn' => [
            'sometimes', 'required', 'string', 'size:13',
            Rule::unique('books', 'isbn')->ignore($this->route('book')),
        ],
        'available' => ['sometimes', 'required', 'boolean'],
    ];
}
```

**Penjelasan Aturan Update:**
- `sometimes`: Memungkinkan pengiriman payload parsial (misal hanya field `available` pada operasi `PATCH`). Jika suatu field tidak disertakan dalam request body, validasi untuk field tersebut diabaikan tanpa memicu error.
- `Rule::unique('books', 'isbn')->ignore($this->route('book'))`: Mencegah false positive duplikasi ISBN ketika memperbarui rekaman yang sama. Sistem mengecualikan ID buku yang sedang diubah dari pengecekan keunikan ISBN, namun tetap menolak jika ISBN tersebut telah digunakan oleh rekaman buku lain.

---

## Verification

Seluruh endpoint diuji secara empiris terhadap instance aktif server Laravel (`http://127.0.0.1:8000`). Berikut rekaman request dan actual response untuk setiap skenario:

### 1. 201 Create (Pembuatan Buku Berhasil)
- **Method & URL:** `POST http://127.0.0.1:8000/api/books`
- **Request Headers:**
  ```http
  Accept: application/json
  Content-Type: application/json
  ```
- **Request Body:**
  ```json
  {
    "title": "Refactoring",
    "isbn": "9780134757599",
    "available": true
  }
  ```
- **HTTP Status Code:** `201 Created`
- **Actual Response Body:**
  ```json
  {
    "data": {
      "id": 3,
      "title": "Refactoring",
      "isbn": "9780134757599",
      "available": true,
      "created_at": "2026-10-12T05:47:32.000000Z"
    }
  }
  ```

### 2. 422 Validation (Validasi Input Gagal)
- **Method & URL:** `POST http://127.0.0.1:8000/api/books`
- **Request Headers:**
  ```http
  Accept: application/json
  Content-Type: application/json
  ```
- **Request Body:**
  ```json
  {
    "title": "",
    "isbn": "123"
  }
  ```
- **HTTP Status Code:** `422 Unprocessable Content`
- **Actual Response Body:**
  ```json
  {
    "message": "The title field is required. (and 1 more error)",
    "errors": {
      "title": [
        "The title field is required."
      ],
      "isbn": [
        "The isbn field must be 13 characters."
      ]
    }
  }
  ```

### 3. 200 Update (Pembaruan Sebagian / Partial Update)
- **Method & URL:** `PATCH http://127.0.0.1:8000/api/books/3`
- **Request Headers:**
  ```http
  Accept: application/json
  Content-Type: application/json
  ```
- **Request Body:**
  ```json
  {
    "available": false
  }
  ```
- **HTTP Status Code:** `200 OK`
- **Actual Response Body:**
  ```json
  {
    "data": {
      "id": 3,
      "title": "Refactoring",
      "isbn": "9780134757599",
      "available": false,
      "created_at": "2026-10-12T05:47:32.000000Z"
    }
  }
  ```
  *(Terverifikasi: atribut `available` berhasil diubah menjadi `false`, sedangkan `title` dan `isbn` tetap utuh).*

### 4. 404 Not Found (Resource Buku Tidak Ditemukan)
- **Method & URL:** `GET http://127.0.0.1:8000/api/books/999999`
- **Request Headers:**
  ```http
  Accept: application/json
  ```
- **HTTP Status Code:** `404 Not Found`
- **Actual Response Body:**
  ```json
  {
    "message": "Book not found",
    "errors": null
  }
  ```

### 5. 204 Delete (Penghapusan Buku Berhasil)
- **Method & URL:** `DELETE http://127.0.0.1:8000/api/books/3`
- **Request Headers:**
  ```http
  Accept: application/json
  ```
- **HTTP Status Code:** `204 No Content`
- **Actual Response Body:** *(Empty / 0 bytes body sesuai standar RFC 9110)*
- **Verifikasi Pasca Hapus:**
  - `GET http://127.0.0.1:8000/api/books/3` menghasilkan `404 Not Found`:
    ```json
    {
      "message": "Book not found",
      "errors": null
    }
    ```

---

## Contract Comparison

Perbandingan antara spesifikasi rancangan kontrak API dengan actual response yang dihasilkan oleh implementasi Laravel Minggu 5:

| Aspek Kontrak | Spesifikasi Perancangan Kontrak | Actual Response Laravel Minggu 5 | Evaluasi Kesesuaian |
|---|---|---|---|
| **Root Wrapper Sukses** | Seluruh data entitas dibungkus dalam atribut `data` | `{"data": {...}}` atau `{"data": [...]}` via `BookResource` | **Sesuai 100%** |
| **Status Code Create** | `201 Created` | `201 Created` via `->response()->setStatusCode(201)` | **Sesuai 100%** |
| **Status Code Update** | `200 OK` | `200 OK` via `new BookResource($model->refresh())` | **Sesuai 100%** |
| **Status Code Validation Error** | `422 Unprocessable Content` dengan list error per field | `422 Unprocessable Content` dengan struktur `{message, errors: {...}}` | **Sesuai 100%** |
| **Status Code Not Found** | `404 Not Found` dengan envelope error terstruktur | `404 Not Found` dengan JSON `{"message": "Book not found", "errors": null}` | **Sesuai 100%** |
| **Status Code Delete** | `204 No Content` tanpa body | `204 No Content` dengan response kosong via `response()->json(null, 204)` | **Sesuai 100%** |
| **Tipe Data Field** | `id` (int), `title` (string), `isbn` (string), `available` (bool) | `id` (int), `title` (string), `isbn` (string), `available` (bool) | **Sesuai 100%** |
| **Format Timestamp** | ISO-8601 UTC string (`toISOString()`) | Contoh: `"2026-10-12T05:47:32.000000Z"` | **Sesuai 100%** |

### Analisis Kesesuaian & Arsitektur
1. **Penerapan API Resource:** Penggunaan `App\Http\Resources\BookResource` menggantikan method ad-hoc `bookData()`. Resource ini bertindak sebagai lapisan transformasi data yang menjamin data output terisolasi dari skema tabel mentah, format timestamp selalu berstandar ISO-8601, dan tipe casting boolean tetap konsisten.
2. **Form Request Validation:** Pengecekan authorization dan rules dilakukan sebelum request menyentuh controller action. Jika validasi gagal, Laravel secara otomatis melempar `ValidationException` yang dirender menjadi HTTP 422 JSON terstandar.
3. **Penanganan Error 404 Terpusat:** Method helper `notFound()` memastikan format error 404 konsisten seragam dengan pesan deskriptif dan atribut `errors: null`.
4. **Idempotensi & Keamanan:** Penentuan atribut `$fillable = ['title', 'isbn', 'available']` pada model `Book` mengamankan aplikasi dari serangan *Mass Assignment Vulnerability*, mencegah client memanipulasi kolom `id` maupun timestamps secara ilegal.

---

## Testing & Evidence

### 1. Bukti Postman Collection
Koleksi Postman disimpan pada berkas:
- **Path:** `docs/postman/week-05-crud.postman_collection.json` (dan duplikasi pada `postman/week-05-crud.postman_collection.json`)
- **Fitur Pengujian:**
  1. `00 - GET All Books (200 OK)`: Memverifikasi kode status 200 dan keberadaan array data.
  2. `01 - Create Book (201 Created)`: Memverifikasi pembuatan buku baru, validasi struktur BookResource, serta penyimpanan dinamis variabel koleksi `book_id`.
  3. `02 - Validation Fails (422 Unprocessable Content)`: Menguji penolakan input title kosong dan panjang ISBN tidak valid.
  4. `03 - Update Partial Book (200 OK)`: Menguji method `PATCH` untuk mengubah `available` menjadi `false` tanpa merusak field lain.
  5. `04 - Detail Not Found (404 Not Found)`: Menguji respons ID fiktif `999999`.
  6. `05 - Delete Book (204 No Content)`: Menguji penghapusan buku dengan status code 204 dan payload kosong.
  7. `06 - Verify Deleted Book Returns 404 (404 Not Found)`: Memastikan record yang telah dihapus tidak dapat diakses kembali.

### 2. Bukti Automated Feature Test Laravel
Suite pengujian otomatis ditulis pada `tests/Feature/BookApiTest.php` menggunakan database memory/sqlite via `RefreshDatabase`:
- `test_can_list_all_books_with_200_ok`: **PASSED**
- `test_can_create_book_with_201_created`: **PASSED**
- `test_validation_fails_on_empty_title_and_invalid_isbn_with_422`: **PASSED**
- `test_can_get_single_book_detail_with_200_ok`: **PASSED**
- `test_returns_404_when_book_not_found`: **PASSED**
- `test_can_partially_update_book_with_200_ok`: **PASSED**
- `test_update_allows_keeping_same_isbn`: **PASSED**
- `test_update_fails_when_isbn_belongs_to_another_book`: **PASSED**
- `test_can_delete_book_with_204_no_content`: **PASSED**
- `test_delete_returns_404_when_book_does_not_exist`: **PASSED**

Hasil eksekusi keseluruhan test suite (`vendor/bin/phpunit`):
```text
{"tool":"phpunit","result":"passed","tests":17,"passed":17,"assertions":86,"duration_ms":2334}
```
*Total 17 feature tests (termasuk modul stasiun dan modul buku), 86 assertions, 0 failure.*

---

## AI Usage

Sesuai pedoman integritas akademik, penggunaan kecerdasan buatan (AI) dideklarasikan sebagai berikut:

- **Alat / Tool AI:** Antigravity AI Coding Assistant (berbasis Gemini 3.8 Flash model).
- **Tujuan Penggunaan:**
  1. Melakukan ekstraksi dan analisis modul panduan Notion Praktikum 5.
  2. Membantu scaffolding kode boilerplate untuk Eloquent API Resource (`BookResource`), Form Request (`StoreBookRequest`, `UpdateBookRequest`), dan Controller CRUD.
  3. Membantu perancangan suite pengujian otomatis (`BookApiTest`) dan Postman Collection format v2.1.0.
  4. Menyusun template laporan komparasi kontrak dan dokumentasi teknis.
- **Cara Verifikasi Manual:**
  1. Menjalankan migrasi dan seeder pada basis data SQLite lokal menggunakan `php artisan migrate` dan `php artisan db:seed`.
  2. Menjalankan test suite unit/feature secara riil dengan PHPUnit CLI hingga seluruh 86 assertions terbukti sukses (*green*).
  3. Menguji server aktif HTTP menggunakan client HTTP curl lokal untuk memastikan status code 201, 422, 200, 404, dan 204 sesuai spesifikasi.
  4. Memeriksa staging git secara manual dengan `git diff --cached` untuk memastikan berkas rahasia (`.env`, database sqlite, credential) tidak ikut ter-commit.

---

## Referensi

1. **Laravel Documentation (v11/v12/v13):**
   - *Eloquent: API Resources*: [https://laravel.com/docs/eloquent-resources](https://laravel.com/docs/eloquent-resources)
   - *Validation: Form Request Validation*: [https://laravel.com/docs/validation#form-request-validation](https://laravel.com/docs/validation#form-request-validation)
   - *Controllers & Resource Controllers*: [https://laravel.com/docs/controllers#resource-controllers](https://laravel.com/docs/controllers#resource-controllers)
2. **Internet Engineering Task Force (IETF) / RFC Standards:**
   - *RFC 9110 — HTTP Semantics*: Status Codes `200 OK`, `201 Created`, `204 No Content`, `404 Not Found`, `422 Unprocessable Content`.
   - *RFC 8259 — The JavaScript Object Notation (JSON) Data Interchange Format*.
3. **Modul Praktikum Manajemen Layanan Web 2026:**
   - *Praktikum 5 — Membangun CRUD yang Konsisten* (Modul Pembelajaran Manajemen API).
