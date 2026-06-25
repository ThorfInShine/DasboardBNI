<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'npp', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function createdData(): HasMany
    {
        return $this->hasMany(Data::class, 'created_by');
    }

    public function updatedData(): HasMany
    {
        return $this->hasMany(Data::class, 'updated_by');
    }

    public function dataHistories(): HasMany
    {
        return $this->hasMany(DataHistory::class, 'changed_by');
    }

    public function importHistories(): HasMany
    {
        return $this->hasMany(ImportHistory::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeAdmins($query)
    {
        return $query->role('admin');
    }

    public function scopeUsers($query)
    {
        return $query->role('user');
    }
}
