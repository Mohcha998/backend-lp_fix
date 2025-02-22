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
        Schema::create('invitonal_codes', function (Blueprint $table) {
            $table->id();
            $table->integer('id_cabang')->constrained('branches');
            $table->string('voucher_code');
            $table->integer('qty')->nullable();
            $table->boolean('status_voc')->default(false);
            $table->integer('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitonal_codes');
    }
};
