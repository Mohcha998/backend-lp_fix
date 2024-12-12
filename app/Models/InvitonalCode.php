<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvitonalCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_code',
        'status_code'
    ];
}
