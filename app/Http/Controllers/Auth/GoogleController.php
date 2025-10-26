<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use App\Traits\GoogleLogin;
use App\Traits\TokenResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Role;
use App\Models\User;

class GoogleController extends Controller
{
  use TokenResponse, GoogleLogin;

  private $imageService;

  public function __construct(ImageService $imageService)
  {
      $this->imageService = $imageService;
  }

  public function redirectToGoogle()
  {
    $authUrl = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

    return response()->json([
      'auth_url' => $authUrl,
    ]);
  }

  public function handleGoogleCallback()
  {
    try {
      $googleUser = Socialite::driver('google')->stateless()->user();
      $user = User::where('email', $googleUser->getEmail())->first();
      
      if (!$user) {
        $user_name = Str::slug($googleUser->getName());

        $duplicateUser = User::where('username', $user_name)->exists();
        if($duplicateUser) {
          $user_name = $user_name .  mt_rand(1000, 9999);
        }
  
        $profile_picture_folder = "image/user/profile-picture";
        $profile_picture_file = $googleUser->getAvatar();
        $profile_picture_file_name = $user_name;
        $profile_picture_tags = ["user", "profile_picture"];
        $profile_picture_url = $this->imageService->uploadFile($profile_picture_folder, $profile_picture_file, $profile_picture_file_name, $profile_picture_tags);

        $user = User::create([
          'name' => $googleUser->getName(),
          'username' => $googleUser->getNickname() ? strtolower($googleUser->getNickname()) : $user_name,
          'email' => $googleUser->getEmail(),
          'google_id' => $googleUser->getId(),
          'profile_picture'=> $profile_picture_url,
          'password' => Hash::make(12345678),
          'email_verified_at' => now(),
          'role_id' => Role::where('name', 'user')->first()->id,
          'notice' => $duplicateUser ? true : false,
        ]);
      }

      $token = $this->login([
        'email' => $user->email, 
        'password' => '12345678'
      ]);

      if ($user->wasRecentlyCreated) {
        return response()->json([
          'status' => 'success',
          'message' => 'Register with google account success',
          'notice' => $user->notice == 1 ? true : false,
          'data' => $token,
        ], 200);
      } else {
        if ($user->google_id) {
          return response()->json([
            'status' => 'success',
            'message' => 'Login with google account success',
            'notice' => $user->notice == 1 ? true : false,
            'data' => $token,
          ], 200);
        } else {
          return response()->json([
            'status' => 'success',
            'message' => 'Login with email success',
            'data' => $token,
          ], 200);
        }
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
