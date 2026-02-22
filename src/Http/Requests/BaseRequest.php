<?php

namespace ApiCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{

    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->storeRules(),
            'PUT', 'PATCH' => $this->updateRules(),
            default => [],
        };
    }

    public function storeRules(): array
    {
        return [];
    }

    public function updateRules(): array
    {
        return [];
    }
}
