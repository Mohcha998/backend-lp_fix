<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function payments()
    {
        return $this->hasMany(Payment_Sps::class, 'nama_bank');
    }
}
