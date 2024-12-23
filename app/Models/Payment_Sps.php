<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment_Sps extends Model
{
    use HasFactory;

    protected $table = 'payment__sps';

    protected $fillable = [
        'id_parent',
        'link_pembayaran',
        'payment_type',
        'payment_method',
        'no_invoice',
        'no_pemesanan',
        'date_paid',
        'status_pembayaran',
        'biaya_admin',
        'total',
        'is_inv',
    ];
}
