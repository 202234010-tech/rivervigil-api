<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Book::create([
            'id' => 1,
            'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'isbn' => '9780132350884',
            'available' => true,
        ]);

        Book::create([
            'id' => 2,
            'title' => 'The Pragmatic Programmer: Your Journey to Mastery',
            'isbn' => '9780201616224',
            'available' => true,
        ]);
    }

    public function test_can_list_all_books_with_200_ok(): void
    {
        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'isbn', 'available', 'created_at']
                ]
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_create_book_with_201_created(): void
    {
        $payload = [
            'title' => 'Refactoring',
            'isbn' => '9780134757599',
            'available' => true,
        ];

        $response = $this->postJson('/api/books', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'id' => 3,
                    'title' => 'Refactoring',
                    'isbn' => '9780134757599',
                    'available' => true,
                ]
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'isbn', 'available', 'created_at']
            ]);

        $this->assertDatabaseHas('books', [
            'id' => 3,
            'title' => 'Refactoring',
            'isbn' => '9780134757599',
            'available' => true,
        ]);
    }

    public function test_validation_fails_on_empty_title_and_invalid_isbn_with_422(): void
    {
        $payload = [
            'title' => '',
            'isbn' => '123',
        ];

        $response = $this->postJson('/api/books', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'isbn']);
    }

    public function test_can_get_single_book_detail_with_200_ok(): void
    {
        $response = $this->getJson('/api/books/1');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 1,
                    'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                    'isbn' => '9780132350884',
                    'available' => true,
                ]
            ]);
    }

    public function test_returns_404_when_book_not_found(): void
    {
        $response = $this->getJson('/api/books/999999');

        $response->assertStatus(404)
            ->assertExactJson([
                'message' => 'Book not found',
                'errors' => null,
            ]);
    }

    public function test_can_partially_update_book_with_200_ok(): void
    {
        $payload = [
            'available' => false,
        ];

        $response = $this->patchJson('/api/books/1', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 1,
                    'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                    'isbn' => '9780132350884',
                    'available' => false,
                ]
            ]);

        $this->assertDatabaseHas('books', [
            'id' => 1,
            'available' => false,
        ]);
    }

    public function test_update_allows_keeping_same_isbn(): void
    {
        $payload = [
            'title' => 'Clean Code 2nd Edition',
            'isbn' => '9780132350884',
        ];

        $response = $this->putJson('/api/books/1', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 1,
                    'title' => 'Clean Code 2nd Edition',
                    'isbn' => '9780132350884',
                ]
            ]);
    }

    public function test_update_fails_when_isbn_belongs_to_another_book(): void
    {
        $payload = [
            'isbn' => '9780201616224', // exists on book 2
        ];

        $response = $this->patchJson('/api/books/1', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_can_delete_book_with_204_no_content(): void
    {
        $response = $this->deleteJson('/api/books/1');

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());

        $this->assertDatabaseMissing('books', [
            'id' => 1,
        ]);

        $verifyResponse = $this->getJson('/api/books/1');
        $verifyResponse->assertStatus(404)
            ->assertExactJson([
                'message' => 'Book not found',
                'errors' => null,
            ]);
    }

    public function test_delete_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->deleteJson('/api/books/999999');

        $response->assertStatus(404)
            ->assertExactJson([
                'message' => 'Book not found',
                'errors' => null,
            ]);
    }
}
