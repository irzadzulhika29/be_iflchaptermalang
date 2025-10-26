<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Comment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
      'username',
      'content',
      'like',
      'base_comment_id',

      'user_id',
      'blog_id',
    ];

    public function blog()
    {
      return $this->belongsTo(Blog::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
      return $this->hasMany(Comment::class, 'base_comment_id');
    }

    public function likes()
  {
    return $this->belongsToMany(Comment::class, 'comment_like');
  }
}
