<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes, Traits\Uuid;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $dates = ['delete_at'];
    protected $fillable = ['name', 'description', 'is_active']; // sets the fields are safe
}
