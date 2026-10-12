<?php

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
