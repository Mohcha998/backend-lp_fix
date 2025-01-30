<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kota',
        'kode_cabang',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class, 'kota', 'name');
    }
}
