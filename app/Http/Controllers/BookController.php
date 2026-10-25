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
