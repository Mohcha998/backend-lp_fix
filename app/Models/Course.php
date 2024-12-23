<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'id_program', 'price', 'schedule', 'course_type'];

    protected $casts = [
        'schedule' => 'array',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class, 'id_program');
    }

    public function schedules()
    {
        return $this->hasMany(SchedulePrg::class);
    }
}
