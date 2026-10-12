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
        Schema::create('stations', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('name', 150);
            $table->string('river_basin', 100);
            $table->string('location', 200);
            $table->double('current_level_cm');
            $table->double('flow_rate_m3s');
            $table->string('alert_status', 50)->default('normal');
            $table->json('thresholds_cm');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
