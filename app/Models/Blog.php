<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;
use App\Models\BlogCategories;
use App\Models\BlogImages;
use App\Models\Comment;
use App\Models\Blog_Like;

class Blog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
      'title', 
      'slug',
      'content', 
      'like',

      'author_id',
    ];

  public function author()
  {
      return $this->belongsTo(User::class);
  }

  public function blog_categories()
  {
      return $this->belongsToMany(BlogCategories::class, 'blog_category', 'blog_id', 'category_id')->using(Blog_Category::class)->withTimestamps();
  }

  public function blog_images()
  {
    return $this->hasMany(BlogImages::class);
  }

  public function comments()
  {
      return $this->hasMany(Comment::class);
  }

  public function likes()
  {
    return $this->belongsToMany(Blog::class, 'blog_like');
  }
}
