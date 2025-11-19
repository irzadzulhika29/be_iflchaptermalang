<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventSdg extends Pivot
{
    use HasUuids;

    protected $table = 'event_sdg';

    public $incrementing = false;
    protected $keyType = 'string';
}
