<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees_snapshot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('employee_id', 64)->index();    // referensi ke employees.employee_id
            $table->json('payload');                       // raw SITMS row (opsional)
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('employees_snapshot');
    }
};
