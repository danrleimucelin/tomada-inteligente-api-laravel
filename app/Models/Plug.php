<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plug extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'serial_number',
        'pin',
        'power',
        'consumption',
        'token',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->whereNull('deleted_at')
            ->withTimestamps()
            ->withPivot(['deleted_at']);
    }

    public function usersWithTrashed(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot(['deleted_at']);
    }

    public function isMyToken(string $token)
    {
        return $this->token === $token;
    }
}
