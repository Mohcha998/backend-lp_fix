<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'id_parent',
        'name',
        'email',
        'phone',
        'address',
        'is_father',
        'is_mother',
    ];

    public function prospectParent()
    {
        return $this->belongsTo(ProspectParent::class, 'id_parent', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
