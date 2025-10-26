<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Traits\TokenResponse;

class LoginController extends Controller
{
  use TokenResponse;
  
  public function login(Request $request)
  {
    $credentials = $request->only('email', 'password');

    $rule = [
      'email' => ['required', 'email'],
      'password' => ['required', 'string', 'min:8'],
    ];

    $validator = Validator::make($credentials, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'message' => $validator->messages(),
      ], 422);
    }

    $user = User::where('email', $credentials['email'])->first();
    $token = JWTAuth::attempt($credentials);

    try {
      if (!$token) {
        if (!$user) {
          throw ValidationException::withMessages([
            trans('auth.email'),
          ])->status(404);
        } elseif (!Hash::check($request->password, optional($user)->getAuthPassword())) {
          throw ValidationException::withMessages([
            trans('auth.password')
          ])->status(401);
        } else {
          throw ValidationException::withMessages([
            trans('auth.failed'),
          ])->status(401);
        }
      }

      if (!$request->user()->hasVerifiedEmail()) {
        throw new AuthenticationException('Email not verified');
      }

      $user = auth()->user();

      $data['token'] = $this->respondWithToken($user->id, $token);
      
      $role = $user->role->name;

      switch($role) {
        case User::ROLE_ADMIN:
          return response()->json([
            'status' => 'success',
            'message' => 'Admin login success',
            'data' => $data,
          ], 200);

        case User::ROLE_COPYWRITER:
          return response()->json([
            'status' => 'success',
            'message' => 'Copywriter login success',
            'data' => $data,
          ], 200);

        case User::ROLE_BISMAR:
          return response()->json([
            'status' => 'success',
            'message' => 'Bismar login success',
            'data' => $data,
          ], 200);

        default:
          return response()->json([
            'status' => 'success',
            'message' => 'User login success',
            'data' => $data,
          ], 200);
      }
    } catch (ValidationException $e) {
      return response()->json([
          'status' => 'error',
          'message' => $e->validator->errors()->first(),
      ], $e->status);
    } catch (AuthenticationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 400);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function logout(Request $request)
  {
    try {
      JWTAuth::invalidate($request->token);

      return response()->json([
        'status' => 'success',
        'message' => 'User log out success'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function refreshToken()
  {
    $token = JWTAuth::getToken();
    
    if(!$token) { 
      return response()->json([
        'status' => 'error',
        'message' => 'Token not provided'
      ], 400);
    }

    try {
      $newToken = JWTAuth::refresh($token);
      return response()->json([
        'status' => 'success',
        'message' => 'Session extend success',
        'token' => $newToken,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
