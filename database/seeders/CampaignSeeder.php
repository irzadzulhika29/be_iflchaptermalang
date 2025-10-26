<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $this->call(CategorySeeder::class);

      $admin = User::where('username', 'admin')->first();
      $pendidikan = Category::where('name', 'pendidikan')->first();
      $kemanusiaan = Category::where('name', 'kemanusiaan')->first();
      $kesehatan = Category::where('name', 'kesehatan')->first();
      $tanggap_bencana = Category::where('name', 'tanggap bencana')->first();

      Campaign::factory()->create([
        'title' => 'Galang Dana Makan Siang Gratis',
        'slug' => Str::slug('Galang Dana Makan Siang Gratis'),
        'short_description' => 'Penggalangan dana untuk makan siang gratis',
        'body' => 'Penggalangan dana ini ditujukkan untuk menyediakan makan siang gratis di berbagai sekolah di seluruh Indonesia',
        'status' => 'active',
        'current_donation' => 0.00,
        'target_donation' => 3000000.00,
        'end_date' => Carbon::now()->addMonths(3),
        'note' => 'Untuk anak sekolah, usia 6 - 17 tahun',
        'receiver' => 'Yayasan Galang Dana',
        'image' => 'https://ik.imagekit.io/iflmalang/image/campaign/peduli_kasih_panti_asuhan-1711324925.jpg',
        'user_id' => $admin->id,
      ])->categories()->attach([$pendidikan->id, $kemanusiaan->id]);
      
      Campaign::factory()->create([
        'title' => 'Galang Dana Gizi Sehat Anak',
        'slug' => Str::slug('Galang Dana Gizi Sehat Anak'),
        'short_description' => 'Penggalangan dana untuk meningkatkan kualitas gizi anak-anak',
        'body' => 'Penggalangan dana ini ditujukkan untuk menyediakan fasilitas dan ketersediaan makanan dan kebutuhan pokok bagi anak usia pertumbuhan',
        'status' => 'active',
        'current_donation' => 0.00,
        'target_donation' => 3000000.00,
        'end_date' => Carbon::now()->addMonths(3),
        'note' => 'Untuk anak usia pertumbuhan, usia 6 - 12 tahun',
        'receiver' => 'Yayasan Galang Dana',
        'image' => 'https://ik.imagekit.io/iflmalang/image/campaign/peduli_kasih_panti_asuhan-1711324925.jpg',
        'user_id' => $admin->id,
      ])->categories()->attach([$kesehatan->id, $kemanusiaan->id, $tanggap_bencana->id]);
    }

    public static function getCampaignId()
    {
        return Campaign::pluck('id')->toArray();
    }
}
