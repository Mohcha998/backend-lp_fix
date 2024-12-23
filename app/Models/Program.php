<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    public function prospectParents()
    {
        return $this->hasMany(ProspectParent::class, 'id_program');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
