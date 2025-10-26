<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Blog;
use App\Models\BlogCategories;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $this->call(BlogCategoriesSeeder::class);

      $technology = BlogCategories::where('name', 'technology')->first();
      $science = BlogCategories::where('name', 'science')->first();
      $health = BlogCategories::where('name', 'health')->first();
      $education = BlogCategories::where('name', 'education')->first();
      $politic = BlogCategories::where('name', 'politic')->first();
      $sport = BlogCategories::where('name', 'sport')->first();

      $copyWriterId = CopyWriterSeeder::getId();

      Blog::factory()->create([
        'title' => 'Tips Membuat REST API Yang Baik',
        'slug' => Str::slug('Tips Membuat REST API Yang Baik'),
        'content' => 'Untuk membuat REST API yang baik, pastikan desainnya konsisten dan intuitif dengan endpoint yang jelas. Gunakan metode HTTP secara tepat dan berikan respons dalam format standar seperti JSON. Sediakan dokumentasi yang komprehensif dan pastikan keamanan API dengan autentikasi yang tepat. Terakhir, lakukan pemeliharaan rutin untuk menjaga ketersediaan dan kinerja yang optimal.',
        'author_id' => $copyWriterId,
        'like' => 50,
      ])->blog_categories()->attach([$technology->id, $science->id]);

      Blog::factory()->create([
        'title' => 'Manfaat Jogging Untuk Kesehatan',
        'slug' => Str::slug('Manfaat Jogging Untuk Kesehatan'),
        'content' => 'Jogging bermanfaat untuk meningkatkan kekuatan otot, daya tahan jantung, dan metabolisme. Selain membakar kalori, aktivitas ini juga mengurangi risiko penyakit jantung, diabetes, dan tekanan darah tinggi. Selain manfaat kesehatan fisik, jogging juga meningkatkan kualitas tidur, mengurangi stres, dan meningkatkan kesejahteraan mental secara keseluruhan.',
        'author_id' => $copyWriterId,
        'like' => 70,
      ])->blog_categories()->attach([$health->id, $education->id]);

      Blog::factory()->create([
        'title' => 'Real Madrid VS Man City: 6 Gol Tercipta, Laga Berakhir 3-3',
        'slug' => Str::slug('Real Madrid VS Man City: 6 Gol Tercipta, Laga Berakhir 3-3'),
        'content' => 'Real Madrid vs Manchester City pada laga leg pertama perempatfinal Liga Champions digelar di Stadion Santiago Bernabeu, Rabu (10/4) dini hari WIB. The Citizens bisa mencuri gol cepat ketika laga baru berjalan dua menit. Bernardo Silva bikin Man City unggul dari sepakan bebas. Madrid bisa menyetarakan angka dari bunuh diri Ruben Dias di menit ke-12. Madrid bisa berbalik memimpin dua menit berselang lewat aksi Rodrygo di menit ke-14. Skor 2-1 untuk keunggulan Madrid bertahan hingga babak pertama tuntas. Gol Phil Foden di menit ke-66 bikin kedudukan kembali imbang. Josko Gvardiol mengembalikkan keunggulan Man City setelah bikin gol di menit ke- 71.',
        'author_id' => $copyWriterId,
        'like' => 90,
      ])->blog_categories()->attach([$politic->id, $sport->id]);

      Blog::factory()->create([
        'title' => 'Di Papan Catur Ada Berapa Menteri? Pahami Jumlah dan Aturan Geraknya',
        'slug' => Str::slug('Di Papan Catur Ada Berapa Menteri? Pahami Jumlah dan Aturan Geraknya'),
        'content' => 'Menteri (atau juga dikenal dengan Ratu, Queen, dan Ster) adalah buah catur yang paling kuat. Dikutip dari Piececlopedia, Menteri atau Ster dapat bergerak secara ortogonal atau diagonal. Ia dapat mengakhiri pergerakannya dengan menempati ruang kosong atau menangkap buah catur musuh. Dalam pengertian lain, Menteri adalah buah catur yang dapat bergerak ke segala arah, baik secara vertikal, horizontal, maupun diagonal. Pada awal permainan, masing-masing pemain memiliki satu buah Menteri yang posisinya berada tepat di samping Raja atau King.',
        'author_id' => $copyWriterId,
        'like' => 30,
      ])->blog_categories()->attach([$science->id, $technology->id]);
    }

    public static function getBlogId()
    {
        return Blog::pluck('id')->toArray();
    }
}
