<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      Category::factory()->create([
        'name' => 'kemanusiaan',
        'description' => 'This is kemanusiaan category'
      ]);
      
      Category::factory()->create([
        'name' => 'kesehatan',
        'description' => 'This is kesehatan category'
      ]);
      
      Category::factory()->create([
        'name' => 'pendidikan',
        'description' => 'This is pendidikan category'
      ]);
      
      Category::factory()->create([
        'name' => 'tanggap bencana',
        'description' => 'This is tanggap bencana category'
      ]);
    }
}
