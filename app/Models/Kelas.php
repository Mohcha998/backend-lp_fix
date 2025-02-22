<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_schedule',
        'id_coach',
        'name',
        'day',
        'start_time',
        'end_time',
    ];
}
