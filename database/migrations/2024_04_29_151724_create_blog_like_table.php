<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blog_like', function (Blueprint $table) {
          $table->uuid('id')->primary();
            
          $table->foreignUuid('blog_id')
            ->constrained('blogs')
            ->onDelete('cascade');

          $table->foreignUuid('user_id')
            ->constrained('users')
            ->onDelete('cascade');

          $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blog_like');
    }
};
