<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvitonalCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_code',
        'id_cabang',
        'status_code',
        'qty',
        'type',
        'diskon'
    ];

    public function parents()
    {
        return $this->hasMany(ProspectParent::class, 'inv_id', 'id');
    }
}
