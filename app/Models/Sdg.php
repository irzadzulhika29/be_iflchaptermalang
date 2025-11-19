<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Sdg extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',

    ];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_sdg')
            ->using(EventSdg::class)
            ->withTimestamps();
    }
}
