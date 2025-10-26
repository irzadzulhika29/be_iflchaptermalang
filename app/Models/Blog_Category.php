<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Blog_Category extends Pivot
{
  use HasFactory, HasUuids;

    protected $table = 'blog_blog_categories';

    protected $fillable = [
      'blog_id',
      'category_id',
    ];
}
