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
        Schema::create('volunteer_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Foreign key ke users (user yang mendaftar)
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Data dari profile user (pre-filled dari chat form)
            $table->string('email');
            $table->string('name'); // Nama Lengkap
            $table->string('phone_number')->nullable(); // No HP (WhatsApp)
            
            // Data input baru user untuk volunteer registration
            $table->string('university')->nullable(); // Asal Universitas
            $table->string('line_id')->nullable(); // ID Line
            $table->string('choice_1')->nullable(); // Pilihan 1
            $table->string('choice_2')->nullable(); // Pilihan 2
            $table->text('google_drive_link')->nullable(); // Link Folder Google Drive Berkas Pendaftaran
            
            // Status registrasi
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            
            // Event/program info
            $table->string('event_name')->default('Close the Gap IFL Chapter Malang 2025');
            $table->year('event_year')->default(2025);
            
            // Timestamps
            $table->timestamps();
            
            // Index untuk performa query
            $table->index('user_id');
            $table->index('status');
            $table->index('event_year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('volunteer_registrations');
    }
};
