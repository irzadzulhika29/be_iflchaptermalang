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
        Schema::table('volunteer_registrations', function (Blueprint $table) {
            $table->string('referral_code_used')->nullable()->after('google_drive_link');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('referral_code_used');
            $table->decimal('original_price', 10, 2)->nullable()->after('discount_amount');
            $table->decimal('final_price', 10, 2)->nullable()->after('original_price');
            $table->index('referral_code_used');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('volunteer_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'referral_code_used',
                'discount_amount',
                'original_price',
                'final_price',
            ]);
        });
    }
};
