<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Comment_Like extends Pivot
{
  use HasFactory, HasUuids;

  protected $table = 'comment_like';

  protected $fillable = [
    'comment_id',
    'user_id',
  ];
}
