<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order;
use App\Models\User;
use App\Models\Invoice;

class CashReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'cash_receipts';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'ammount',
        'description'
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function invoice() {
        return $this->hasMany(Invoice::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
