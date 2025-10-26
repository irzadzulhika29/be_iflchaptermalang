<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BlogImages;
use App\Models\Blog;

class BlogImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $blogIds = BlogSeeder::getBlogId();
      $blog1 = Blog::find($blogIds[0]);
      $blog2 = Blog::find($blogIds[1]);
      $blog3 = Blog::find($blogIds[2]);
      $blog4 = Blog::find($blogIds[3]);

      BlogImages::factory()->create([
        'blog_id' => $blog1,
        'image_url' => 'https://ik.imagekit.io/iflmalang/image/blog/undefined/undefined-1712851116.png',
      ]);

      BlogImages::factory()->create([
        'blog_id' => $blog2,
        'image_url' => 'https://ik.imagekit.io/iflmalang/image/blog/undefined/undefined-1712851356.jpg',
      ]);

      BlogImages::factory()->create([
        'blog_id' => $blog3,
        'image_url' => 'https://ik.imagekit.io/iflmalang/image/blog/undefined/undefined-1712851462.jpg',
      ]);

      BlogImages::factory()->create([
        'blog_id' => $blog4,
        'image_url' => 'https://ik.imagekit.io/iflmalang/image/blog/undefined/undefined-1712851527.png',
      ]);
    }
}
