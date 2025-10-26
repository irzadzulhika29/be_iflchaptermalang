<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      // Create a 'user' role
      Role::factory()->create([
          'name' => 'user',
          'description' => 'This is user role',
      ]);

      // Create an 'admin' role
      Role::factory()->create([
          'name' => 'admin',
          'description' => 'This is admin role',
      ]);

      // Create a 'copywriter' role
      Role::factory()->create([
          'name' => 'copywriter',
          'description' => 'This is copywriter role',
      ]);

      // Create a 'shop manager' role
      Role::factory()->create([
          'name' => 'bismar',
          'description' => 'This is bismar role',
      ]);
  }
}
