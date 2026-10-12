# Laporan Praktikum 5 — Membangun CRUD yang Konsisten

**Mata Kuliah:** Manajemen Layanan Web / Web Service Management  
**Modul:** Praktikum 5 — CRUD, Validation, API Resource, dan Error Response  
**Proyek Semester:** RiverVigil API — Disaster Early Warning & River Telemetry System  
**Resource Domain:** `Book` (`books`) [dan `Station`]  
**Versi API:** `v1.2.0`  
**Base URL:** `http://127.0.0.1:8000/api`  
**Repositori GitHub:** [https://github.com/202234010-tech/rivervigil-api](https://github.com/202234010-tech/rivervigil-api)  
**Tautan Commit Terkait:** [Commit a79c7af](https://github.com/202234010-tech/rivervigil-api/commit/a79c7af)  
**Waktu Praktikum:** 120 menit  

---

## 1. Ringkasan & Tujuan Praktikum

Setelah menyelesaikan tahapan pada Praktikum 5 ini, mahasiswa berhasil:
1. **Membangun Operasi CRUD Lengkap:** Mengembangkan endpoint `index`, `store`, `show`, `update`, dan `destroy` menggunakan standar resource routing Laravel (`Route::apiResource`).
2. **Menerapkan Validasi Form Request:** Memisahkan logika validasi ke dalam kelas `StoreBookRequest` dan `UpdateBookRequest` untuk menjamin integritas data sebelum diproses oleh controller.
3. **Menggunakan Eloquent API Resource:** Memanfaatkan `BookResource` untuk memformat keluaran JSON secara konsisten dengan uniform envelope `data`, format ISO-8601 untuk timestamp, serta casting boolean yang tepat.
4. **Menyeragamkan Respon Sukses dan Error:** Mengembalikan kode status HTTP semantik (`200 OK`, `201 Created`, `204 No Content`, `404 Not Found`, dan `422 Unprocessable Content`) dengan struktur error body yang informatif.
5. **Mengamankan Entitas Model dari Mass Assignment:** Membatasi field yang dapat dimanipulasi client melalui properti `$fillable = ['title', 'isbn', 'available']`.
6. **Menyediakan Suite Pengujian Otomatis & Postman:** Menguji seluruh alur CRUD dan validasi melalui automated test PHPUnit (`BookApiTest`) serta Postman Collection lengkap dengan assertions.
7. **Menerapkan Version Control yang Aman & Terstruktur:** Mengelola commit Git secara spesifik tanpa mengekspos kredensial, berkas `.env`, maupun database lokal.

---

## 2. Struktur Komponen yang Dihasilkan

| Komponen | Path File | Peran & Deskripsi |
|---|---|---|
| **Model** | `app/Models/Book.php` | Model Eloquent dengan proteksi mass assignment `$fillable` dan attribute casts boolean. |
| **Form Request Store** | `app/Http/Requests/StoreBookRequest.php` | Validasi pembuatan buku: wajib `title`, panjang ISBN tepat 13 digit, dan unik. |
| **Form Request Update** | `app/Http/Requests/UpdateBookRequest.php` | Validasi pembaruan buku parsial (`sometimes`), dengan pengecualian ID buku pada aturan unik ISBN. |
| **API Resource** | `app/Http/Resources/BookResource.php` | Transformasi objek model menjadi JSON terstruktur dengan pembungkus `data` dan format ISO-8601. |
| **Controller** | `app/Http/Controllers/BookController.php` | Controller CRUD dengan respon `200`, `201`, `204`, `404`, dan `422`. |
| **Migration** | `database/migrations/*_create_books_table.php` | Blueprint tabel basis data `books` dengan string ISBN unik dan boolean availability. |
| **Seeder** | `database/seeders/BookSeeder.php` | Seeder awal idempotent untuk data pengujian development. |
| **Route** | `routes/api.php` | Pendaftaran resource route `Route::apiResource('books', BookController::class)`. |
| **Feature Test** | `tests/Feature/BookApiTest.php` | 10 skenario pengujian otomatis dengan 46 assertions (semua lulus). |
| **Postman Collection** | `docs/postman/week-05-crud.postman_collection.json` | Koleksi Postman dengan 7 request, script assertions, dan contoh actual response. |
| **Dokumentasi Praktikum** | `docs/minggu-5-praktikum.md` | Laporan verifikasi praktikum, tabel komparasi kontrak, AI declaration, dan referensi. |

---

## 3. Implementasi Kode Utama

### 3.1 Model (`app/Models/Book.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['title', 'isbn', 'available'];

    protected function casts(): array
    {
        return ['available' => 'boolean'];
    }
}
```

### 3.2 Form Request Validasi
#### Store (`app/Http/Requests/StoreBookRequest.php`)
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
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
}
```

#### Update (`app/Http/Requests/UpdateBookRequest.php`)
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
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
}
```

### 3.3 API Resource (`app/Http/Resources/BookResource.php`)
```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'isbn' => $this->isbn,
            'available' => (bool) $this->available,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

