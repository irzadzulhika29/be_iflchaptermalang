<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BlogCategories extends Model
{
  use HasFactory, HasUuids;

  protected $table = 'blog_categories';
    
    protected $fillable = [
      'name',
      'description',
    ];
    
    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_category');
    }
}
