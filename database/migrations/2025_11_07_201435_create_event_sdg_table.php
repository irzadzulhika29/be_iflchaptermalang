<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('event_sdg', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')
                ->constrained('events')
                ->onDelete('cascade');
            $table->foreignUuid('sdg_id')
                ->constrained('sdgs')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['event_id', 'sdg_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_sdg');
    }
};
