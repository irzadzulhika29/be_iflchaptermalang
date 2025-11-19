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
        'end_date',
        'description',
        'event_activity',
        'event_photo',
        'participant',
        'committee',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'participant' => 'integer',
        'committee' => 'integer',
    ];

    public function sdgs()
    {
        return $this->belongsToMany(Sdg::class, 'event_sdg')
            ->using(EventSdg::class)
            ->withTimestamps();
    }

    public function volunteerRegistrations()
    {
        return $this->hasMany(VolunteerRegistration::class);
    }

    public function scopeOpenForVolunteerRegistration($query)
    {
        return $query->where('status', 'open')
            ->where('category', 'program');
    }
}
