<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 20)->unique();
            $table->string('full_name');
            $table->enum('care_status', ['stable', 'monitoring', 'critical'])->default('stable');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index(['care_status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
