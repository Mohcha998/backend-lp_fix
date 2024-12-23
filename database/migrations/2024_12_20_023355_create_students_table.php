<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user_fthr')->constrained('parents');
            $table->integer('id_user_mthr')->constrained('parents');
            $table->integer('user_id')->constrained('users');
            $table->integer('id_program')->constrained('programs');
            $table->integer('id_course')->constrained('courses');
            $table->integer('id_branch')->contrained('branches');
            $table->integer('id_kelas')->constrained('kelas');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('asal_sekolah')->nullable();
            $table->string('perubahan')->nullable();
            $table->string('kelebihan')->nullable();
            $table->boolean('dirawat')->nullable();
            $table->string('kondisi')->nullable();
            $table->string('tindakan')->nullable();
            $table->integer('emergency_contact')->nullable();
            $table->string('hubungan_eme')->nullable();
            $table->integer('emergency_call')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
