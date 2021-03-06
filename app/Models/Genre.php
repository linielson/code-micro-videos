<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Genre extends Model
{
    use SoftDeletes, Traits\Uuid;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'is_active']; // sets the fields are safe
    protected $casts = ['is_active' => 'boolean'];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
