<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class CopyWriterSeeder extends Seeder
{

  private static $copyWriterId;

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $role = Role::where('name', 'copywriter')->first();
    $copyWriter = User::factory()->create([
      'username' => 'copyWritter',
      'email' => 'copyWritter@gmail.com',
      'password' => bcrypt('CopyWritter0123'),
      'email_verified_at' => now(),
      'remember_token' => Str::random(10),
      'profile_picture' => 'https://ik.imagekit.io/iflmalang/image/user/profile-picture/seederasya.jpeg',
      'background_picture' => 'https://ik.imagekit.io/iflmalang/image/user/background-picture/user-1712509710.png',
      'role_id' => $role->id,
    ]);

    self::$copyWriterId = $copyWriter->id;

    User::factory(2)->create([
      'role_id' => $role->id,
    ]);
  }

  public static function getId()
  {
    return self::$copyWriterId;
  }
}
