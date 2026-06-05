<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    // Category Moodle se sync hoti hai, lekin website catalog filters mein use hoti hai.
    protected $fillable = ['moodle_category_id', 'name', 'slug', 'status'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
