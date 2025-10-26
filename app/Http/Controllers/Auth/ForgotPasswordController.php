<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendResetPasswordLink;
use App\Models\User;

class ForgotPasswordController extends Controller
{
  public function sendResetLinkEmail(Request $request)
  {
    $data = $request->only('email');
    $rule = [
      'email' => ['required', 'email', 'max:255'],
    ];

    $validator = Validator::make($data, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'message' => $validator->messages(),
      ], 422);
    }

    $user = User::where('email', $request->email)->first();
    try {
      if (!$user) {
        return response()->json([
          'status' => 'failed',
          'message' => 'User not found with the given email',
        ], 404);
      } else {
        SendResetPasswordLink::dispatch($request->email);
        return response()->json([
          'status' => 'success',
          'message' => __('passwords.sent'),
        ], 200);
      }
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

  public function showResetForm(Request $request)
  {
    $token = $request->query('token');
    $email = $request->query('email');
    $titleHead = "Reset Password";

    return view("auth.reset-password", compact('titleHead', 'email', 'token'));
  }

  public function reset(Request $request)
  {
    $user_oldPassword = Password::broker()->getUser($request->only('email', 'token'))->password;

    $data = $request->only('token', 'email', 'password');
    $rule = [
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'username' => ['required', 'string', 'min:5', 'max:60', 'regex:/^\S*$/', 'unique:users'],
      'password' => ['required', 'confirmed', 'min:8', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[\W]).*$/'],
    ];
    $validation_message = [
      'password.regex' => 'Passwords must contain at least: 1 lowercase letter, 1 uppercase letter, 1 number, and 1 symbol (such as !, @, $, #, ^, etc).'
    ];

    $validator = Validator::make($data, $rule, $validation_message);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    if ($user_oldPassword && Hash::check($request->input('password'), $user_oldPassword)) {
      return back()->with(['error' => 'Your new password is the same as the current password!']);
    }

    try {
      Password::reset(
        $data,
        function ($user, $password) {
          $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
          ])->save();
        }
      );

      $redirect_link = "http://localhost:5173/login";
      $redirect_message = "Back to Login";

      return view("auth.notification",[
        "titleHead" => "Reset Password Successfully",
        "title" => "Reset Password Successfully",
        "message" => "Your password has been updated successfully. You can now login!",
        "isErrorImg" => false,
        "directLink" => $redirect_link,
        "titleLogin" => $redirect_message,
      ]);
    } catch (ValidationException $e) {
      return back()->with(['error' => $e->getMessage()]);
    } catch (\Exception $e) {
      return back()->with(['error' => $e->getMessage()]);
    }
  }
}
