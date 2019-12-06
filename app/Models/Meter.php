<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Customer;
use App\Models\Zone;
use App\Models\Order;

class Meter extends Model
{
    use SoftDeletes;

    protected $table = 'meters';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'serialNumber',
        'address',
        'zone_id'
    ];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function zone() {
        return $this->belongsTo(Zone::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }
}
