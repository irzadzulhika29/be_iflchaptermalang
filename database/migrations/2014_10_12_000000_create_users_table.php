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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('username')->unique()->collation('utf8mb4_bin'); // biar bisa validasi case sensitive
            $table->string('email')->unique();
            $table->string('google_id')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->enum('gender', ['male', 'female', 'not specified'])->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->text('about_me')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('background_picture')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('notice')->nullable()->default(false);
            $table->rememberToken();

            $table->foreignUuid('role_id')
              ->constrained('roles')
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
        Schema::dropIfExists('users');
    }
};
