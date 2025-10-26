<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProfileController;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{
  private $imageService;
  private $profileController;

  public function __construct(ImageService $imageService, ProfileController $profileController)
  {
      $this->imageService = $imageService;
      $this->profileController = $profileController;
  }

  public function getAllUsers()
  {
    $users = User::all();

    foreach ($users as $user) {
      $role = Role::find($user->role_id);
      $user->role = $role->name;
    }

    $users_total = $users->count();
    $latest_update = User::latest()->value('updated_at');

    try {    
      return response()->json([
        'status' => 'success',
        'message' => 'Get all user success',
        'total' => $users_total,
        'data' => [
          'latest_update' => $latest_update,
          'users' => $users
        ],
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function getUserById(string $id)
  {
    $user = User::where('id', $id)->first();

    if (!$user) {
      return response()->json([
        'status' => 'error',
        'message' => 'user not found with the given id',
      ], 404);
    }

    try {
      $role = Role::find($user->role_id);
      $user->role = $role->name;

      $user->likedBlogs->makeHidden('pivot');
      $user->likedComments->makeHidden('pivot');

      $donation = $this->profileController->getDonationByUserId($user->id);

      return response()->json([
        'status' => 'success',
        'message' => 'Get user by id success',
        'data' => $user,
        'donation' => $donation,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function updateUser(Request $request, string $id) 
  {
    $user = User::find($id);

    if (!$user) {
      return response()->json([
        'status' => 'error',
        'message' => 'User not found with the given id',
      ], 404);
    }

    $data = $request->only('name', 'username', 'password', 'phone_number', 'gender', 'birth_data', 'address', 'about_me', 'profile_picture', 'background_picture', 'role_id');

    $rule = [
      'name' => ['nullable', 'string'],
      'username' => ['nullable', 'string', 'unique:users,username,' . $user->id],
      'password' => ['nullable', 'min:8', 'confirmed', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[\W]).*$/'],
      'phone_number' => ['nullable', 'numeric'],
      'gender' => ['nullable', 'string', 'in:male,female,not specified'],
      'birth_date' => ['nullable', 'date'],
      'address' => ['nullable', 'string'],
      'about_me' => ['nullable', 'string'],
      'profile_picture' => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
      'background_picture' => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
      'role_id' => ['nullable', 'uuid'],
    ];

    $validator = Validator::make($data, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    try {
      DB::beginTransaction();

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

      $inputRole = $data['role_id'];

      if($inputRole) {
        $role = Role::where('id', $inputRole)->first();

        if (!$role) {
          return response()->json([
            'status' => 'error',
            'message' => 'role with id ' . (string) $inputRole . ' not found',
          ], 404);
        }
      }

      $user->update([
        'name' => $data['name'] ?? $user->name,
        'username' => $data['username'] ?? $user->username,
        'phone_number' => $data['phone_number'] ?? $user->phone_number,
        'gender' => $data['gender'] ?? $user->gender,
        'birth_date' => $data['birth_date'] ?? $user->birth_date,
        'address' => $data['address'] ?? $user->address,
        'about_me' => $data['about_me'] ?? $user->about_me,
        'profile_picture' => $profile_picture_url ?? $user->profile_picture,
        'background_picture' => $background_picture_url ?? $user->background_picture,
        'role_id' => $inputRole ?? $user->role_id,
        'notice' => isset($updatedUsername) ? false : $user->notice,
      ]);

      DB::commit();

      $role = Role::find($user->role_id);
      $user->role = $role->name;

      return response()->json([
        'status' => 'success',
        'message' => 'Update user success',
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

  public function deleteUser(string $id)
  {
    $user = User::find($id);

    if (!$user) {
      return response()->json([
        'status' => 'error',
        'message' => 'User not found with the given id'
      ], 404);
    }

    try {
      $profile_picture_folder = "image/user/profile-picture";
        if ($user->profile_picture) {
          $this->imageService->deleteFile($profile_picture_folder, $user->profile_picture);
      }

      $background_picture_folder = "image/user/background-picture";
        if ($user->background_picture) {
          $this->imageService->deleteFile($background_picture_folder, $user->background_picture);
      }

      $user->delete();

      return response()->json([
        'status' => 'success',
        'message' => 'Delete user success',
        'data' => $user,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
