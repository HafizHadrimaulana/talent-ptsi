<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('employees', function (Blueprint $t) {
            if (!Schema::hasColumn('employees', 'profile_photo_url')) {
                $t->string('profile_photo_url', 500)->nullable()->after('talent_class_level');
            }
        });
    }

    public function down(): void {
        Schema::table('employees', function (Blueprint $t) {
            if (Schema::hasColumn('employees', 'profile_photo_url')) {
                $t->dropColumn('profile_photo_url');
            }
        });
    }
};
