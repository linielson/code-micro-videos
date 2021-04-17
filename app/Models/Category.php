<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // sets the fields are safe
    protected $fillable = ['name', 'description', 'is_active'];
}
