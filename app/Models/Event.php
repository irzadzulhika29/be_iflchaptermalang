<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'status',
        'category',
        'start_date',
        'description',
        'event_activity',
        'event_photo',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function sdgs()
    {
        return $this->belongsToMany(Sdg::class, 'event_sdg')
            ->using(EventSdg::class)
            ->withTimestamps();
    }
}
