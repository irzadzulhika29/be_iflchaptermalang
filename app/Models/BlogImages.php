<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Blog;

class BlogImages extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
      'image_url', 

      'blog_id',
    ];

    public function blog() 
    {
      return $this->belongsTo(Blog::class);
    }
}
