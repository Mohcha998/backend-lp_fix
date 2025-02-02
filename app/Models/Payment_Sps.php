<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment_Sps extends Model
{
    use HasFactory;

    protected $table = 'payment__sps';

    protected $fillable = [
        'id_parent',
        'course',
        'link_pembayaran',
        'payment_type',
        'payment_method',
        'num_children',
        'no_invoice',
        'no_pemesanan',
        'voucher_code',
        'nama_bank',
        'nomor_kartu',
        'bulan_cicilan',
        'file',
        'description',
        'date_paid',
        'status_pembayaran',
        'biaya_admin',
        'total',
        'is_inv',
    ];

    public function parent()
    {
        return $this->belongsTo(ProspectParent::class, 'id_parent');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
