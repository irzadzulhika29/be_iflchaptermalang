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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username')->default('anonymous');
            $table->string('content');
            $table->uuid('base_comment_id')->nullable();
            $table->integer('like')->default(0);
            $table->timestamps();

            $table->foreignUuid('user_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('cascade');

            $table->foreignUuid('blog_id')
              ->constrained('blogs')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
