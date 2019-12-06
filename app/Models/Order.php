<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Meter;
use App\Models\User;
use App\Models\CashReceipt;
use App\Models\CustomerBlacklist;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'meter_id',
        'user_id',
        'finalMeasure'
    ];

    // Accesor
    public function getPreviousMeasureAttribute() {
        $value = self::where([['meter_id', '=', $this->meter_id], ['month', '=', $this->month-1]])
                    ->pluck('finalMeasure')
                    ->all();
        
        if ($value == []) {
            return 0;
        } else {
            return $value[0];
        }
    }

    // mutator
    // public function setInitialMeasureAttribute($value) {
    //     $this->attributes['initialMeasure'] = $value;
    // }

    public function meter() {
        return $this->belongsTo(Meter::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function cashReceipt() {
        return $this->hasMany(CashReceipt::class);
    }

    public function customerBlacklist() {
        return $this->hasMany(CustomerBlacklist::class);
    }
}
