<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
      'snap_token',
      'payment_url',
      'midtrans_transaction_id',
      'payment_method',
      'payment_provider',
      'va_number',
      'transaction_success_time',
      'transaction_expiry_time',

      'donation_id',
      'user_id',
    ];

    public function donation()
    {
      return $this->belongsTo(Donation::class);
    }

    public function user()
    {
      return $this->belongsTo(User::class);
    }
}
