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
        'event_id',
        'name',
        'phone_number',
        'username_instagram',
        'info_source',
        'motivation',
        'experience',
        'has_read_guidebook',
        'is_committed',
        'google_drive_link',
        'status',
        'event_name',
        'event_year',

        'referral_code_used',
        'discount_amount',
        'original_price',
        'final_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'event_year' => 'integer',
        'discount_amount' => 'decimal:2',
        'original_price' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

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

    public function hasUsedReferralCode(): bool
    {
        return !empty($this->referral_code_used);
    }
}
