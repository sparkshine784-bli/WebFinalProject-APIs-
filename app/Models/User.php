<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role' => 'string',
    ];

    // JWTSubject interface

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // Relationships

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }
}
