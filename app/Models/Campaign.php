<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
      'title',
      'slug',
      'short_description',
      'body',
      'view_count',
      'status',
      'current_donation',
      'target_donation',
      'publish_date',
      'end_date',
      'note',
      'receiver',
      'image',
      
      'user_id',
    ];

    public function user()
    {
    return $this->belongsTo(User::class);
    }

    public function categories()
    {
      return $this->belongsToMany(Category::class)->using(Campaign_Category::class)->withTimestamps();
    }

    public function donations()
    {
      return $this->hasMany(Donation::class);
    }
}
