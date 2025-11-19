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
            $table->dropColumn(['email', 'line_id', 'choice_1', 'choice_2', 'university']);

            $table->string('username_instagram')->nullable()->after('phone_number');
            $table->text('info_source')->nullable()->after('username_instagram'); // Tau info dari mana
            $table->text('motivation')->nullable()->after('info_source');
            $table->text('experience')->nullable()->after('motivation');
            $table->boolean('has_read_guidebook')->default(false)->after('experience');
            $table->boolean('is_committed')->default(false)->after('has_read_guidebook');
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
            $table->string('email')->after('user_id');
            $table->string('line_id')->nullable()->after('university');
            $table->string('choice_1')->nullable()->after('line_id');
            $table->string('choice_2')->nullable()->after('choice_1');

            $table->dropIndex(['has_read_guidebook']);
            $table->dropIndex(['is_committed']);
            $table->dropColumn([
                'username_instagram',
                'info_source',
                'motivation',
                'experience',
                'has_read_guidebook',
                'is_committed'
            ]);
        });
    }
};
