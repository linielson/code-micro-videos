<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes, Traits\Uuid;

    const RATING_LIST = ['L', '10', '12', '14', '16', '18'];

    public $incrementing = false;
    protected $keyType = 'string';
    protected $dates = ['deleted_at'];
    protected $fillable = ['title', 'description', 'year_launched', 'opened', 'rating', 'duration'];
    protected $casts = ['opened' => 'boolean', 'year_launched' => 'integer', 'duration' => 'integer'];
}