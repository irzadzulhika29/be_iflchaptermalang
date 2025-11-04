<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendActivationEmail;
use App\Models\User;

class EmailVerificationController extends Controller
{
  public function verify($id, Request $request)
  {
    $user = User::find($id);

    try {
      if (!hash_equals((string) $request->route('id'), (string) $id)) {
        throw new AuthorizationException;
      }

      if (!hash_equals((string) $request->route('hash'), sha1($user->email))) {
        throw new AuthorizationException;
      }

      $redirect_link = "https://iflchaptermalang.org/masuk";
      $redirect_message = "Back to Login";

      if (!$request->hasValidSignature()) {
        return view("auth.notification", [
          "titleHead" => "Email Verified Error",
          "title" => "Email verification failed",
          "message" => "Your email has not a valid signature",
          "isErrorImg" => true,
          "linkHref" => $redirect_link,
          "titleLogin" => $redirect_message,
        ]);
      }

      if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
      } else {
        return view("auth.notification", [
          "titleHead" => "Email Verified",
          "title" => "Your email was verified a moment ago",
          "message" => "You can login now!",
          "isErrorImg" => false,
          "directLink" => $redirect_link,
          "titleLogin" => $redirect_message,
        ]);
      }

      return view("auth.notification", [
        "titleHead" => "Email Verified",
        "title" => "Email has been verified",
        "message" => "Your email has been successfully verified. You can now login and access all features!",
        "isErrorImg" => false,
        "directLink" => $redirect_link,
        "titleLogin" => $redirect_message,
      ]);
    } catch (AuthorizationException $e) {
      return view("auth.notification", [
        "titleHead" => "Email Verified Error",
        "title" => "Authorization error",
        "message" => $e->getMessage(),
        "isErrorImg" => true,
        "directLink" => $redirect_link,
        "titleLogin" => $redirect_message,
      ]);
    } catch (\Exception $e) {
      return view("auth.notification", [
        "titleHead" => "Email Verified Error",
        "title" => "Error verifying email",
        "message" => $e->getMessage(),
        "isErrorImg" => true,
        "directLink" => $redirect_link,
        "titleLogin" => $redirect_message,
      ]);
    }
  }

  public function resend(Request $request)
  {
    $credentials = $request->only('email');

    $rule = ['email' => ['required', 'email', 'exists:users,email']];

    $validator = Validator::make($credentials, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    try {
      $user = User::where('email', $request->email)->first();

      if ($user->hasVerifiedEmail()) {
        return response()->json([
          'status' => 'success',
          'message' => 'Email have been verified, no verification action needed'
        ], 200);
      }

      SendActivationEmail::dispatch($user);

      return response()->json([
        'status' => 'success',
        'message' => 'Verification link has been sent to your email'
      ], 200);
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
