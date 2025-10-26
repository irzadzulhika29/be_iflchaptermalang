<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class AdminSeeder extends Seeder
{
  private static $adminId;
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $role = Role::where('name', 'admin')->first();
    $admin = User::factory()->create([
      'username' => 'admin',
      'email' => 'iflchaptermalang@gmail.com',
      'password' => bcrypt('IflMalang0123'),
      'email_verified_at' => now(),
      'remember_token' => Str::random(10),
      'profile_picture' => 'https://ik.imagekit.io/iflmalang/image/user/profile-picture/admin-1712834809.png',
      'background_picture' => 'https://ik.imagekit.io/iflmalang/image/user/background-picture/admin-1712564528.png',
      'role_id' => $role->id,
    ]);

    self::$adminId = $admin->id;
  }

  public static function getId()
  {
    return self::$adminId;
  }
}
