<?php

namespace App\Http\Controllers\Api;

use App\Models\Video;
use App\Rules\GenresHasCategoriesRule;
use Illuminate\Http\Request;
class VideoController extends BasicCrudController
{
    private $rules;

    public function __construct()
    {
        $this->rules = [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|date_format:Y',
            'opened' => 'boolean',
            'rating' => 'required|in:' .implode(',', Video::RATING_LIST),
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
            'genres_id' => [
                'required',
                'array',
                'exists:genres,id,deleted_at,NULL'
            ],
            'thumb_file' => 'max:10240|mimetypes:image/jpg',
            'video_file' => 'max:102400|mimetypes:video/mp4',
        ];
    }

    public function store(Request $request)
    {
        $this->addRuleIfGenreHasCategories($request);
        $validateData = $this->validate($request, $this->rules());
        $obj = $this->model()::create($validateData);
        $obj->refresh();
        return $obj;
    }

    public function update(Request $request, $id)
    {
        $obj = $this->findOrFail($id);
        $this->addRuleIfGenreHasCategories($request);
        $validateData = $this->validate($request, $this->rules());
        $obj->update($validateData);
        return $obj;
    }

    protected function model()
    {
        return Video::class;
    }

    protected function rules()
    {
        return $this->rules;
    }

    protected function addRuleIfGenreHasCategories(Request $request)
    {
        $categoriesId = $request->get('categories_id');
        $categoriesId = is_array($categoriesId) ? $categoriesId : [];
        $this->rules['genre_id'][] = new GenresHasCategoriesRule($categoriesId);
    }
}
