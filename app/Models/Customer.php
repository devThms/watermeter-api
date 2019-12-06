<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Meter;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'customers';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'NIT',
        'firstName', 
        'lastName', 
        'address',
        'telephone',
    ];

    public function meters() {
        return $this->hasMany(Meter::class);
    }
}
