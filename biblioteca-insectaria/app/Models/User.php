<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'role' => 'string',
        ];
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_VOLUNTARIO = 'voluntario';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isVolunteer(): bool
    {
        return $this->role === self::ROLE_VOLUNTARIO;
    }

    public function prestamosRegistrados(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function sociosRegistrados(): HasMany
    {
        return $this->hasMany(Socio::class);
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(UserActionLog::class, 'user_id');
    }

    public function actorLogs(): HasMany
    {
        return $this->hasMany(UserActionLog::class, 'actor_id');
    }
}
