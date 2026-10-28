<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoActivity extends Model
{
    protected $table = 'turno_actividades';

    protected $fillable = ['turno_schedule_id', 'date', 'title', 'responsible', 'description', 'convocatoria', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TurnoSchedule::class, 'turno_schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
