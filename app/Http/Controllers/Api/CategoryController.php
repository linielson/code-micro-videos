<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;

class CategoryController extends BasicCrudController
{
    protected function model()
    {
        return Category::class;
    }

    protected function rules()
    {
        return ['name' => 'required|max:255', 'description' => 'nullable', 'is_active' => 'boolean'];
    }
}
