<?php

namespace App\Traits;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Traits\TokenResponse;

trait GoogleLogin
{
  use TokenResponse;

  protected function login($credentials)
  {
    $rule = [
      'email' => ['required', 'email'],
      'password' => ['required', 'string', 'min:8'],
    ];

    $validator = Validator::make($credentials, $rule);

    if ($validator->fails()) {
      return response()->json(['error' => $validator->messages()], 200);
    }

    $user = User::where('email', $credentials['email'])->first();

    try {
      if (!$user->google_id) {
        $notGooleAccountToken = JWTAuth::fromUser($user);
        $token = $this->respondWithToken($user->id, $notGooleAccountToken);
      } else {
        if (!$token = JWTAuth::attempt($credentials)) {
          if (!$user) {
            throw ValidationException::withMessages([
              'credentials' => [trans('auth.failed')],
            ]);
          } elseif (!Hash::check($credentials->password, optional($user)->getAuthPassword())) {
            throw ValidationException::withMessages([
              'credentials' => [trans('auth.password')],
            ]);
          } else {
            throw ValidationException::withMessages([
              'credentials' => ['Invalid credentials'],
            ]);
          }
        }

        if (!$user->hasVerifiedEmail()) {
          throw new AuthenticationException('User email not verified.');
        }
        $userId = auth()->user()->id;

        $token = $this->respondWithToken($userId, $token);
      }
      
      return $token;
    } catch (\Exception $e) {
      return [
        'error' => 'could not create token',
        'message' => $e->getMessage()
      ];
    }
  }
}