### 3.4 Controller (`app/Http/Controllers/BookController.php`)
```php
namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BookResource::collection(Book::query()->orderBy('id')->get());
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    public function show(int $book): BookResource|JsonResponse
    {
        $model = Book::find($book);

        return $model ? new BookResource($model) : $this->notFound();
    }

    public function update(
        UpdateBookRequest $request,
        int $book
    ): BookResource|JsonResponse {
        $model = Book::find($book);

        if ($model === null) {
            return $this->notFound();
        }

        $model->update($request->validated());

        return new BookResource($model->refresh());
    }

    public function destroy(int $book): JsonResponse
    {
        $model = Book::find($book);

        if ($model === null) {
            return $this->notFound();
        }

        $model->delete();

        return response()->json(null, 204);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Book not found',
            'errors' => null,
        ], 404);
    }
}
```

---

## 4. Hasil Verifikasi & Testing

### 4.1 Ringkasan Respons HTTP
- **201 Created:** `POST /api/books` berhasil menyimpan data baru dengan `id: 3`, format ISO-8601 pada `created_at`, dan dibungkus atribut `data`.
- **422 Unprocessable Content:** `POST /api/books` dengan input kosong mengembalikan HTTP 422 beserta daftar error untuk field `title` dan `isbn`.
- **200 OK (Partial Update):** `PATCH /api/books/3` hanya memperbarui nilai `available` menjadi `false` tanpa menghilangkan atau mengubah nilai field lainnya.
- **404 Not Found:** `GET /api/books/999999` menghasilkan format error baku `{"message": "Book not found", "errors": null}`.
- **204 No Content:** `DELETE /api/books/3` mengembalikan HTTP 204 dengan payload kosong, dan rekaman terkonfirmasi terhapus pada pemeriksaan ulang.

### 4.2 Automated Feature Test
Suite pengujian otomatis dieksekusi menggunakan PHPUnit:
- `tests/Feature/BookApiTest.php`: 10 passed, 46 assertions, 0 failed.
- Keseluruhan test suite repositori: 17 passed, 86 assertions, 0 failed.

---

## 5. Referensi & Deklarasi AI

- **Deklarasi Penggunaan AI:** Tugas ini dikembangkan dengan asistensi Antigravity AI Coding Assistant (Gemini 3.8 Flash model) untuk analisis modul, scaffolding kode, dan penyusunan dokumentasi. Seluruh kode telah diuji secara menyeluruh melalui live HTTP client dan PHPUnit automated feature test.
- **Referensi:**
  1. Laravel Official Documentation (API Resources, Form Requests, Routing, Controllers)
  2. RFC 9110 (HTTP Semantics) & RFC 8259 (JSON)
  3. Modul Pembelajaran Manajemen Web Service 2026: *Praktikum 5 — Membangun CRUD yang Konsisten*.
