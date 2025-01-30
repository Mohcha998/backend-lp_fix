<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user_fthr',
        'id_user_mthr',
        'id_program',
        'user_id',
        'id_course',
        'id_branch',
        'id_kelas',
        'name',
        'phone',
        'email',
        'tgl_lahir',
        'asal_sekolah',
        'perubahan',
        'jenis_kelamin',
        'jadwal',
        'kelebihan',
        'dirawat',
        'kondisi',
        'tindakan',
        'emergency_contact',
        'hubungan_eme',
        'emergency_call',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
