<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VolunteerRegistration extends Model
{
    use HasFactory, HasUuids;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'name',
        'phone_number',
        'university',
        'line_id',
        'choice_1',
        'choice_2',
        'google_drive_link',
        'status',
        'event_name',
        'event_year',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'event_year' => 'integer',
    ];

    /**
     * Get the user that owns the volunteer registration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if registration is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if registration is approved
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }
}

