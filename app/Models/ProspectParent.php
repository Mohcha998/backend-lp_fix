<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectParent extends Model
{
    protected $primaryKey = 'id';

    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'source',
        'id_cabang',
        'id_program',
        'is_sp',
        'invoice_prg',
        'call',
        'call2',
        'call3',
        'call4',
        'tgl_checkin',
        'invitional_code',
        'is_father',
        'is_mother',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'id_cabang');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'id_program');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'parent_id');
    }
    public function parents()
    {
        return $this->hasMany(Parents::class, 'id_parent', 'id');
    }
    public function invite()
    {
        return $this->belongsTo(InvitonalCode::class, 'inv_id');
    }
    public function payments()
    {
        return $this->hasMany(Payment_Sps::class, 'id_parent');
    }
}
