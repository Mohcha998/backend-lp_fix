<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchedulePrg extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'month', 'year', 'module', 'start_date', 'end_date'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
