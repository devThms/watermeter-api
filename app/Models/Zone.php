<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Meter;

class Zone extends Model
{
    use SoftDeletes;

    protected $table = 'zones';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'dateMax'
    ];

    public function meters() {
        return $this->hasMany(Meter::class);
    }
}
