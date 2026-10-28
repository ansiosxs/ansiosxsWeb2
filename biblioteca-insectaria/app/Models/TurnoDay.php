<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurnoDay extends Model
{
    protected $fillable = ['turno_schedule_id', 'date', 'is_open', 'assignment_notes'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_open' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TurnoSchedule::class, 'turno_schedule_id');
    }

    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'turno_asignaciones', 'turno_dia_id', 'user_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TurnoActivity::class, 'turno_schedule_id', 'turno_schedule_id')
            ->whereDate('date', $this->date);
    }
}
