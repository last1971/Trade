<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistrationRequest;
use App\User;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * Class AuthController
 * @package App\Http\Controllers\Api
 */
class AuthController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return ResponseFactory|Response
     */
    public function login(LoginRequest $request)
    {
        $user = User::query()->whereEmail($request->email)->first();
        $status = 200;
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Personal')->accessToken;
                $response = [
                    'token' => $token,
                    'user' => $user->only(['name', 'email']),
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ];
            } else {
                $response = [
                    'message' => 'The given data was invalid.',
                    'errors' => ['password' => ['Password missmatch']]
                ];
                $status = 422;
            }
        } else {
            $response = [
                'message' => 'The given data was invalid.',
                'errors' => ['email' => ['User does not exist']]
            ];
            $status = 422;
        }
        return response($response, $status);
    }

    /**
     * @param RegistrationRequest $request
     * @return ResponseFactory|Response
     */
    public function register(RegistrationRequest $request)
    {
        $user = User::query()->create($request->all());
        $user->assignRole('guest');
        $token = $user->createToken('Personal')->accessToken;
        $response = [
            'token' => $token,
            'user' => $user->only(['name', 'email']),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been succesfully logged out!'];
        return response($response, 200);
    }

    /**
     * @return JsonResponse
     */
    public function refresh()
    {
        return response()->json([
            'user' => request()->user()->only(['name', 'email']),
            'roles' => request()->user()->getRoleNames(),
            'permissions' => request()->user()->getAllPermissions()->pluck('name'),
        ]);
    }
}
