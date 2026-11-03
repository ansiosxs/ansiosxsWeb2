<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoVolunteerProfile extends Model
{
    protected $fillable = ['user_id', 'enabled', 'weekdays', 'monthly_turns'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'weekdays' => 'array',
            'monthly_turns' => 'integer',
        ];
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
