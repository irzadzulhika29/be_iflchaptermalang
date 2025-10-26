<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
  private static $userId;
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $role = Role::where('name', 'user')->first();
    $user = User::factory()->create([
      'username' => 'user',
      'email' => 'user@gmail.com',
      'password' => bcrypt('User0123'),
      'email_verified_at' => now(),
      'remember_token' => Str::random(10),
      'profile_picture' => 'https://ik.imagekit.io/iflmalang/image/user/profile-picture/user-1711180999.jpg',
      'background_picture' => 'https://ik.imagekit.io/iflmalang/image/user/background-picture/user-1711180999.png',
      'role_id' => $role->id,
    ]);

    self::$userId = $user->id;

    User::factory(2)->create([
      'role_id' => $role->id,
    ]);
  }

  public static function getId()
  {
    return self::$userId;
  }
}
