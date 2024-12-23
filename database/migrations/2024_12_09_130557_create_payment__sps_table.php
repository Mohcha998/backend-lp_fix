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
        Schema::create('payment__sps', function (Blueprint $table) {
            $table->id();
            $table->integer('id_parent')->constrained('prospect_parents');
            $table->string('link_pembayaran')->nullable();
            $table->string('no_invoice');
            $table->string('no_pemesanan')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('description')->nullable();
            $table->date('date_paid')->nullable();
            $table->integer('status_pembayaran');
            $table->integer('biaya_admin')->nullable();
            $table->integer('total');
            $table->integer('is_inv')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment__sps');
    }
};
