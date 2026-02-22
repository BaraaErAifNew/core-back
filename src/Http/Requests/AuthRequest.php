<?php

namespace ApiCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthRequest extends FormRequest
{


    /**
     * Default rules (empty if you use custom methods)
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Shared logic for identity validation (mobile/email)
     */
    protected function identityRules(string $username): array
    {
        return [
            'required',
            Rule::when($username === 'mobile', ['regex:/^([0-9\s\-\+\(\)]*)$/']),
            Rule::when($username === 'email', ['email']),
        ];
    }

    public function loginRules(string $username): array
    {
        return [
            $username => $this->identityRules($username),
        ];
    }

    public function updateRules(string $username): array
    {
        return array_merge([
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
        ], $this->loginRules($username));
    }

    public function verifyRules(): array
    {
        return [
            'verify_code' => 'required|string|size:6', // Often codes are fixed length
        ];
    }
}
