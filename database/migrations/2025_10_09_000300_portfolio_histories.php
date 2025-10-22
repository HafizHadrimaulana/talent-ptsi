<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('portfolio_histories', function (Blueprint $table) {
            $table->id();
            $table->ulid('person_id')->nullable()->index();
            $table->string('employee_id', 64)->nullable()->index();
            $table->string('title', 200)->nullable();
            $table->string('unit_name', 150)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('portfolio_histories');
    }
};
