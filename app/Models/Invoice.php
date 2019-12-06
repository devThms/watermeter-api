<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CashReceipt;
use App\Models\User;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cash_receipt_id',
        'user_id',
        'ammount'
    ];

    public function cashReceipt() {
        return $this->belongsTo(CashReceipt::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
