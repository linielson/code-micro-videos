<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Genre extends Model
{
    use SoftDeletes, Traits\Uuid;

    // sets the fields are safe
    protected $fillable = ['name', 'is_active'];
    protected $dates = ['delete_at'];
    protected $casts = ['id' => 'string'];
}
