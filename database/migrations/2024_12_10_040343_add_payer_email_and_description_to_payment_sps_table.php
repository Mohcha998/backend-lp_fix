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
        Schema::table('payment__sps', function (Blueprint $table) {
            Schema::table('payment__sps', function (Blueprint $table) {
                $table->string('payer_email')->nullable();
                $table->string('description')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment__sps', function (Blueprint $table) {
            $table->dropColumn('payer_email');
            $table->dropColumn('description');
        });
    }
};
