<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
            'role' => Role::class,
        ];
    }

    public function hasRole(Role|string $role): bool
    {
        $current = $this->role instanceof Role ? $this->role : Role::tryFrom((string) $this->role);
        if (! $current) {
            return false;
        }

        if ($role instanceof Role) {
            return $current === $role;
        }

        return $current === Role::tryFrom((string) $role);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isDocente(): bool
    {
        return $this->hasRole(Role::DOCENTE);
    }

    public function isResponsable(): bool
    {
        return $this->hasRole(Role::RESPONSABLE);
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class);
    }

    public function updateReads(): HasMany
    {
        return $this->hasMany(UpdateRead::class);
    }
}
