<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('books')->upsert([
            [
                'id' => 1,
                'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'isbn' => '9780132350884',
                'available' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'title' => 'The Pragmatic Programmer: Your Journey to Mastery',
                'isbn' => '9780201616224',
                'available' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['id'], ['title', 'isbn', 'available', 'updated_at']);
    }
}
