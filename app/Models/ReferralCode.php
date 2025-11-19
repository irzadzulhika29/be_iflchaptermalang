<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReferralCode extends Model
{
    use HasFactory, HasUuids;

    const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    const DISCOUNT_TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'event_name',
        'event_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'event_id' => 'uuid',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function usedByRegistrations()
    {
        return $this->hasMany(VolunteerRegistration::class, 'referral_code_used', 'code');
    }

    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function getInvalidReason(): string
    {
        if (!$this->is_active) {
            return 'Referral code is not active';
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return 'Referral code is not available yet';
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return 'Referral code has expired';
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'Referral code has reached the maximum usage';
        }

        return 'Referral code is not valid';
    }

    public function incrementUsedCount()
    {
        $this->increment('used_count');
    }

    public function calculateDiscountAmount(float $originalPrice): float
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_FIXED) {
            return min($this->discount_value, $originalPrice);
        } else if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            return round($originalPrice * ($this->discount_value / 100), 2);
        }
        return 0;
    }

    public function getRemainingUses(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - $this->used_count);
    }

    public function getDiscountText(): string
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_FIXED) {
            return $this->discount_value . '%';
        }
        return 'Rp ' . number_format($this->discount_value, 0, ',', '.');
    }
}
