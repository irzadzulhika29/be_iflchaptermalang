<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Blog_Like extends Pivot
{
  use HasFactory, HasUuids;

  protected $table = 'blog_like';

  protected $fillable = [
    'blog_id',
    'user_id',
  ];
}
