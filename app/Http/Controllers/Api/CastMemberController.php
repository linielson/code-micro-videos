<?php

namespace App\Http\Controllers\Api;

use App\Models\CastMember;

class CastMemberController extends BasicCrudController
{
    protected function model()
    {
        return CastMember::class;
    }

    protected function rules()
    {
        return [
            'name' => 'required|max:255',
            'type' => 'required|in:' .implode(',', [CastMember::TYPE_ACTOR, CastMember::TYPE_DIRECTOR])
        ];
    }
}
