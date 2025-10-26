<?php

namespace Database\Seeders;

use App\Models\BlogCategories;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlogCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      BlogCategories::factory()->create([
        'name' => 'technology',
        'description' => 'This is technology category for blog',
      ]);

      BlogCategories::factory()->create([
        'name' => 'science',
        'description' => 'This is science category for blog',
      ]);

      BlogCategories::factory()->create([
        'name' => 'health',
        'description' => 'This is health category for blog',
      ]);

      BlogCategories::factory()->create([
        'name' => 'education',
        'description' => 'This is education category for blog',
      ]);

      BlogCategories::factory()->create([
        'name' => 'politic',
        'description' => 'This is politic category for blog',
      ]);

      BlogCategories::factory()->create([
        'name' => 'sport',
        'description' => 'This is sport category for blog',
      ]);
    }
}
