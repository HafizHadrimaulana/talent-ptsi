<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('doc_type');
            $t->string('title')->nullable();
            $t->string('file_path');
            $t->string('source_system',32)->default('SITMS');
            $t->date('due_date')->nullable();
            $t->string('hash',191)->nullable();
            $t->timestamps();
            $t->index(['person_id','doc_type']);
            $t->index('due_date');
        });
    }
    public function down(): void {
        Schema::dropIfExists('documents');
    }
};
