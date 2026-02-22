<?php

namespace ApiCore\Repositories;

use ApiCore\Exceptions\AuthException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthRepository
{
    public function __construct(protected $guard, protected $model)
    {
        $this->model = app($this->model);
    }


    public function loginFromUser($model)
    {
        $token = Auth::guard($this->guard)->login($model);
        throw_if(is_null($token), AuthException::class, trans('apicore::messages.invalid_token'));
        return $token;
    }

    public function findUserByUsername($username)
    {
        $auth = $this->model->where($username, request($username))->first();
        throw_if(is_null($auth), AuthException::class, trans('apicore::messages.user_not_found'));
        return $auth;
    }

    public function attemptWithUsername($username)
    {
        $auth = $this->findUserByUsername($username);
        return $this->loginFromUser($auth);
    }


    public function attemptCredentials(array $credentials): ?string
    {
        return auth($this->guard)->attempt($credentials) ?: null;
    }

    public function getAuthenticatedUser(): Authenticatable
    {
        return auth($this->guard)->user();
    }

    public function makeAuthVerified() : void
    {
        throw_if(!$this->model, AuthException::class, trans('apicore::messages.model_not_found'));
        $this->getAuthenticatedUser()->update(['is_verified' => true]);
    }

    public function logout(): void
    {
        auth($this->guard)->logout();
    }

    public function refreshToken(): string
    {
        return auth($this->guard)->refresh();
    }

    public function updateVerificationCode($verificationCodeDigits, $authUser = null)
    {
        $auth = $authUser ?? $this->getAuthenticatedUser();
        $auth->update([
            'verify_code' => generateOtp($verificationCodeDigits),
        ]);
        return $auth;
    }

    public function findOrCreateUser($username,$verificationCodeDigits)
    {
        return $this->model->updateOrCreate([$username => request($username)],[
            'verify_code' => generateOtp($verificationCodeDigits)
        ]);
    }
}
