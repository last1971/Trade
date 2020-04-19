<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Notifications\PasswordChangeNotification;
use App\Notifications\PasswordResetNotification;
use App\PasswordReset;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class AuthController
 * @package App\Http\Controllers\Api
 */
class AuthController extends Controller
{
    /**
     * @param AuthRequest $request
     * @return ResponseFactory|Response
     */
    public function login(AuthRequest $request)
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
     * @param AuthRequest $request
     * @return ResponseFactory|Response
     */
    public function register(AuthRequest $request)
    {
        $request->merge(['password' => Hash::make($request->password)]);
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

    /**
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function forgot(AuthRequest $request)
    {
        $user = User::whereEmail($request->email)->first();
        $status = 200;
        $response = [
            'message' => 'Ссылка для сброса пароля была отправлена на Вашу почту!'
        ];
        if (!$user) {
            $response = [
                'message' => 'The given data was invalid.',
                'errors' => ['email' => ['User does not exist']]
            ];
            $status = 422;
        } else {
            $passwordReset = PasswordReset::query()->updateOrCreate(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => Str::random(60),
                    'created_at' => Carbon::now(),
                ]
            );
            $user->notify(new PasswordResetNotification($passwordReset->token));
        }
        return response()->json($response, $status);
    }

    /**
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function checkToken(AuthRequest $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);
        $status = 200;
        $passwordReset = PasswordReset::whereToken($request->token)->first();
        if (!$passwordReset) {
            $response = [
                'message' => 'The given data was invalid.',
                'errors' => ['token' => ['Токен не существует']]
            ];
            $status = 422;
        } else if (Carbon::parse($passwordReset->created_at)->addMinutes(60)->isPast()) {
            $passwordReset->delete();
            $response = [
                'message' => 'The given data was invalid.',
                'errors' => ['token' => ['Токен протух']]
            ];
            $status = 422;
        } else {
            $response = $passwordReset;
        }
        return response()->json($response, $status);
    }

    /**
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function reset(AuthRequest $request)
    {
        $passwordReset = PasswordReset::with('user')->where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();
        $status = 200;
        if (!$passwordReset) {
            $response = [
                'message' => 'The given data was invalid.',
                'errors' => ['email' => ['Токен или e-mail не существует']]
            ];
            $status = 404;
        } else if (!$passwordReset->user) {
            $response = [
                'message' => 'We cant find a user with that e-mail address.',
                'errors' => ['email' => ['e-mail не существует']]
            ];
            $status = 404;
        } else {
            $passwordReset->user->fill(['password' => Hash::make($request->password)])->save();
            $passwordReset->user->notify(new PasswordChangeNotification());
            $token = $passwordReset->user->createToken('Personal')->accessToken;
            $response = [
                'token' => $token,
                'user' => $passwordReset->user->only(['name', 'email']),
                'roles' => $passwordReset->user->getRoleNames(),
                'permissions' => $passwordReset->user->getAllPermissions()->pluck('name'),
            ];
            $passwordReset->delete();
        }
        return response()->json($response, $status);
    }

}
