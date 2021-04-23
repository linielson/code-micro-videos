<?php

namespace App\Http\Controllers\Api;

use App\Models\Video;
use Illuminate\Http\Request;
class VideoController extends BasicCrudController
{
    protected function model()
    {
        return Video::class;
    }

    protected function rules()
    {
        return [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|date_format:Y',
            'opened' => 'boolean',
            'rating' => 'required|in:' .implode(',', Video::RATING_LIST),
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id',
            'genres_id' => 'required|array|exists:genres,id'
        ];
    }

    public function store(Request $request)
    {
        $validateData = $this->validate($request, $this->rules());
        $self = $this;
        $obj = \DB::transaction(function() use ($request, $validateData, $self) {
            $obj = $this->model()::create($validateData);
            $self->handleRelations($obj, $request);
            return $obj;
        });

        $obj->refresh();
        return $obj;
    }

    public function update(Request $request, $id)
    {
        $obj = $this->findOrFail($id);
        $validateData = $this->validate($request, $this->rules());
        $self = $this;
        $obj = \DB::transaction(function() use ($request, $validateData, $self, $obj) {
            $obj->update($validateData);
            $self->handleRelations($obj, $request);
            return $obj;
        });

        return $obj;
    }

    protected function handleRelations($video, Request $request)
    {
        $video->categories()->sync($request->get('categories_id'));
        $video->genres()->sync($request->get('genres_id'));
    }
}
