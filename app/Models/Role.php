<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Role extends Model
{
    use SoftDeletes;

    protected $table = 'roles';
    protected $dates = ['deleted_at'];
    
    protected $fillable = [
        'description',
        'status'
    ];

    public function users() {
        return $this->hasMany(User::class);
    }

}
