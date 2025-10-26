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
        Schema::create('campaign_category', function (Blueprint $table) {
          $table->uuid('id')->primary();
          
          $table->foreignUuid('campaign_id')
            ->constrained('campaigns')
            ->onDelete('cascade');
            
            $table->foreignUuid('category_id')
            ->constrained('categories')
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
        Schema::dropIfExists('campaign_category');
    }
};
