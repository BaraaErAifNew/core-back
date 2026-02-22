<?php

namespace ApiCore\Http\Resources;

use Illuminate\Http\Request;

class AuthResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }

    public function serializeForVerify()
    {
        return $this->serializeForUser();
    }

    public function serializeForUser()
    {
        return $this->toArray(request: \request());
    }
}
