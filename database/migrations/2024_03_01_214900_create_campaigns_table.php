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
      Schema::create('campaigns', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('title')->unique();
        $table->string('slug');
        $table->text('short_description');
        $table->longText('body');
        $table->integer('view_count')->default(0);
        $table->enum('status', ['active', 'closed', 'pending'])->default('pending');
        $table->decimal('current_donation', 12, 2)->default(0);
        $table->decimal('target_donation', 12, 2);
        $table->dateTime('publish_date')->default(now());
        $table->dateTime('end_date');
        $table->text('note')->nullable();
        $table->string('receiver');
        $table->string('image')->nullable();

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
        Schema::dropIfExists('campaigns');
    }
};
