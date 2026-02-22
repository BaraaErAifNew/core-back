<?php

namespace ApiCore\Http\Controllers;

use ApiCore\Exceptions\AuthException;
use ApiCore\Http\Requests\AuthRequest;
use ApiCore\Http\Resources\AuthResource;
use ApiCore\Repositories\AuthRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $model;
    protected $resource = AuthResource::class;
    protected $request = AuthRequest::class;
    protected $repository = AuthRepository::class;
    protected $guard;
    protected $username = 'mobile';
    protected array $credentials = [];
    protected $verificationCodeDigits = 4;

    public function __construct()
    {
        $this->repository = new $this->repository($this->guard, $this->model);
    }

    public function login(Request $request)
    {
        $authRequest = app($this->request);
        $request->validate($authRequest->loginRules($this->username));
        $auth = $this->repository->findOrCreateUser($this->username, $this->verificationCodeDigits);
        return successResponse(trans('apicore::messages.login_successfully'), [
            'auth' => new $this->resource($auth),
        ]);
    }

    public function beforeAuthenticated(Request $request)
    {

    }

    public function authenticated($auth, Request $request)
    {

    }

    /**
     * @throws \Throwable
     */
    public function verify(Request $request)
    {
        $this->beforeAuthenticated($request);
        $authRequest = app($this->request);
        $request->validate($authRequest->updateRules($this->username));
        if ($request->has('password'))
            $token = $this->repository->attemptCredentials(
                $request->only($this->username, 'password')
            );
        else
            $token = $this->repository->attemptWithUsername($this->username);

        $auth = $this->repository->getAuthenticatedUser();
        $this->authenticated($auth, $request);

        throw_if(
            $auth->verify_code != $request->verify_code,
            AuthException::class,
            trans('apicore::messages.invalid_verification_code'
            ));

        $this->repository->makeAuthVerified();
        return successResponse(
            trans('apicore::messages.auth_verified_successfully'),
            [
                'auth' =>  $this->resource::make($auth->refresh())->serializeForVerify(),
                'token' => $token,
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationCode(Request $request)
    {
        $auth = $this->repository->updateVerificationCode(
            $this->verificationCodeDigits,
            $this->usernameUser()
        );
        //send otp notify
        return successResponse(
            trans('apicore::messages.verification_code_sent_successfully'),
            new $this->resource($auth)
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->repository->logout();
        return successResponse(trans('apicore::messages.logout_successfully'));
    }

    /**
     * @return mixed
     */
    public function usernameUser()
    {
        return $this->repository->findUserByUsername($this->username);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $authRequest = app($this->request);
        $request->validate($authRequest->updateRules($this->username));
        $auth = $this->repository->getAuthenticatedUser();
        $attributes = $request->all();

        if ($auth->{$this->username} != $request->{$this->username})
            $attributes['is_verified'] = false;

        $auth->update($attributes);
        return successResponse(trans('apicore::messages.updated_successfully'),[
            'auth' => new $this->resource($auth),
        ]);
    }

    public function profile()
    {
        return successResponse(trans('apicore::messages.profile_shown_successfully'),[
            'auth' => new $this->resource($this->repository->getAuthenticatedUser()),
        ]);
    }

    public function delete(Request $request)
    {
        customer_auth()->delete();

        return successResponse(trans('apicore::messages.deleted_successfully'));
    }

}
