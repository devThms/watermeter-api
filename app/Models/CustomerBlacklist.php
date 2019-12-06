<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order;

class CustomerBlacklist extends Model
{
    use SoftDeletes;

    protected $table = 'customer_blacklists';
    protected $dates = ['deleted_at'];

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
