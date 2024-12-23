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
        Schema::create('prospect_parents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->foreignId('id_cabang')->nullable()->constrained('branches')->onDelete('set null')->change();
            $table->foreignId('id_program')->nullable()->constrained('programs')->onDelete('set null')->change();
            $table->bigInteger('phone')->nullable()->unique();
            $table->string('source')->nullable();
            $table->foreignId('invoice_sp')->nullable()->constrained('payment__sps')->onDelete('set null')->change();
            $table->foreignId('invoice_prg')->nullable()->constrained('pembayaran_programs')->onDelete('set null')->change();
            $table->integer('call')->default(0);
            $table->date('tgl_checkin')->nullable();
            $table->integer('inv_id')->constrained('invitonal_codes')->nullable();
            $table->string('invitional_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_parents');
    }
};
