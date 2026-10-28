<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoAvailability extends Model
{
    protected $fillable = ['turno_schedule_id', 'user_id', 'weekdays', 'date_overrides'];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'date_overrides' => 'array',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TurnoSchedule::class, 'turno_schedule_id');
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
