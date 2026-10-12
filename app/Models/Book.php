<?php

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
