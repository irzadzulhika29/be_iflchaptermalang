<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Role;
use App\Models\Donation;

class ProfileController extends Controller
{
  private $imageService;

  public function __construct(ImageService $imageService)
  {
      $this->imageService = $imageService;
  }

  public function getDonationByUserId(string $id)
  {
    $donations = Donation::where('user_id', $id)
      ->with(['campaign', 'transaction'])
      ->get();

    $user_donation = [];
    foreach ($donations as $donation) {
      $campaign = $donation->campaign;
      $transaction = $donation->transaction;

      $user_donation[] = [
        'campaign' => [
          'id' => $campaign->id, 
          'title' => $campaign->title,
          'current_donation' => $campaign->current_donation,
          'image' => $campaign->image ?? null,
        ],

        'donation' => [
          'donation_id' => $donation->id,
          'name' => $donation->name,
          'email' => $donation->email,
          'message' => $donation->donation_message,
          'donation_amount' => $donation->donation_amount,
          'donation_message' => $donation->donation_message,
          'donation_time' => $transaction->transaction_success_time ?? 'null',
          'payment_method' => $transaction->payment_method ?? 'unpaid',
          'payment_url' => $transaction->payment_url ?? 'null',
          'status' => $donation->status,
        ],
      ];
    }
    return $user_donation;
  }

  public function showProfile()
  {
    try {
      $user = auth()->user();

      $role = Role::find($user->role_id);
      $user->role = $role->name;

      $user->likedBlogs->makeHidden('pivot');
      $user->likedComments->makeHidden('pivot');

      $donation = $this->getDonationByUserId($user->id);

      return response()->json([
        'status' => 'success',
        'message' => 'Get profile success',
        'data' => $user,
        'donation' => $donation
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function updateProfile(Request $request)
  {
    $user = auth()->user();

    $data = $request->only('name', 'username', 'phone_number', 'gender', 'birth_date', 'address', 'about_me', 'profile_picture', 'background_picture');
    $data['anonymous'] = $request->input('anonymous') ?? 0;
    $rule = [
      'name' => ['nullable', 'string'],
      'username' => ['nullable', 'string', 'min:5', 'max:50', 'regex:/^\S*$/', 'unique:users,username,' . $user->id],
      'phone_number' => ['nullable', 'numeric'],
      'gender' => ['nullable', 'string', 'in:male,female,not specified'],
      'birth_date' => ['nullable', 'date'],
      'address' => ['nullable', 'string'],
      'about_me' => ['nullable', 'string'],
      'profile_picture' => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
      'background_picture' => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
    ];
    $validation_message = ['username.regex' => 'The username field must not contain spaces.'];

    $validator = Validator::make($data, $rule, $validation_message);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }
    try {
      if (isset($data['profile_picture'])) {
        $profile_picture_folder = "image/user/profile-picture";

        if($user->profile_picture) {
          $this->imageService->deleteFile($profile_picture_folder, $user->profile_picture);
        }

        $profile_picture_file = $data['profile_picture'];
        $profile_picture_file_name = $user->username;
        $profile_picture_tags = ["user", "profile-picture"];

        $profile_picture_url = $this->imageService->uploadFile($profile_picture_folder, $profile_picture_file, $profile_picture_file_name, $profile_picture_tags);
      }

      if (isset($data['background_picture'])) {
        $background_picture_folder = "image/user/background-picture";
        
        if($user->background_picture) {
          $this->imageService->deleteFile($background_picture_folder, $user->background_picture);
        }
        
        $background_picture_file = $data['background_picture'];
        $background_picture_file_name = $user->username;
        $background_picture_tags = ["user", "background-picture"];
        $background_picture_url = $this->imageService->uploadFile($background_picture_folder, $background_picture_file, $background_picture_file_name, $background_picture_tags);
      }

      if (isset($data['username'])) {
        $updatedUsername = true;
      }

      $user->update([
        'username' => $data['username'] ?? $user->username,
        'name' => $data['name'] ?? $user->name,
        'address' => $data['address'] ?? $user->address,
        'phone_number' => $data['phone_number'] ?? $user->phone_number,
        'about_me' => $data['about_me'] ?? $user->about_me,
        'profile_picture' => $profile_picture_url ?? $user->profile_picture,
        'background_picture' => $background_picture_url ?? $user->background_picture,
        'notice' => isset($updatedUsername) ? false : $user->notice,
      ]);

      return response()->json([
        'status' => 'success',
        'message' => 'Update profile success',
        'data' => $user,
      ], 200);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function updatePassword(Request $request)
  {
    try {
      $data = $request->only('current_password', 'new_password', 'new_password_confirmation');
      $validator = Validator::make($data, [
        'current_password' => ['required', 'min:8'],
        'new_password' => ['required', 'confirmed', 'min:8', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[\W]).*$/'],        
      ], 
      [
        'password.regex' => 'Password harus berisi setidaknya: 1 huruf kecil, 1 huruf besar, 1 angka, dan 1 simbol (seperti !, @, $, #, ^, dll)'
      ]);

      if ($validator->fails()) {
        $errors = $validator->messages();
  
        if ($errors->has('new_password')) {

          $errors->add('detail', 'Password harus berisi setidaknya : 1 huruf kecil, 1 huruf besar, 1 angka, dan 1 simbol (seperti !, @, $, #, ^, dll)');
        }
  
        if ($validator->fails()) {
          return response()->json([
            'status' => 'error',
            'error' => $errors,
          ], 422);
        }
      }

      $user = auth()->user();

      if (!Hash::check($data['current_password'], $user->password)) {
        return response()->json([
          'status' => 'error',
          'message' => 'Current password is incorrect.',
        ], 401);
      }

      if (Hash::check($data['new_password'], $user->password)) {
        return response()->json([
          'status' => 'error',
          'message' => 'Your new password is the same as the current password',
        ], 400);
      }

      $user->password = Hash::make($data['new_password']);
      $user->save();

      return response()->json([
        'status' => 'success',
        'message' => 'Password changed successfully.',
      ], 200);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}