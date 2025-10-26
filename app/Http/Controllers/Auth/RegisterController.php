<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendActivationEmail;
use App\Models\User;
use App\Models\Role;

class RegisterController extends Controller
{
  public function register(Request $request)
  {
    $data = $request->only('username', 'email', 'password', 'password_confirmation');
    $rule = [
      'email' => ['required', 'email', 'max:255', 'unique:users'],
      'username' => ['required', 'string', 'min:5', 'max:60', 'regex:/^\S*$/', 'unique:users'],
      'password' => ['required', 'confirmed', 'min:8', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[\W]).*$/'],
    ];
    $validation_message = [
      'username.regex' => 'The username field must not contain spaces.',
      'password.regex' => 'Passwords must contain at least: 1 lowercase letter, 1 uppercase letter, 1 number, and 1 symbol (such as !, @, $, #, ^, etc).'
    ];

    $validator = Validator::make($data, $rule, $validation_message);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    try {
      DB::beginTransaction();

      $user = User::create([
        'username' => $data["username"],
        'email' => $data["email"],
        'password' => Hash::make($data["password"]),
        'role_id' => Role::where('name', User::ROLE_USER)->first()->id,
      ]);

      $role = Role::find($user->role_id);
      $user->role = $role->name;

      SendActivationEmail::dispatch($user);

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'User registered successfully, please check your email for verification.',
        'data' => $user,
      ], 201);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
