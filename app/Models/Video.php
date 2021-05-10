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

    public static function create(array $attributes = [])
    {
        try {
            \DB::beginTransaction();
            $obj = static::query()->create($attributes);
            static::handleRelations($obj, $attributes);
            //upload
            \DB::commit();
            return $obj;
        } catch (\Exception $e) {
            if (isset($obj)) {
                //delete
            }
            \DB::rollBack();
            throw $e;
        }
    }

    public function update(array $attributes = [], array $options = [])
    {
        try {
            \DB::beginTransaction();
            $saved = parent::update($attributes, $options);
            static::handleRelations($this, $attributes);
            if ($saved) {
                //upload new
                //delete old
            }
            \DB::commit();
            return $saved;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    protected static function handleRelations(Video $video, array $attributes)
    {
        if (isset($attributes['categories_id'])) {
            $video->categories()->sync($attributes['categories_id']);
        }
        if (isset($attributes['genres_id'])) {
            $video->genres()->sync($attributes['genres_id']);
        }
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTrashed();
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class)->withTrashed();
    }
}
