<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'parent_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function parent()
    {
        return $this->belongsTo(ProspectParent::class, 'parent_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'user_id', 'id');
    }
}
