<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('email');
            $table->string('city', 120)->nullable()->after('address');
        });
    }
    public function down(): void {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn(['address','city']);
        });
    }
};
