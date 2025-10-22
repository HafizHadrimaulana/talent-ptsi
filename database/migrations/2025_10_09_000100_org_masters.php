<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('directorates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->string('name', 150)->index();
            $table->unsignedBigInteger('directorate_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('directorate_id')->references('id')->on('directorates')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('position_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();     // raw string (mis: "Head Office")
            $table->string('type', 50)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('position_levels');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('units');
        Schema::dropIfExists('directorates');
    }
};
