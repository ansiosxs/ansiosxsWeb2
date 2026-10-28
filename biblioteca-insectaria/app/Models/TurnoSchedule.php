<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurnoSchedule extends Model
{
    protected $fillable = [
        'year', 'month', 'volunteers_per_day', 'availability_closes_at', 'status', 'created_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'availability_closes_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function days(): HasMany
    {
        return $this->hasMany(TurnoDay::class)->orderBy('date');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TurnoAvailability::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TurnoActivity::class)->orderBy('date');
    }

    public function getMonthLabelAttribute(): string
    {
        return \Illuminate\Support\Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y');
    }
}
